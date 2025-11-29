<?php
require 'config/db.php';
require 'includes/auth.php';

// start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_base64_src($blob_data) {
    if (empty($blob_data)) {
        return null; 
    }
    
    $separatorPos = strpos($blob_data, '|');
    
    if ($separatorPos !== false) {
        $mime_type = substr($blob_data, 0, $separatorPos);
        $binary_data = substr($blob_data, $separatorPos + 1);
        $base64_img = base64_encode($binary_data);
        
        return "data:{$mime_type};base64,{$base64_img}";
    }
    return null; 
}


function get_avatar_html($photoData, $userName, $class = 'chat-avatar') {
    $image_src = get_base64_src($photoData);

    if (!empty($image_src)) {
        return '<img src="' . htmlspecialchars($image_src) . '" alt="' . htmlspecialchars($userName) . '" class="' . htmlspecialchars($class) . '" data-bs-toggle="tooltip" title="' . htmlspecialchars($userName) . '">';
    } else {
        return '<div class="' . htmlspecialchars($class) . ' bg-secondary text-white d-flex justify-content-center align-items-center" data-bs-toggle="tooltip" title="' . htmlspecialchars($userName) . '">
                    <i class="bi bi-person-circle fs-5"></i>
                </div>';
    }
}


// 1. Initial Checks & User ID Determination

$userId = null;
$currentUserName = null; 
$loggedInUserPhotoData = null; 
if (!empty($_SESSION['user']['id'])) {
    $userId = (int)$_SESSION['user']['id'];
    $currentRole = $_SESSION['user']['role'] ?? null;
    $currentUserName = $_SESSION['user']['name'] ?? 'You';
    
    $userPhotoStmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $userPhotoStmt->execute([$userId]);
    $loggedInUserPhotoData = $userPhotoStmt->fetchColumn();

} elseif (!empty($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
    $currentRole = $_SESSION['role'] ?? null;
    $currentUserName = $_SESSION['name'] ?? 'You';
}

if (!$userId || !$currentRole) {
    header("Location: login.php");
    exit;
}


// 2. Determine Chat Partner (selected_user_id)

$selectedUserId = null;
$selectedUser = []; 
if (isset($_GET['chat_with'])) {
    $selectedUserId = (int)$_GET['chat_with'];
    
    $checkStmt = $pdo->prepare("SELECT id, name, role, profile_photo FROM users WHERE id = ?"); 
    $checkStmt->execute([$selectedUserId]);
    $selectedUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedUser) {
        $selectedUserId = null; 
        $selectedUser = [];
    }
}


// 3. Handle Send Message (POST Request)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiver = (int)$_POST['receiver_id'];
    $msg = trim($_POST['message']);

    if ($msg !== '') {
        
        $checkRoleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $checkRoleStmt->execute([$receiver]);
        $r = $checkRoleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$r || $receiver === $userId) {
            header("Location: message.php");
            exit;
        }

        $receiverRole = $r['role'];

        if ($currentRole === 'customer') {
            if ($receiverRole !== 'admin' && $receiverRole !== 'staff') {
                header("Location: message.php");
                exit;
            }
        }
        
        // ADDED: Staff to Admin/Staff to Customer chat validation
        if ($currentRole === 'staff') {
            // Staff is allowed to talk to any admin.
            // Staff is allowed to talk to customers associated with their projects (already handled by contact list query).
            // Staff should not be able to send messages to other staff or non-contact users.
            
            // To be thorough, check if the receiver is an admin OR is in the staff's contact list (which includes assigned customers)
            if ($receiverRole !== 'admin' && $receiverRole !== 'super_admin') { // Assuming 'super_admin' exists
                // If not an admin, check if they are in the contact list (which covers assigned customers/staff if applicable)
                $is_in_contact_list = false;
                // A quick, inefficient check for simplicity in a single file logic:
                $checkContactStmt = $pdo->prepare("
                    SELECT 1 FROM project_requests pr
                    WHERE pr.staff_id = ? AND pr.user_id = ?
                    LIMIT 1
                ");
                $checkContactStmt->execute([$userId, $receiver]);
                if ($checkContactStmt->fetchColumn()) {
                    $is_in_contact_list = true;
                }

                if (!$is_in_contact_list) {
                    header("Location: message.php");
                    exit;
                }
            }
        }
        // END ADDED

        $ins = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $ins->execute([$userId, $receiver, $msg]);

        header("Location: message.php?chat_with=" . $receiver);
        exit;
    }
    header("Location: message.php" . ($selectedUserId ? "?chat_with=" . $selectedUserId : ""));
    exit;
}


