<?php

// vérification de connexion pour le menu
$isLoggedIn = isset($_SESSION['user_id']);

// on s'assure que base_url est disponible
$baseUrl = $_ENV['BASE_URL'] ?? '';
?>

<header>
    <nav class="navbar">
        <div class="logo-container">
            <a href="<?= $baseUrl ?>/index.php">
                <img src="<?= $baseUrl ?>/images/logo.png" alt="Logo" class="logo">
            </a>
        </div>
        
        <ul class="nav-links">
            <?php if ($isLoggedIn): ?>
                <li><a href="<?= $baseUrl ?>/commande"><?= $trans['menu_orders'] ?? 'Mes Commandes' ?></a></li>
                <li><a href="<?= $baseUrl ?>/user/logout" class="btn-logout"><?= $trans['menu_logout'] ?? 'Déconnexion' ?></a></li>
            <?php else: ?>
                <li><a href="<?= $baseUrl ?>/setting"><?= $trans['menu_settings'] ?? 'Paramètres' ?></a></li>
                <li><a href="<?= $baseUrl ?>/user/login"><?= $trans['menu_login'] ?? 'Connexion' ?></a></li>
                <li><a href="<?= $baseUrl ?>/user/register"><?= $trans['menu_register'] ?? 'Inscription' ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>