<?php
// --- CONFIGURATION DE BASE ---
// On calcule l'url de base (identique au header classique pour garder la cohérence)
$baseUrl = $_ENV['BASE_URL'] ?? dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($baseUrl, '/\\');

// Vérification de sécurité (optionnelle ici si gérée par le contrôleur)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/CSS/header.css">

<header class="header header-admin">
    <div class="header-container">
        
        <a href="<?= $baseUrl ?>/admin" class="logo">
            <img src="<?= $baseUrl ?>/img/logo_admin.png" alt="Admin Logo" class="logo-img">
        </a>

        <nav class="nav-menu main-nav">
            <?php if ($isAdmin): ?>
                
                <a href="<?= $baseUrl ?>/admin/stats" class="nav-link">
                    <?= $t['header_admin_stats'] ?? 'Statistiques' ?>
                </a>
                <a href="<?= $baseUrl ?>/admin/supplier" class="nav-link">
                    <?= $t['header_admin_supplier'] ?? 'Fournisseur' ?>
                </a>
                <a href="<?= $baseUrl ?>/stock" class="nav-link">
                    <?= $t['header_admin_inventory'] ?? 'Inventaire' ?>
                </a>
                <a href="<?= $baseUrl ?>/setting" class="nav-link">
                    <?= $t['header_admin_settings'] ?? 'Paramètres' ?>
                </a>

                <a href="<?= $baseUrl ?>/user/logout" class="btn-header-base btn-outline" style="margin-left: 15px;">
                    <?= $t['header_admin_logout'] ?? 'Déconnexion' ?>
                </a>

            <?php else: ?>
                <a href="<?= $baseUrl ?>/index.php" class="btn-header-base btn-primary">
                    <?= $t['header_admin_back'] ?? 'Retour au site' ?>
                </a>
            <?php endif; ?>
        </nav>
        
    </div>
</header>