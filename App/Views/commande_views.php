<?php
// check session status and start if necessary
if (session_status() === PHP_SESSION_NONE) session_start();

// load configuration settings
require_once __DIR__ . '/../control/config.php';
$BASE_URL = $_ENV["BASE_URL"];

// include command model if not already instantiated
require_once __DIR__ . '/../models/commande_models.php';
if (!isset($commandeModel)) {
    $commandeModel = new CommandeModel();
}

// set default values for translations and orders
$tr = $t ?? [];
$commandes = $commandes ?? [];
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?= $tr['orders_title'] ?? 'Mes commandes' ?></title>
    
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/style.css">
    <link rel="stylesheet" href="<?=$BASE_URL?>/views/CSS/commande_views.css?v=<?= time() ?>">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="orders-container">
    
    <h1 class="page-title"><?= $tr['orders_title'] ?? 'Mes commandes' ?></h1>
    
    <p class="subtitle"><?= $tr['orders_intro'] ?? 'Retrouvez ici l\'historique de vos créations.' ?></p>

    <div class="actions-bar">
        <a class="btn-lego btn-lego-blue" href="<?=$BASE_URL?>/views/images_views.php">
            + <?= $tr['new_order'] ?? 'Nouvelle commande' ?>
        </a>
    </div>

    <?php 
    // check if there are no orders to display
    if (empty($commandes)): ?>
        <div class="empty-state">
            <p><?= $tr['no_orders'] ?? 'Aucune commande trouvée.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="lego-table">
                <thead>
                    <tr>
                        <th><?= $tr['order_number'] ?? 'Numéro' ?></th>
                        <th><?= $tr['date'] ?? 'Date' ?></th>
                        <th><?= $tr['status'] ?? 'Statut' ?></th>
                        <th><?= $tr['mosaic'] ?? 'Mosaïque' ?></th>
                        <th><?= $tr['amount'] ?? 'Montant' ?></th>
                        <th><?= $tr['actions'] ?? 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $cmd): 
                        // calculate order status dynamically
                        $status = $commandeModel->getCommandeStatusById($cmd['id_commande']);
                        $statusClass = strtolower(str_replace(' ', '-', $status));
                        
                        // format the unique order reference code
                        $orderCode = 'CMD-' . date('Y', strtotime($cmd['date_commande'])) . '-' . str_pad($cmd['id_commande'], 5, '0', STR_PAD_LEFT);

                        // retrieve image identifier and type
                        $imgName = $cmd['image_identifiant'] ?? ''; 
                        $type = $cmd['image_type'] ?? 'default';

                        // apply css filters based on the selected palette
                        $filterCSS = match($type) {
                            'blue' => 'brightness(1.1) saturate(1.4) hue-rotate(200deg)',
                            'red'  => 'brightness(1.1) saturate(1.4) hue-rotate(-20deg)',
                            'bw'   => 'grayscale(100%) contrast(1.1)',
                            default => 'none'
                        };
                    ?>
                    <tr>
                        <td data-label="<?= $tr['order_number'] ?? 'Numéro' ?>">
                            <strong><?= htmlspecialchars($orderCode) ?></strong>
                        </td>
                        
                        <td data-label="<?= $tr['date'] ?? 'Date' ?>">
                            <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?>
                        </td>
                        
                        <td data-label="<?= $tr['status'] ?? 'Statut' ?>">
                            <span class="status-badge <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                        
                        <td data-label="<?= $tr['mosaic'] ?? 'Mosaïque' ?>">
                            <div class="img-frame">
                                <img class="thumb" 
                                     src="<?=$BASE_URL?>/control/get_image.php?img=<?= urlencode($imgName) ?>" 
                                     style="filter: <?= $filterCSS ?>;" 
                                     alt="mosaic">
                            </div>
                        </td>
                        
                        <td data-label="<?= $tr['amount'] ?? 'Montant' ?>" class="price">
                            <?= number_format($cmd['montant'], 2, ',', ' ') ?> €
                        </td>
                        
                        <td data-label="<?= $tr['actions'] ?? 'Actions' ?>">
                            <a class="btn-lego btn-lego-yellow btn-small" href="<?=$BASE_URL?>/control/commande_detail_control.php?id=<?= (int)$cmd['id_commande'] ?>">
                                <?= $tr['view_details'] ?? 'Voir détails' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/footer.html'; ?>
</body>
</html>