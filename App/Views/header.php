<?php
// on récupère l'url de base
$baseUrl = $_ENV['BASE_URL'];

// on vérifie si l'utilisateur est connecté et son rôle
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// logique pour le lien du logo
// si admin, on redirige vers la vue admin, sinon vers la page d'accueil (images)
if ($isAdmin) {
    $logoLink = $baseUrl . '/user/admin';
} else {
    $logoLink = $baseUrl . '/index.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lego Mosaic</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/header.css">
    <?php if (isset($css)): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/<?= htmlspecialchars($css) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-container">
            
            <a href="<?= $logoLink ?>" class="logo">
                <img src="<?= $baseUrl ?>/images/logo.png" alt="Logo">
            </a>

            <nav class="nav-menu">
                <?php if ($isAdmin): ?>
                    <a href="<?= $baseUrl ?>/admin/stats" class="nav-link">Statistiques</a>
                    <a href="<?= $baseUrl ?>/admin/supplier" class="nav-link">Fournisseur</a>
                    <a href="<?= $baseUrl ?>/stock">Inventaire</a>
                    <a href="<?= $baseUrl ?>/setting" class="nav-link">Paramètres</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/setting" class="nav-link">Paramètres</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $baseUrl ?>/commande/list" class="nav-link">Mes Commandes</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="user-actions">
                <?php if ($isLoggedIn): ?>
                    <li><a href="<?= $_ENV['BASE_URL'] ?>/compte">Mon compte</a></li>
                    <a href="<?= $baseUrl ?>/user/logout" class="btn-outline">Déconnexion</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/user/login" class="btn-outline">Connexion</a>
                    <a href="<?= $baseUrl ?>/user/register" class="btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </header>