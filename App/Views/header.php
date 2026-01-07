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
            <img src="<?= $baseUrl ?>/img/logo.png" alt="MyBrixStore Logo">
        </a>

        <nav class="main-nav">
            <ul>
                <?php if ($isLoggedIn): ?>
                    
                    <li class="nav-item">
                        <a href="<?= $baseUrl ?>/cart" class="btn-header cart-btn" id="cart-container">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            
                            <span id="cart-count" class="cart-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>">
                                <?= $cartCount ?>
                            </span>
                        </a>
                    </li>

                    <li class="profile-menu">
                        <div class="profile-trigger">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Mon Compte') ?></span>
                            <img src="<?= $baseUrl ?>/Public/img/default_avatar.png" alt="Avatar" class="avatar-mini" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'User') ?>&background=random'">
                        </div>
                        
                        <ul class="dropdown">
                            <li><a href="<?= $baseUrl ?>/setting"><?= isset($t) ? ($t['mon_profil'] ?? 'Paramètres') : 'Paramètres' ?></a></li>
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