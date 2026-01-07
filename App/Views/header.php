<?php
// Vérification de connexion
$isLoggedIn = isset($_SESSION['user_id']);
$baseUrl = $_ENV['BASE_URL'] ?? '';

// --- LOGIQUE PANIER : Compter les articles ---
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = count($_SESSION['cart']);
}
?>

<header>
    <div class="header-container">
        <a href="<?= $baseUrl ?>/index.php" class="logo">
            <img src="<?= $baseUrl ?>/img/logo.png" alt="Img2Brick Logo">
        </a>

        <nav class="main-nav">
            <ul>
                <?php if ($isLoggedIn): ?>
                    <li class="profile-menu">
                        <div class="profile-trigger">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Mon Compte') ?></span>
                            <img src="<?= $baseUrl ?>/img/default_avatar.png" alt="Avatar" class="avatar-mini" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'User') ?>&background=random'">
                        </div>
                        <ul class="dropdown">
                            <li><a href="<?= $baseUrl ?>/setting"><?= isset($t) ? ($t['mon_profil'] ?? 'Paramètres') : 'Paramètres' ?></a></li>
                                                        <li><a href="<?= $baseUrl ?>/cart"><?= isset($t) ? ($t['mon_panier'] ?? 'Mon Panier') : 'Mon Panier' ?></a></li>
                            <li><a href="<?= $baseUrl ?>/commande"><?= isset($t) ? ($t['mes_commandes'] ?? 'Mes Commandes') : 'Mes Commandes' ?></a></li>
                            <li class="separator"></li>
                            <li><a href="<?= $baseUrl ?>/user/logout" class="logout-btn"><?= isset($t) ? ($t['deconnexion'] ?? 'Déconnexion') : 'Déconnexion' ?></a></li>
                        </ul>
                    </li>

                <?php else: ?>
                    <li><a href="<?= $baseUrl ?>/user/login" class="nav-link"><?= isset($t) ? ($t['connexion'] ?? 'Connexion') : 'Connexion' ?></a></li>
                    <li><a href="<?= $baseUrl ?>/user/register" class="btn-header"><?= isset($t) ? ($t['inscription'] ?? 'Inscription') : 'Inscription' ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>