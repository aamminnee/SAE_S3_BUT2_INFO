<?php
// start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// redirect to order list if command data is missing
if (!isset($commande) || empty($commande)) {
    header("Location: " . ($BASE_URL ?? '/img2brick') . "/views/commande_views.php");
    exit;
}

$mosaic = $mosaic ?? [];

$tr = $t ?? [];

// initialize command model if not already present
if (!isset($commandeModel)) {
    require_once __DIR__ . '/../models/commande_models.php';
    $commandeModel = new CommandeModel();
}

// retrieve status and format order reference
$status = $commandeModel->getCommandeStatusById($commande['id_commande']);
$statusClass = strtolower(str_replace(' ', '-', $status));
$orderCode = 'CMD-' . date('Y', strtotime($commande['date_commande'])) . '-' . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);

// setup image path and fallback
$identifiant = $mosaic['identifiant'] ?? 'default.png';
$imagePath = $BASE_URL . "/control/get_image.php?img=" . urlencode($identifiant);

// determine css filters based on mosaic type
$type = $mosaic['type'] ?? 'default';
$colorLabel = 'Original';
$filterCSS = 'none';

if ($type === 'blue') {
    $filterCSS = 'brightness(1.1) saturate(1.4) hue-rotate(200deg)';
    $colorLabel = 'Blue Palette';
} elseif ($type === 'red') {
    $filterCSS = 'brightness(1.1) saturate(1.4) hue-rotate(-20deg)';
    $colorLabel = 'Red Palette';
} elseif ($type === 'bw') {
    $filterCSS = 'grayscale(100%) contrast(1.1)';
    $colorLabel = 'Black & White';
}

// calculate estimated shipping and delivery dates
$dateCommande = strtotime($commande['date_commande']);
$expeditionDate = date('d/m/Y', strtotime('+2 days', $dateCommande));
$livraisonDate = date('d/m/Y', strtotime('+7 days', $dateCommande));

$supportEmail = $_ENV['SUPPORT_EMAIL'] ?? 'support@img2brick.com';
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tr['order_details'] ?? 'Order Details') ?></title>
    
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/style.css">
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/commande_detail_views.css?v=<?= time() ?>">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="detail-container">
    
    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($tr['order_details'] ?? 'Order details') ?></h1>
        <h2 class="order-ref"><?= htmlspecialchars($orderCode) ?></h2>
    </div>

    <div class="detail-grid">

        <div class="detail-card mosaic-card">
            <h3><?= $tr['mosaic_details'] ?? 'Mosaic Visual' ?></h3>
            
            <div class="img-wrapper">
                <img src="<?= $imagePath ?>" 
                     alt="<?= htmlspecialchars($tr['selected_mosaic'] ?? 'Selected mosaic') ?>" 
                     class="mosaic-img" 
                     style="filter: <?= htmlspecialchars($filterCSS) ?>;">
            </div>
            
            <p class="palette-tag">
                <?= $tr['palette'] ?? 'Palette' ?>: <strong><?= htmlspecialchars($colorLabel) ?></strong>
            </p>

            <div class="download-section">
                <a href="<?= $imagePath ?>" download="mosaic_<?= $identifiant ?>" class="btn-lego btn-lego-green btn-small">
                    <?= $tr['download_image'] ?? 'Download Image' ?>
                </a>
            </div>
        </div>

        <div class="detail-card info-card">
            
            <div class="info-section">
                <h3><?= $tr['status'] ?? 'Status' ?></h3>
                <div class="status-box <?= htmlspecialchars($statusClass) ?>">
                    <?= htmlspecialchars($status) ?>
                </div>
                <p class="date-line">
                    <?= $tr['date'] ?? 'Order Date' ?>: <strong><?= date('d/m/Y', $dateCommande) ?></strong>
                </p>
            </div>

            <hr>

            <?php 
            // display estimated dates only if not delivered
            if (strtolower($status) !== 'livrée' && strtolower($status) !== 'delivered'): ?>
            <div class="info-section">
                <h3><?= $tr['estimated_dates'] ?? 'Estimations' ?></h3>
                <p><strong><?= $tr['estimated_shipping'] ?? 'Shipping' ?>:</strong> <?= htmlspecialchars($expeditionDate) ?></p>
                <p><strong><?= $tr['estimated_delivery'] ?? 'Delivery' ?>:</strong> <?= htmlspecialchars($livraisonDate) ?></p>
            </div>
            <hr>
            <?php endif; ?>

            <div class="info-section address-box">
                <h3><?= $tr['delivery_address'] ?? 'Delivery Address' ?></h3>
                <p>
                    <strong><?= htmlspecialchars($commande['adresse']) ?></strong><br>
                    <?= htmlspecialchars($commande['code_postal']) ?> <?= htmlspecialchars($commande['ville']) ?><br>
                    <?= htmlspecialchars($commande['pays']) ?><br>
                    <?= htmlspecialchars($commande['telephone']) ?>
                </p>
            </div>

        </div>

    </div>

    <div class="footer-actions">
        <a class="btn-lego btn-lego-blue" href="<?=$BASE_URL?>/control/commande_control.php">
            <?= htmlspecialchars($tr['back'] ?? '← Back to Orders') ?>
        </a>
        
        <a class="btn-lego btn-lego-yellow" href="mailto:<?= htmlspecialchars($supportEmail) ?>">
            <?= htmlspecialchars($tr['contact_support'] ?? 'Contact Support') ?>
        </a>
    </div>

</main>

<?php include __DIR__ . '/footer.html'; ?>
</body>
</html>