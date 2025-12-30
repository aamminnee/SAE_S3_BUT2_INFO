<?php
// on sécurise l'accès direct aux variables d'environnement
$BASE_URL = $_ENV["BASE_URL"];
$tr = $t ?? [];
$commandes = $commandes ?? [];
if (!isset($commandeModel)) {
    // cas d'erreur ou d'accès direct non prévu
    $commandeModel = null; 
}
?>

<h1 class="page-title"><?= $tr['orders_title'] ?? 'Mes commandes' ?></h1>

<p class="subtitle"><?= $tr['orders_intro'] ?? 'Retrouvez ici l\'historique de vos créations.' ?></p>

<div class="actions-bar">
    <a class="btn-lego btn-lego-blue" href="<?=$BASE_URL?>/index.php">
        + <?= $tr['new_order'] ?? 'Nouvelle commande' ?>
    </a>
</div>
<?php 
// vérification s'il n'y a aucune commande
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
                    // support objet (fetch_obj) ou array (fetch_assoc)
                    $id_commande = is_object($cmd) ? $cmd->id_commande : $cmd['id_commande'];
                    $date_commande = is_object($cmd) ? $cmd->date_commande : $cmd['date_commande'];
                    $montant = is_object($cmd) ? $cmd->montant : $cmd['montant'];
                    $imgName = is_object($cmd) ? ($cmd->image_identifiant ?? '') : ($cmd['image_identifiant'] ?? '');
                    $type = is_object($cmd) ? ($cmd->image_type ?? 'default') : ($cmd['image_type'] ?? 'default');

                    // calcul du statut via le modèle passé par le contrôleur
                    $status = "Inconnu";
                    if ($commandeModel) {
                        $status = $commandeModel->getCommandeStatusById($id_commande);
                    }
                    $statusClass = strtolower(str_replace(' ', '-', $status));
                    
                    // formatage de la référence unique
                    $orderCode = 'CMD-' . date('Y', strtotime($date_commande)) . '-' . str_pad($id_commande, 5, '0', STR_PAD_LEFT);

                    // filtres css pour la vignette
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
                        <?= date('d/m/Y', strtotime($date_commande)) ?>
                    </td>
                    
                    <td data-label="<?= $tr['status'] ?? 'Statut' ?>">
                        <span class="status-badge <?= htmlspecialchars($statusClass) ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </td>
                    
                    <td data-label="<?= $tr['mosaic'] ?? 'Mosaïque' ?>">
                        <div class="img-frame">
                            <?php if($imgName): ?>
                                <img class="thumb" 
                                        src="<?=$BASE_URL?>/uploads/<?= htmlspecialchars($imgName) ?>" 
                                        style="filter: <?= $filterCSS ?>;" 
                                        alt="mosaic">
                            <?php else: ?>
                                <span>No Image</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <td data-label="<?= $tr['amount'] ?? 'Montant' ?>" class="price">
                        <?= number_format($montant, 2, ',', ' ') ?> €
                    </td>
                    
                    <td data-label="<?= $tr['actions'] ?? 'Actions' ?>">
                        <a class="btn-lego btn-lego-yellow btn-small" href="<?=$BASE_URL?>/commande/detail/<?= (int)$id_commande ?>">
                            <?= $tr['view_details'] ?? 'Voir détails' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
