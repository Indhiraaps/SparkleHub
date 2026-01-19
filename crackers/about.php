<?php
// Ensure session starts before any output
session_start();

// 1. Connection and Error Handling Setup
require 'inc/db.php'; 

// 5. Cart Count (for navbar)
$session_id = session_id();
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE session_id = ?");
    $count_stmt->execute([$session_id]);
    $cart_count = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $cart_count = 0;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sparkle Hub | About Us</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #ff6b35;
    --secondary: #ffa500;
    --accent: #ff4757;
    --dark: #1a1a2e;
    --darker: #0f0f1a;
    --light: #ffffff;
    --glass: rgba(255, 255, 255, 0.1);
    --glass-dark: rgba(0, 0, 0, 0.3);
    --border-glow: rgba(255, 107, 53, 0.4);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 50%, #16213e 100%);
    min-height: 100vh;
    color: var(--light);
    overflow-x: hidden;
}

/* Animated Background */
.bg-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2;
    background: 
        radial-gradient(circle at 20% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 165, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(255, 71, 87, 0.05) 0%, transparent 50%);
    animation: backgroundShift 20s ease-in-out infinite;
}

@keyframes backgroundShift {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(1deg); }
}

/* Floating Particles */
.particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.particle {
    position: absolute;
    background: radial-gradient(circle, var(--secondary) 0%, transparent 70%);
    border-radius: 50%;
    animation: float 15s infinite linear;
}

@keyframes float {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.7; }
    90% { opacity: 0.7; }
    100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
}

/* Navbar Styles */
.navbar {
    background: rgba(26, 26, 46, 0.95) !important;
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-glow);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    padding: 1rem 0;
    transition: all 0.3s ease;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.8rem;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-link {
    color: var(--light) !important;
    font-weight: 500;
    margin: 0 0.5rem;
    padding: 0.5rem 1rem !important;
    border-radius: 25px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.nav-link.active {
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    color: white !important;
}

.badge {
    background: linear-gradient(45deg, var(--accent), #ff6b9d) !important;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Hero Section */
.hero-section {
    text-align: center;
    padding: 4rem 0 2rem;
    position: relative;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { text-shadow: 0 0 20px rgba(255, 107, 53, 0.5); }
    to { text-shadow: 0 0 30px rgba(255, 165, 0, 0.8), 0 0 40px rgba(255, 107, 53, 0.6); }
}

.hero-subtitle {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
}

/* About Content Styles */
.about-container {
    padding: 2rem 0;
}

.about-card {
    background: var(--glass);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 3rem;
    margin-bottom: 2rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.about-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s;
}

.about-card:hover::before {
    left: 100%;
}

.about-card:hover {
    transform: translateY(-5px);
    border-color: var(--border-glow);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        0 0 80px rgba(255, 107, 53, 0.2);
}

.about-heading {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1.5rem;
}

.about-subheading {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--light);
    margin-bottom: 1rem;
}

.about-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.about-lead {
    font-size: 1.3rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    line-height: 1.6;
}

/* Image Section */
.image-section {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.image-section img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    transition: transform 0.5s ease;
    display: block;
}

.image-section:hover img {
    transform: scale(1.05);
}

/* Feature Boxes */
.feature-box {
    text-align: center;
    padding: 2.5rem 1.5rem;
    border-radius: 15px;
    background: var(--glass);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.feature-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s;
}

.feature-box:hover::before {
    left: 100%;
}

.feature-box i {
    color: var(--secondary);
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    font-size: 3rem;
}

.feature-box h3 {
    color: var(--light);
    font-weight: 600;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.feature-box p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    transition: all 0.3s ease;
}

.feature-box:hover {
    background: linear-gradient(45deg, var(--primary), var(--accent));
    transform: translateY(-10px);
    box-shadow: 
        0 15px 35px rgba(0, 0, 0, 0.3),
        0 0 60px rgba(255, 107, 53, 0.3);
    border-color: var(--border-glow);
}

.feature-box:hover i,
.feature-box:hover h3,
.feature-box:hover p {
    color: white !important;
}

/* Footer */
footer {
    background: rgba(10, 10, 20, 0.8);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem 0;
    margin-top: 4rem;
    text-align: center;
    color: rgba(255, 255, 255, 0.8);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.stagger-animation > * {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .about-heading {
        font-size: 2rem;
    }
    
    .about-card {
        padding: 2rem;
    }
    
    .about-subheading {
        font-size: 1.5rem;
    }
}
</style>
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="particles" id="particles"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-sparkles me-2"></i>Sparkle Hub
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_orders.php">
                            <i class="fas fa-receipt me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart 
                            <span class="badge"><?= $cart_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light" href="admin/login.php">
                            <i class="fas fa-user-shield me-1"></i>Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title fade-in-up">
                About Sparkle Hub
            </h1>
            <p class="hero-subtitle fade-in-up">
                Crafting Unforgettable Moments, Safely
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container about-container">
        <div class="about-card fade-in-up">
            <h1 class="about-heading text-center mb-4">Our Promise: Quality, Safety, Joy 🚀</h1>
            
            <div class="row align-items-center mb-5 pb-4">
                <div class="col-md-6">
                    <h3 class="about-subheading mb-3">Illuminating Celebrations Worldwide</h3>
                    <p class="about-lead">
                        At <strong>Sparkle Hub</strong>, we believe every celebration deserves to be brilliant, reliable, and safe. Our commitment goes beyond selling products—we deliver <strong>peace of mind</strong> and <strong>pure excitement</strong>.
                    </p>
                    <p class="about-text">
                        Established on the principles of <strong>integrity and customer trust</strong>, we meticulously source our inventory. We partner only with certified suppliers who meet stringent national and international safety standards, ensuring that every purchase is spectacular and secure.
                    </p>
                    <p class="about-text fst-italic">
                        Join thousands of satisfied customers who trust Sparkle Hub for their special occasions.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="image-section">
                        <img src="colorful_fireworks.jpg" 
                             alt="Certified Safety & Quality" 
                             class="img-fluid">
                    </div>
                </div>
            </div>

            <div class="row pt-4 g-4 stagger-animation">
                <div class="col-md-6">
                    <div class="feature-box">
                        <i class="fas fa-hand-holding-heart"></i>
                        <h3>Our Core Mission</h3>
                        <p>
                            To <strong>illuminate celebrations</strong> globally by providing the most diverse, high-quality, and <strong>safest</strong> range of products, always putting the customer experience first.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-box">
                        <i class="fas fa-users-cog"></i>
                        <h3>Our Dedication</h3>
                        <p>
                            To constantly <strong>innovate and improve</strong>, ensuring a seamless online shopping experience, fast delivery, and expert support that guarantees satisfaction every single time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p class="mb-0">© <?= date("Y") ?> Sparkle Hub | All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 100 + 50;
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 20 + 10;
                const animationDelay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}vw`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${animationDelay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Stagger animation delays
            const staggerItems = document.querySelectorAll('.stagger-animation > *');
            staggerItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.2}s`;
            });
        });
    </script>
</body>
</html>