// 4. Fetch Users for the Contact List

if ($currentRole === 'customer') {
    // Customers can chat with admin and assigned staff
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.name, u.role, u.profile_photo
        FROM users u
        LEFT JOIN project_requests pr ON pr.staff_id = u.id
        WHERE (u.role IN ('admin', 'super_admin') OR (u.role = 'staff' AND pr.user_id = ?))
        AND u.id != ?
        ORDER BY u.role DESC, u.name
    ");
    $usersStmt->execute([$userId, $userId]);

} elseif ($currentRole === 'staff') {
    // Staff can chat with:
    // 1. Admin/Super Admin
    // 2. Customers linked to their assigned projects
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.name, u.role, u.profile_photo
        FROM users u
        LEFT JOIN project_requests pr ON pr.user_id = u.id OR pr.staff_id = u.id 
        WHERE (u.role IN ('admin', 'super_admin'))
        OR (u.id IN (SELECT user_id FROM project_requests WHERE staff_id = ?))
        AND u.id != ?
        ORDER BY FIELD(u.role, 'super_admin', 'admin', 'staff'), u.name
    ");
    $usersStmt->execute([$userId, $userId]);

} elseif ($currentRole === 'admin') {
    // Admin can chat with all users and staff
    $usersStmt = $pdo->prepare("
        SELECT id, name, role, profile_photo
        FROM users
        WHERE id != ?
        ORDER BY name
    ");
    $usersStmt->execute([$userId]);
}

$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Auto-select first user if no one chosen
if (!$selectedUserId && !empty($allUsers)) {
    $selectedUserId = (int)$allUsers[0]['id'];
    $selectedUser = $allUsers[0];
}

// 5. Fetch Messages for the Selected User
// ... (REST OF THE CODE REMAINS UNCHANGED)

$messages = [];
$lastMessageId = 0; // Initialize for JavaScript Polling
if ($selectedUserId) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
                u.name AS sender_name, u.profile_photo AS sender_photo
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
          OR (m.receiver_id = ? AND m.sender_id = ?)
        ORDER BY m.created_at ASC 
    ");
    $stmt->execute([$userId, $selectedUserId, $userId, $selectedUserId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the highest ID for JS polling initialization
    if (!empty($messages)) {
        $lastMessageId = end($messages)['id'];
    }
}

$chattingWithName = $selectedUser['name'] ?? 'Select a Contact';

include 'includes/header.php';
?>

<style>
    /* Custom styles for the chat interface */
    body {
        background-color: #f0f2f5; 
    }
    .chat-container {
        display: flex;
        height: calc(100vh - 120px); 
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
        background-color: #fff;
    }
    .contact-list {
        width: 300px;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
        background-color: #f8f9fa;
        flex-shrink: 0; 
    }
    .chat-window {
        /* Set position relative for the toast positioning */
        position: relative;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background-color: #fff;
    }
    .chat-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1rem;
        background-color: #e5ddd5; 
    }
    .message-bubble {
        padding: 0.75rem 1rem;
        border-radius: 1.5rem;
        margin-bottom: 0.5rem;
        max-width: 75%;
        word-wrap: break-word;
        box-shadow: 0 1px 1px rgba(0,0,0,0.08); 
    }
    .message-sent {
        background-color: #dcf8c6; 
        margin-left: auto;
        text-align: right;
    }
    .message-received {
        background-color: #ffffff; 
        margin-right: auto;
        text-align: left;
    }
    .chat-input {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        background-color: #f0f2f5; 
    }
    .contact-item {
        padding: 1rem;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
        display: flex; 
        align-items: center;
    }
    .contact-item:hover, .contact-item.active {
        background-color: #e9ecef;
    }

    /* Profile Avatar Styling for Chat */
    .chat-avatar {
        width: 40px; 
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0; 
    }
    /* Icon Fallback Specific Styles */
    .chat-avatar.bg-secondary {
        background-color: #6c757d !important;
    }
    
    /* Margin for avatars in contact list */
    .contact-item .chat-avatar {
        margin-right: 15px;
    }
    /* Margin for avatars in chat messages */
    .message-wrapper {
        display: flex;
        align-items: flex-end; 
        margin-bottom: 10px;
    }
    .message-wrapper.sent {
        justify-content: flex-end;
    }
    .message-wrapper.received {
        justify-content: flex-start;
    }
    .message-wrapper .chat-avatar {
        margin: 0 10px; 
    }
    .message-wrapper.sent .chat-avatar {
        order: 2; 
    }
    .message-wrapper.received .chat-avatar {
        order: 0; 
    }
    .message-wrapper .message-bubble {
        order: 1;
        margin-bottom: 0; 
    }
    .message-content {
        color: #333;
    }

    /* === NEW STYLES FOR TOAST NOTIFICATION === */
    .incoming-message-toast {
        position: absolute;
        top: 10px; 
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050; 
        width: 90%;
        max-width: 400px;
        padding: 10px 15px;
        border-radius: 8px;
        background-color: #28a745; /* Green success color */
        color: #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        opacity: 0; 
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    .incoming-message-toast.show {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0); 
    }
    .toast-body-text {
        flex-grow: 1;
        font-weight: bold;
        font-size: 0.95rem;
    }
</style>

<div class="container my-4">
    <div class="chat-container shadow">
        
        <div class="contact-list">
            <h5 class="p-3 mb-0 border-bottom">Contacts</h5>
            <?php if (empty($allUsers)): ?>
                <div class="p-3 text-muted">No contacts available.</div>
            <?php endif; ?>
            <?php foreach ($allUsers as $u): 
                $contactPhotoData = $u['profile_photo'] ?? null;
            ?>
                <a href="message.php?chat_with=<?= $u['id'] ?>" class="text-decoration-none text-dark">
                    <div class="contact-item <?= ((int)$selectedUserId === (int)$u['id']) ? 'active' : '' ?>">
                        
                        <?= get_avatar_html($contactPhotoData, $u['name'], 'chat-avatar') ?>
                        
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars(ucfirst($u['role'])) ?></small>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="chat-window">
            
            <div class="incoming-message-toast" id="messageToast">
                <i class="bi bi-chat-dots-fill me-2 fs-5"></i>
                <span class="toast-body-text" id="toastMessageText">New Message! Click to view.</span>
                <i class="bi bi-x-lg ms-auto text-light" onclick="document.getElementById('messageToast').classList.remove('show'); event.stopPropagation();"></i>
            </div>
            <div class="chat-header">
                <h4 class="mb-0"><?= htmlspecialchars($chattingWithName) ?></h4>
                <small class="text-muted">
                    <?php if ($selectedUserId && !empty($selectedUser)): ?>
                        Chatting with <?= htmlspecialchars($selectedUser['name']) ?> (<?= htmlspecialchars(ucfirst($selectedUser['role'] ?? 'N/A')) ?>)
                    <?php else: ?>
                        Start a new conversation by selecting a contact.
                    <?php endif; ?>
                </small>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if ($selectedUserId): ?>
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted mt-5">Hello <?= htmlspecialchars($selectedUser['name']) ?>! 👋</div>
                    <?php endif; ?>

                    <?php foreach ($messages as $m): 
                        $isSent = (int)$m['sender_id'] === $userId;
                        $avatarPhotoData = $isSent ? $loggedInUserPhotoData : $m['sender_photo'];
                        $avatarName = $isSent ? $currentUserName : $m['sender_name'];
                    ?>
                        
                        <div class="message-wrapper <?= $isSent ? 'sent' : 'received' ?>" data-message-id="<?= $m['id'] ?>">
                            
                            <?php if (!$isSent): ?>
                                <?= get_avatar_html($avatarPhotoData, $avatarName, 'chat-avatar') ?>
                            <?php endif; ?>

                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>">
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 5px;">
                                    <?= date('H:i', strtotime($m['created_at'])) ?>
                                </div>
                            </div>
                            
                            <?php if ($isSent): ?>
                                <?= get_avatar_html($loggedInUserPhotoData, $currentUserName, 'chat-avatar') ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted mt-5">Select a contact to begin chatting.</div>
                <?php endif; ?>
            </div>

            <div class="chat-input">
                <?php if ($selectedUserId): ?>
                    <form method="post" class="d-flex" id="chat-form">
                        <input type="hidden" name="receiver_id" value="<?= $selectedUserId ?>">
                        <input type="text" name="message" id="chat-input-message" class="form-control me-2" placeholder="Type your message..." required>
                        <button class="btn btn-success" type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-0">Please select a user from the contact list to send a message.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const chatMessages = document.getElementById('chat-messages');
    const messageToast = document.getElementById('messageToast');
    const toastMessageText = document.getElementById('toastMessageText');
    
    // PHP variables passed to JS
    let selectedUserId = <?= $selectedUserId ?? 'null' ?>;
    let lastMessageId = <?= $lastMessageId ?? 0 ?>;
    let currentUserId = <?= $userId ?>;

    // Helper function to get the base64 avatar HTML for the current user safely
    function getCurrentUserAvatarHtml() {

        return '<div class="chat-avatar bg-secondary text-white d-flex justify-content-center align-items-center" data-bs-toggle="tooltip" title="<?= $currentUserName ?>"><i class="bi bi-person-circle fs-5"></i></div>';
    }


    // Function to render a new message into the chat window
    function renderNewMessage(message) {
        const isSent = parseInt(message.sender_id) === currentUserId;
        const wrapperClass = isSent ? 'sent' : 'received';
        const bubbleClass = isSent ? 'message-sent' : 'message-received';
        
        // Determine the correct avatar HTML
        let senderAvatar = isSent ? getCurrentUserAvatarHtml() : 
            `<div class="chat-avatar bg-secondary text-white d-flex justify-content-center align-items-center" data-bs-toggle="tooltip" title="${message.sender_name}"><i class="bi bi-person-circle fs-5"></i></div>`;

        const messageTime = new Date(message.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'});

        const messageHtml = `
            <div class="message-wrapper ${wrapperClass}" data-message-id="${message.id}">
                ${!isSent ? senderAvatar : ''}
                <div class="message-bubble ${bubbleClass}">
                    <div class="message-content">
                        ${message.message}
                    </div>
                    <div class="text-muted" style="font-size: 0.7rem; margin-top: 5px;">
                        ${messageTime}
                    </div>
                </div>
                ${isSent ? getCurrentUserAvatarHtml() : ''}
            </div>
        `;
        
        chatMessages.insertAdjacentHTML('beforeend', messageHtml);
    }
    
    // --- Polling Function ---
    function pollForNewMessages() {
        if (!selectedUserId) return; 
        
        fetch(`fetch_new_messages.php?chat_with=${selectedUserId}&last_id=${lastMessageId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    
                    // 1. Handle messages from the currently open chat partner
                    if (data.newMessages.length > 0) {
                        data.newMessages.forEach(message => {
                            renderNewMessage(message);
                            if (parseInt(message.id) > lastMessageId) {
                                lastMessageId = parseInt(message.id);
                            }
                        });
                        // Scroll to bottom after new messages
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                    
                    // 2. Handle toast notification for messages from OTHERS
                    if (data.messagesFromOthers && !messageToast.classList.contains('show')) {
                        const senderName = data.messagesFromOthers.sender_name || 'Unknown User';
                        toastMessageText.textContent = `New message from ${senderName}! Click to view.`;
                        messageToast.classList.add('show');

                        setTimeout(() => {
                            messageToast.classList.remove('show');
                        }, 5000); 
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching new messages:', error);
                // Optionally hide the toast if the error is severe
                messageToast.classList.remove('show');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initial scroll
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // --- Start Polling ---
        if (selectedUserId) {
            setInterval(pollForNewMessages, 3000); 
        }

        // --- Toast Click Action ---
        messageToast.addEventListener('click', function() {
             messageToast.classList.remove('show');
             // Consider adding logic here to redirect or update the chat window based on the toast sender.
        });
        
        // --- Tooltip Initialization (Bootstrap) ---
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
             var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
             var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
               return new bootstrap.Tooltip(tooltipTriggerEl)
             })
        }
    });
</script>

<?php include 'includes/footer.php'; ?>