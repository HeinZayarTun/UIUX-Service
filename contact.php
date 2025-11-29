<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/db.php';

function get_base64_src($blob_data) {
    if (empty($blob_data) || strtolower($blob_data) === 'null') {
        return null; // No data or explicit null, no source
    }
    
    // Find the position of the separator "|"
    $separatorPos = strpos($blob_data, '|');
    
    if ($separatorPos !== false) {
        // Extract the MIME type (before "|") and the binary data (after "|")
        $mime_type = substr($blob_data, 0, $separatorPos);
        $binary_data = substr($blob_data, $separatorPos + 1);
        
        // Encode the binary data to base64
        $base64_img = base64_encode($binary_data);
        
        // Create the data URL for the image source
        return "data:{$mime_type};base64,{$base64_img}";
    }
    // If the data is present but doesn't have the expected '|' separator, treat it as invalid BLOB
    return null; 
}


// Function to fetch staff details (name and profile image BLOB data)
function get_staff_details($pdo, $staff_id) {
    $stmt = $pdo->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$staff_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function get_profile_image_url($blob_data) {
    
    return get_base64_src($blob_data);
}

// Fetch only approved services
// **CORRECTED SQL QUERY** to select only the existing columns plus 'status'
$sql = "SELECT id, title, description, price, image_data, uploaded_by_staff_id, status FROM services WHERE status = 'approved'";
$stmt = $pdo->query($sql);
$approved_services = $stmt->fetchAll();

?>
<?php include './includes/header.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services - UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <style>
        .service-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e9ecef;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .service-card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #f1f1f1;
        }
        .price-badge {
            font-size: 1.1rem;
            font-weight: 700;
            background-color: #5d98d8;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        .card-body-custom {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .card-title-service {
            font-size: 1.5rem;
            font-weight: 700;
            color: #343a40;
        }
        /* Style for the Staff Avatar */
        .staff-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            border: 2px solid #5d98d8;
            background-color: #f1f5f9;
            color: #5d98d8;
            font-size: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }
      
        .modal-body-proposal {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>


<div class="container my-5">
    <div class="row">
        <div class="col-12 text-center mb-5">
            <h1 class="display-5 fw-bold mb-3"><i class="bi bi-list-task me-2"></i>Our Professional Services</h1>
            <p class="lead text-muted">Explore the services we offer, complete with a preview and pricing.</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($approved_services) > 0): ?>
            <?php foreach ($approved_services as $service): 
                $staff = get_staff_details($pdo, $service['uploaded_by_staff_id']);
                $profile_image_url = get_profile_image_url($staff['profile_photo'] ?? null);
                // RATING PLACEHOLDER DELETED
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm">
                        
                        <?php if (isset($service['image_data'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($service['image_data']) ?>" 
                                 class="service-card-img" 
                                 alt="<?= htmlspecialchars($service['title']) ?>">
                        <?php else: ?>
                            <div class="service-card-img bg-light d-flex justify-content-center align-items-center" 
                                 style="height: 200px; color: #6c757d;">
                                 <i class="bi bi-image" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body-custom">
                            <div>
                                <h5 class="card-title-service mb-2"><?= htmlspecialchars($service['title']); ?></h5>
                                <p class="card-text text-muted small mb-3 text-truncate"><?= htmlspecialchars($service['description']); ?></p>
                                
                                <?php if ($staff): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($profile_image_url): ?>
                                        <img src="<?= $profile_image_url ?>" 
                                             class="staff-avatar" 
                                             alt="<?= htmlspecialchars($staff['name']) ?>'s Profile">
                                    <?php else: ?>
                                        <div class="staff-avatar">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">Provided by</small>
                                        <p class="fw-bold mb-0" style="color: #343a40; font-size: 0.9rem;">
                                            <?= htmlspecialchars($staff['name']); ?>
                                        </p>
                                    </div>
                                    <div class="ms-auto">
                                        <button class="btn btn-sm btn-outline-dark" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#proposalModal"
                                                data-title="<?= htmlspecialchars($service['title']) ?>"
                                                data-idea="<?= htmlspecialchars($service['description']) ?>"
                                                data-design="<?= isset($service['image_data']) ? 'data:image/jpeg;base64,' . base64_encode($service['image_data']) : 'placeholder' ?>"> <i class="bi bi-eye me-1"></i> Check Proposal
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="price-badge"><?= htmlspecialchars($service['price']); ?></span>
                                <a href="message.php?service_id=<?= $service['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Request Service
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    No services are currently available.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mt-5">
        <div class="col-12 text-center">
            <a href="message.php" class="btn btn-success btn-lg rounded-pill shadow-sm">
                <i class="bi bi-chat-dots-fill me-2"></i> Message Box
            </a>
            <a href="user_requests.php" class="btn btn-success btn-lg rounded-pill shadow-sm">
                <i class="bi bi-box-arrow-in-down-right"></i> Get Service Now
            </a>
        </div>
        
        
    </div>
    
</div>

<div class="modal fade" id="proposalModal" tabindex="-1" aria-labelledby="proposalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proposalModalLabel"><i class="bi bi-file-earmark-text me-2"></i>Project Proposal Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 id="modal-proposal-title" class="fw-bold text-primary mb-3"></h3>
                
                <hr>
                
                <h6 class="fw-bold">Proposal/Idea:</h6>
                <p class="modal-body-proposal" id="modal-proposal-idea"></p>
                
                <hr>
                
                <h6 class="fw-bold mb-3">Design Preview (Image):</h6>
                <div class="text-center">
                    <img id="modal-proposal-design" src="" alt="Design Preview" class="img-fluid rounded shadow-sm" style="max-height: 400px; width: auto;">
                    <p id="modal-design-placeholder" class="text-muted mt-2" style="display:none;">No specific design image uploaded for this proposal.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
// STAR RATING FUNCTION DELETED
// function generateStars(rating) { ... }

document.addEventListener('DOMContentLoaded', function() {
    var proposalModal = document.getElementById('proposalModal');
    proposalModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget; 
        
        // Extract info from data-bs-* attributes
        var title = button.getAttribute('data-title');
        var idea = button.getAttribute('data-idea');
        var design = button.getAttribute('data-design');
        // STAR RATING EXTRACTION DELETED
        
        // Update the modal's content elements
        var modalTitle = proposalModal.querySelector('#modal-proposal-title');
        var modalIdea = proposalModal.querySelector('#modal-proposal-idea');
        var modalDesign = proposalModal.querySelector('#modal-proposal-design');
        var modalPlaceholder = proposalModal.querySelector('#modal-design-placeholder');
        // STAR RATING ELEMENTS DELETED: var modalRating = proposalModal.querySelector('#modal-service-rating');
        // STAR RATING ELEMENTS DELETED: var modalRatingText = proposalModal.querySelector('#modal-rating-text');


        modalTitle.textContent = title;
        modalIdea.textContent = idea; 
        
        // STAR RATING UPDATE DELETED
        // modalRating.innerHTML = generateStars(rating); 
        // modalRatingText.textContent = `(${rating.toFixed(1)} / 5.0)`; 

        // Handle the design image display
        if (design && design !== 'placeholder') {
            modalDesign.src = design;
            modalDesign.style.display = 'block';
            modalPlaceholder.style.display = 'none';
        } else {
            modalDesign.src = '';
            modalDesign.style.display = 'none';
            modalPlaceholder.style.display = 'block';
        }
    });
});
</script>

</body>
</html> 

<?php include './includes/footer.php'; ?>
<?php include 'addBtn.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>