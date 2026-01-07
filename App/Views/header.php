<?php
// on calcule l'url de base si $_env n'est pas défini
// cela évite les erreurs de chemin pour le css et les images
$baseUrl = $_ENV['BASE_URL'] ?? dirname($_SERVER['SCRIPT_NAME']);

// on s'assure que l'url ne finit pas par un slash pour éviter les doubles slashs
$baseUrl = rtrim($baseUrl, '/\\');

// correction : on remonte d'un cran si on est dans un sous-dossier
if (basename($baseUrl) == 'Public') {
    // c'est correct
} else {
    // fallback simple si nécessaire
}

// on vérifie si l'utilisateur est connecté et son rôle
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// logique pour le lien du logo
// si admin, on redirige vers la vue admin, sinon vers la page d'accueil
if ($isAdmin) {
    $logoLink = $baseUrl . '/user/admin';
} else {
    $logoLink = $baseUrl . '/index.php';
}

// logique panier
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = count($_SESSION['cart']);
}
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/CSS/header.css">

<header class="header">
    <div class="header-container">
        
        <a href="<?= $logoLink ?>" class="logo">
            <img src="<?= $baseUrl ?>/images/logo.png" alt="Logo">
        </a>

        <nav class="nav-menu main-nav">
            <?php if ($isAdmin): ?>
                <a href="<?= $baseUrl ?>/admin/stats" class="nav-link">Statistiques</a>
                <a href="<?= $baseUrl ?>/admin/supplier" class="nav-link">Fournisseur</a>
                <a href="<?= $baseUrl ?>/stock" class="nav-link">Inventaire</a>
                <a href="<?= $baseUrl ?>/setting" class="nav-link">Paramètres</a>
                <a href="<?= $baseUrl ?>/user/logout" class="btn-header-base btn-outline">Déconnexion</a>

            <?php else: ?>
                <?php if ($isLoggedIn): ?>
                    <div class="nav-item cart-btn-wrapper">
                        <a href="<?= $baseUrl ?>/cart" class="cart-btn" id="cart-container">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            <span id="cart-count" class="cart-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>">
                                <?= $cartCount ?>
                            </span>
                        </a>
                    </div>

                    <div class="profile-menu">
                        <div class="profile-trigger">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Mon Compte') ?></span>
                            <img src="<?= $baseUrl ?>/Public/images/default_avatar.png" alt="Avatar" class="avatar-mini" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'User') ?>&background=random'">
                        </div>
                        
                        <ul class="dropdown">
                            <li><a href="<?= $baseUrl ?>/compte">Mon compte</a></li>
                            <li><a href="<?= $baseUrl ?>/setting">Paramètres</a></li>
                            <li><a href="<?= $baseUrl ?>/commande">Mes Commandes</a></li>
                            <li class="separator"></li>
                            <li><a href="<?= $baseUrl ?>/user/logout" class="logout-btn">Déconnexion</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <a href="<?= $baseUrl ?>/user/login" class="btn-header-base btn-outline">Connexion</a>
                    <a href="<?= $baseUrl ?>/user/register" class="btn-header-base btn-primary">Inscription</a>
                <?php endif; ?>

            <?php endif; ?>
        </nav>
    </div>
</header>