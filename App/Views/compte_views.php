<?php
// check session status and start if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// include required models and configuration
require_once __DIR__ . '/../models/images_models.php';
require_once __DIR__ . '/../control/config.php';
require_once __DIR__ . '/../models/translation_models.php';

// initialize translations based on session language
$translationModel = new TranslationModel();
$lang = $_SESSION['lang'] ?? 'fr';
$t = $translationModel->getTranslations($lang);

// retrieve user information from session with sanitization
$username = htmlspecialchars($_SESSION['username'] ?? 'N/A');
$email = htmlspecialchars($_SESSION['email'] ?? 'N/A');
$status = $_SESSION['status'] ?? 'invalide';
$mode = $_SESSION['mode'] ?? 'standard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['account_title'] ?? 'My Account' ?></title>
    
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/style.css">
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/compte_views_style.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="account-container">

    <h1 class="page-title"><?= $t['account_title'] ?? 'My Account' ?></h1>

    <div class="account-grid">
        
        <div class="account-card info-card">
            <div class="card-header">
                <span class="icon">👤</span>
                <h3><?= $t['account_info'] ?? 'Account Information' ?></h3>
            </div>
            
            <div class="card-body">
                <div class="info-row">
                    <span class="label"><?= $t['username'] ?? 'Username' ?></span>
                    <span class="value"><?= $username ?></span>
                </div>
                
                <div class="info-row">
                    <span class="label"><?= $t['email'] ?? 'Email' ?></span>
                    <span class="value"><?= $email ?></span>
                </div>

                <div class="info-row">
                    <span class="label"><?= $t['status'] ?? 'Status' ?></span>
                    <span class="value status-badge <?= $status === 'valide' ? 'status-ok' : 'status-warn' ?>">
                        <?= ucfirst($status) ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="label"><?= $t['mode_label'] ?? 'Mode' ?></span>
                    <span class="value"><?= $mode ?></span>
                </div>

                <div class="card-actions">
                    <?php 
                    // conditionally display action buttons based on account status
                    if ($status === 'invalide'): ?>
                        <a href="<?=$BASE_URL?>/control/user_control.php?action=validateEmail" class="btn-lego btn-lego-yellow">
                            <?= $t['valide_email'] ?? 'Validate Email' ?>
                        </a>
                    <?php elseif ($status === 'valide'): ?>
                        <a href="<?=$BASE_URL?>/control/user_control.php?action=resetPassword" class="btn-lego btn-lego-blue">
                            <?= $t['reset_password'] ?? 'Reset Password' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="account-card security-card">
            <div class="card-header">
                <span class="icon"></span>
                <h3><?= $t['two_factor_auth'] ?? 'Security' ?></h3>
            </div>

            <div class="card-body centered-body">
                <p class="security-status">
                    <?= $mode === '2FA'
                        ? '<span class="text-green">' . ($t['2fa_enabled'] ?? '2FA Enabled') . '</span>'
                        : '<span class="text-grey">' . ($t['2fa_disabled'] ?? '2FA Disabled') . '</span>' ?>
                </p>

                <form action="<?=$BASE_URL?>/control/user_control.php" method="post">
                    <input type="hidden" name="mode" value="<?= $mode === '2FA' ? 'disable' : 'enable' ?>">
                    <button type="submit" name="toggle2FA" class="btn-lego <?= $mode === '2FA' ? 'btn-lego-red' : 'btn-lego-green' ?>">
                        <?= $mode === '2FA'
                            ? ($t['disable_2fa'] ?? 'Disable 2FA')
                            : ($t['enable_2fa'] ?? 'Enable 2FA') ?>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <div class="footer-actions">
        <a href="<?=$BASE_URL?>/views/setting_views.php" class="btn-lego btn-lego-blue">
            <?= $t['back'] ?? '← Back to Settings' ?>
        </a>
    </div>

</main>

<?php include __DIR__ . '/footer.html'; ?>
</body>
</html>