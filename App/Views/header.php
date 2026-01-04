<?php

// vérification de connexion pour le menu
$isLoggedIn = isset($_SESSION['user_id']);

// on s'assure que base_url est disponible
$baseUrl = $_ENV['BASE_URL'] ?? '';
?>

<header>
    <div class="header-container">
        <a href="<?= $_ENV['BASE_URL'] ?? '' ?>/index.php" class="logo">
            <img src="<?= $_ENV['BASE_URL'] ?? '' ?>/img/logo.png" alt="Img2Brick Logo">
        </a>

        <nav class="main-nav">
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="profile-menu">
                        <div class="profile-trigger">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Mon Compte') ?></span>
                            <img src="<?= $_ENV['BASE_URL'] ?? '' ?>/img/default_avatar.png" alt="Avatar" class="avatar-mini" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'User') ?>&background=random'">
                        </div>
                        <ul class="dropdown">
                            <li><a href="<?= $baseUrl ?>/setting"><?= isset($t) ? ($t['mon_profil'] ?? 'Settings') : 'Settings' ?></a></li>
                            <li><a href="<?= $_ENV['BASE_URL'] ?? '' ?>/commande"><?= isset($t) ? ($t['mes_commandes'] ?? 'Mes Commandes') : 'Mes Commandes' ?></a></li>
                            <li class="separator"></li>
                            <li><a href="<?= $_ENV['BASE_URL'] ?? '' ?>/user/logout" class="logout-btn"><?= isset($t) ? ($t['deconnexion'] ?? 'Déconnexion') : 'Déconnexion' ?></a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="<?= $_ENV['BASE_URL'] ?? '' ?>/user/login" class="nav-link"><?= isset($t) ? ($t['connexion'] ?? 'Connexion') : 'Connexion' ?></a></li>
                    <li><a href="<?= $_ENV['BASE_URL'] ?? '' ?>/user/register" class="btn-header"><?= isset($t) ? ($t['inscription'] ?? 'Inscription') : 'Inscription' ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>