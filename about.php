<?php
require 'config/db.php';
// Fetch all content to display on the public page
$stmt = $pdo->query("SELECT * FROM content ORDER BY created_at DESC");
$contents = $stmt->fetchAll();
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us - UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/styles.css">   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f0f2f5; /* Light gray background */
        }
        .hero-section {
            background-color: #fff;
            padding: 5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .card {
            border-radius: 1rem;
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #fff; /* White card background */
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-body i {
            font-size: 2.5rem; /* Larger icons */
            margin-bottom: 1rem;
        }
        .text-primary-custom {
            color: #6a11cb !important; 
        }
        .text-success-custom {
            color: #20e3b2 !important; 
        }
        .text-warning-custom {
            color: #ffc107 !important; 
        }
        h1, .card-title {
            font-family: 'Playfair Display', serif;
        }
        .btn-primary-custom {
            background-color: #200041ff;
            border-color: #6a11cb;
            transition: background-color 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #4b0e8a;
            border-color: #4b0e8a;
        }
    </style>
</head>
<body>
<?php include './includes/header.php'; ?>
<div class="container-fluid hero-section">
    <div class="row align-items-center justify-content-center">
        <div class="col-md-5 order-md-2 text-center text-md-start">
            <h1 class="display-3 fw-bold mb-4">Crafting Digital Experiences</h1>
            <p class="lead text-muted">We are a passionate team dedicated to creating seamless and engaging digital products that people love to use.</p>
            <p class="mt-4">Our focus is on designing user-centered interfaces that are not only beautiful but also intuitive and highly functional. We believe great design is the foundation of a successful product.</p>
            <a href="contact.php" class="btn btn-lg text-light btn-primary-custom mt-3">Let's Work Together</a>
        </div>
        <div class="col-md-5 order-md-1 text-center">
            <img src="./img/UI-UX-scaled.webp" class="img-fluid rounded-4 shadow-lg" alt="UI/UX Design Process">
        </div>
    </div>
</div>

<hr class="my-5">

<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">Our Core Principles</h2>
    </div>

    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-lightbulb-fill text-primary-custom"></i>
                    <h5 class="card-title mt-3">Innovative Solutions</h5>
                    <p class="card-text text-muted">We bring fresh ideas and creative approaches to solve complex design challenges with a forward-thinking mindset.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-people-fill text-success-custom"></i>
                    <h5 class="card-title mt-3">User-Centered Approach</h5>
                    <p class="card-text text-muted">Our designs are always focused on the user, ensuring a delightful and effortless experience from start to finish.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-award-fill text-warning-custom"></i>
                    <h5 class="card-title mt-3">Quality & Excellence</h5>
                    <p class="card-text text-muted">We maintain the highest standards to deliver top-notch design quality that consistently exceeds expectations.</p>
                </div>
            </div>
        </div>
    </div>
</div>


</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include './includes/footer.php'; ?>
<?php include 'addBtn.php'; ?>