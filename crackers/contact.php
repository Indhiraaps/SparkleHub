<?php
// Ensure session starts before any output
session_start();

// 1. Connection and Cart Count Setup (Reuse logic from index.php)
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
<title>Contact Us | Sparkle Hub</title>

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

/* Contact Content Styles */
.contact-container {
    padding: 2rem 0;
}

.contact-card {
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

.contact-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s;
}

.contact-card:hover::before {
    left: 100%;
}

.contact-card:hover {
    transform: translateY(-5px);
    border-color: var(--border-glow);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        0 0 80px rgba(255, 107, 53, 0.2);
}

.contact-heading {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1.5rem;
}

.contact-subheading {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--light);
    margin-bottom: 1rem;
}

.contact-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

/* Contact Info Cards */
.contact-info-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    text-align: center;
}

.contact-info-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-5px);
    border-color: var(--border-glow);
}

.contact-info-card i {
    font-size: 2.5rem;
    color: var(--secondary);
    margin-bottom: 1rem;
}

.contact-info-card h5 {
    color: var(--light);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.contact-info-card p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Form Styles */
.form-control {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    padding: 0.8rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: var(--primary);
    box-shadow: 0 0 15px rgba(255, 107, 53, 0.3);
    color: white;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.form-label {
    color: var(--light);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.btn-primary {
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    border: none;
    border-radius: 25px;
    padding: 0.8rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
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
    
    .contact-heading {
        font-size: 2rem;
    }
    
    .contact-card {
        padding: 2rem;
    }
    
    .contact-subheading {
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
                        <a class="nav-link" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">
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
                Contact Us
            </h1>
            <p class="hero-subtitle fade-in-up">
                Get in touch with our team - we're here to help!
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container contact-container">
        <div class="contact-card fade-in-up">
            <h1 class="contact-heading text-center mb-4">📞 Get In Touch</h1>
            <p class="contact-text text-center lead">
                Have a question about an order or our products? We're here to help!
            </p>
            
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-5 mb-4">
                    <h3 class="contact-subheading mb-4">Contact Information</h3>
                    
                    <div class="contact-info-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h5>Our Location</h5>
                        <p>123 Sparkle St, Celebration City, IN 12345</p>
                    </div>
                    
                    <div class="contact-info-card">
                        <i class="fas fa-phone"></i>
                        <h5>Phone Number</h5>
                        <p>+91 98765 43210</p>
                    </div>
                    
                    <div class="contact-info-card">
                        <i class="fas fa-envelope"></i>
                        <h5>Email Address</h5>
                        <p>support@sparklehub.com</p>
                    </div>
                    
                    <div class="contact-info-card">
                        <i class="fas fa-clock"></i>
                        <h5>Business Hours</h5>
                        <p>Mon - Sun: 9:00 AM - 9:00 PM</p>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="col-lg-7">
                    <h3 class="contact-subheading mb-4">Send Us a Message</h3>
                    <form action="contact.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter your full name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required placeholder="What is this regarding?">
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Tell us how we can help you..."></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
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
        });
    </script>
</body>
</html>