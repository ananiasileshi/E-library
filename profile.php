<?php

declare(strict_types=1);

$title = 'Profile';

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/shelves.php';
require_once __DIR__ . '/includes/social.php';

$user = current_user();

if (!$user) {
    redirect(url('/login.php'));
    exit;
}

$stats = get_user_stats((int)$user['id']);
$shelves = get_or_create_default_shelves((int)$user['id']);

// Handle profile update
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));
    $website = trim((string)($_POST['website'] ?? ''));
    
    if ($name === '') {
        $errors[] = 'Name is required';
    } elseif (strlen($name) > 120) {
        $errors[] = 'Name must be under 120 characters';
    }
    
    if (strlen($bio) > 500) {
        $errors[] = 'Bio must be under 500 characters';
    }
    
    if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid website URL';
    }
    
    if (empty($errors)) {
        $stmt = db()->prepare("
            UPDATE users SET name = ?, bio = ?, website = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $bio ?: null, $website ?: null, $user['id']]);
        $success = true;
        
        // Refresh user data
        $user = current_user();
    }
}

require __DIR__ . '/partials/layout_top.php';

?>

<div class="profile-header mb-4">
    <div class="profile-avatar">
        <div class="avatar-circle">
            <i class="bi bi-person"></i>
        </div>
    </div>
    <div class="profile-info">
        <h1 class="profile-name"><?= e($user['name']) ?></h1>
        <div class="profile-role">
            <?php if ($user['role'] === 'admin'): ?>
                <span class="badge bg-danger"><i class="bi bi-shield-lock me-1"></i>Admin</span>
            <?php elseif ($user['role'] === 'premium'): ?>
                <span class="badge bg-warning"><i class="bi bi-star me-1"></i>Premium</span>
            <?php else: ?>
                <span class="badge bg-primary"><i class="bi bi-person me-1"></i>Reader</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2"></i>Your Stats</h5>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= e(number_format((int)$stats['books_read'])) ?></div>
                    <div class="profile-stat-label">Books Read</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= e(number_format((int)$stats['reviews'])) ?></div>
                    <div class="profile-stat-label">Reviews</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= e(number_format((int)$stats['followers'])) ?></div>
                    <div class="profile-stat-label">Followers</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= e(number_format((int)$stats['following'])) ?></div>
                    <div class="profile-stat-label">Following</div>
                </div>
            </div>
        </div>
        
        <div class="glass p-4 mt-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-collection me-2"></i>Your Shelves</h5>
            <div class="vstack gap-2">
                <?php foreach ($shelves as $shelf): ?>
                    <a href="<?= e(url('/dashboard.php?shelf=' . $shelf['slug'])) ?>" class="shelf-link">
                        <span class="shelf-name"><?= e($shelf['name']) ?></span>
                        <span class="shelf-count badge bg-secondary"><?= e((string)$shelf['book_count']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="glass p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-pencil me-2"></i>Edit Profile</h5>
            
            <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully!</div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" maxlength="120" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bio</label>
                    <textarea name="bio" class="form-control" rows="3" maxlength="500" placeholder="Tell us about yourself..."><?= e((string)($user['bio'] ?? '')) ?></textarea>
                    <div class="form-text">Max 500 characters</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Website</label>
                    <input type="url" name="website" class="form-control" value="<?= e((string)($user['website'] ?? '')) ?>" placeholder="https://example.com">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    <div class="form-text">Email cannot be changed</div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </form>
        </div>
        
        <div class="glass p-4 mt-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2"></i>Achievements</h5>
            <?php
            $achievements = db()->prepare("
                SELECT a.*, ua.earned_at
                FROM achievements a
                LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
                ORDER BY ua.earned_at DESC, a.id ASC
            ");
            $achievements->execute([$user['id']]);
            $userAchievements = $achievements->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="achievements-grid">
                <?php foreach ($userAchievements as $ach): ?>
                    <div class="achievement-card <?= $ach['earned_at'] ? 'earned' : 'locked' ?>">
                        <i class="bi <?= e($ach['icon']) ?>"></i>
                        <div class="achievement-name"><?= e($ach['name']) ?></div>
                        <div class="achievement-desc"><?= e($ach['description']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
