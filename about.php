<?php

declare(strict_types=1);

$title = 'About Us';
require __DIR__ . '/partials/layout_top.php';

// Get stats
$statsStmt = db()->query('SELECT COUNT(*) as total_books FROM books WHERE status = "active"');
$stats = $statsStmt->fetch() ?: ['total_books' => 0];

$usersStmt = db()->query('SELECT COUNT(*) as total_users FROM users');
$userStats = $usersStmt->fetch() ?: ['total_users' => 0];

?>

<!-- About Hero -->
<div class="about-hero mb-5">
    <div class="about-hero-content">
        <h1 class="about-hero-title">About <span class="text-gradient">Our Library</span></h1>
        <p class="about-hero-subtitle">Empowering readers with access to thousands of books, fostering a community of lifelong learners and book enthusiasts.</p>
    </div>
</div>

<!-- Mission Section -->
<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="glass p-4 h-100">
            <h3 class="fw-bold mb-3">Our Mission</h3>
            <p class="text-muted mb-3">We believe that access to knowledge should be universal. Our digital library platform was created to break down barriers to reading and learning, making books accessible to everyone, everywhere.</p>
            <p class="text-muted">Whether you're a student, professional, or casual reader, we're here to support your journey of discovery and growth.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass p-4 h-100">
            <h3 class="fw-bold mb-3">What We Offer</h3>
            <ul class="list-unstyled mb-0">
                <li class="d-flex align-items-start gap-3 mb-3">
                    <div class="about-feature-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <div>
                        <strong>Vast Collection</strong>
                        <p class="text-muted small mb-0">Thousands of books across multiple genres and formats</p>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-3 mb-3">
                    <div class="about-feature-icon">
                        <i class="bi bi-cloud-download"></i>
                    </div>
                    <div>
                        <strong>Easy Downloads</strong>
                        <p class="text-muted small mb-0">Download books for offline reading anytime</p>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-3 mb-3">
                    <div class="about-feature-icon">
                        <i class="bi bi-person-heart"></i>
                    </div>
                    <div>
                        <strong>Personalized Experience</strong>
                        <p class="text-muted small mb-0">Custom shelves, reading progress, and recommendations</p>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-3">
                    <div class="about-feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <strong>Community</strong>
                        <p class="text-muted small mb-0">Connect with fellow readers, share reviews and insights</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="glass p-4 mb-5">
    <h3 class="fw-bold text-center mb-4">Our Impact in Numbers</h3>
    <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
            <div class="about-stat">
                <div class="about-stat-value"><?= e(number_format((int)$stats['total_books'])) ?></div>
                <div class="about-stat-label">Books Available</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="about-stat">
                <div class="about-stat-value"><?= e(number_format((int)$userStats['total_users'])) ?></div>
                <div class="about-stat-label">Active Readers</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="about-stat">
                <div class="about-stat-value">50+</div>
                <div class="about-stat-label">Categories</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="about-stat">
                <div class="about-stat-value">24/7</div>
                <div class="about-stat-label">Access</div>
            </div>
        </div>
    </div>
</div>

<!-- Values Section -->
<div class="row g-4 mb-5">
    <div class="col-12">
        <h3 class="fw-bold text-center mb-4">Our Core Values</h3>
    </div>
    <div class="col-md-4">
        <div class="glass p-4 text-center h-100">
            <div class="about-value-icon mx-auto mb-3">
                <i class="bi bi-universal-access"></i>
            </div>
            <h5 class="fw-bold">Accessibility</h5>
            <p class="text-muted small mb-0">Making knowledge accessible to everyone, regardless of location or background.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass p-4 text-center h-100">
            <div class="about-value-icon mx-auto mb-3">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5 class="fw-bold">Quality</h5>
            <p class="text-muted small mb-0">Curating only the best content to ensure a valuable reading experience.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass p-4 text-center h-100">
            <div class="about-value-icon mx-auto mb-3">
                <i class="bi bi-heart"></i>
            </div>
            <h5 class="fw-bold">Community</h5>
            <p class="text-muted small mb-0">Building a supportive space where readers can connect and grow together.</p>
        </div>
    </div>
</div>

<!-- Team/Contact Section -->
<div class="glass p-4 mb-5">
    <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h3 class="fw-bold mb-3">Get in Touch</h3>
            <p class="text-muted mb-4">Have questions, suggestions, or want to collaborate? We'd love to hear from you.</p>
            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-envelope text-primary fs-5"></i>
                    <span>support@elibrary.com</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-geo-alt text-primary fs-5"></i>
                    <span>Addis Ababa, Ethiopia</span>
                </div>
                <div class="d-flex gap-3 mt-2">
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <form class="about-contact-form">
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Your Name">
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" placeholder="Your Email">
                </div>
                <div class="mb-3">
                    <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Message</button>
            </form>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="glass p-4 text-center" style="background: linear-gradient(135deg, rgba(46,144,250,.15), rgba(155,81,224,.12))">
    <h3 class="fw-bold mb-2">Ready to Start Reading?</h3>
    <p class="text-muted mb-3">Join our community of book lovers today.</p>
    <a href="<?= e(url('/register.php')) ?>" class="btn btn-primary btn-lg">Create Free Account</a>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
