<?php
// --- 1. LOGIQUE DE SUIVI (Ton ancien code remis) ---
$dateCommande = new DateTime($commande->order_date);
$maintenant = new DateTime();
$interval = $dateCommande->diff($maintenant);
$joursPasses = $interval->days;

$statusLivraison = "";
$progressWidth = "0%";
$classEtat = "";

if ($joursPasses < 3) {
    $statusLivraison = "En préparation / Expédition";
    $progressWidth = "33%";
    $classEtat = "state-shipping"; // Couleur Orange
} elseif ($joursPasses >= 3 && $joursPasses <= 7) {
    $statusLivraison = "En cours de livraison";
    $progressWidth = "66%";
    $classEtat = "state-transit"; // Couleur Bleu
} else {
    $statusLivraison = "Livrée";
    $progressWidth = "100%";
    $classEtat = "state-delivered"; // Couleur Vert
}

// Sécurité multi-articles
if (!isset($items) || empty($items)) {
    $items = [ 
        (object)[
            'id_Mosaic' => $commande->id_Mosaic ?? 0,
            'visuel' => $visuel,
            'size' => 64,
            'style' => 'Classique'
        ] 
    ];
}
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<div class="detail-wrapper">
    <div class="detail-container">
        
        <div class="page-header">
            <a href="<?= $_ENV['BASE_URL'] ?>/commande" class="btn-back">
                <span class="icon">←</span> Retour aux commandes
            </a>
            <h1>Commande <span>#<?= htmlspecialchars($commande->id_Order) ?></span></h1>
            <p class="subtitle">Effectuée le <?= date('d/m/Y à H:i', strtotime($commande->order_date)) ?></p>
        </div>

        <div class="detail-grid">
            
            <div class="main-column">
                
                <div class="card tracking-card">
                    <div class="tracking-header">
                        <h3>Suivi de livraison</h3>
                        <span class="status-text <?= $classEtat ?>"><?= $statusLivraison ?></span>
                    </div>

                    <div class="tracking-visual">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $progressWidth ?>;"></div>
                        </div>
                        
                        <div class="steps">
                            <div class="step <?= $joursPasses >= 0 ? 'active' : '' ?>">
                                <div class="step-icon">📦</div>
                                <span class="step-label">Expédition<br>(< 3j)</span>
                            </div>
                            <div class="step <?= $joursPasses >= 3 ? 'active' : '' ?>">
                                <div class="step-icon">🚚</div>
                                <span class="step-label">En transit<br>(3-7j)</span>
                            </div>
                            <div class="step <?= $joursPasses > 7 ? 'active' : '' ?>">
                                <div class="step-icon">🏠</div>
                                <span class="step-label">Livrée<br>(> 7j)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="items-section">
                    <h3 class="section-title">Contenu du colis</h3>
                    
                    <?php foreach ($items as $item): 
                        $imgSrc = $item->visuel ?? ($visuel ?? '');
                        $mosaicId = $item->id_Mosaic ?? 0;
                    ?>
                        <div class="item-card">
                            <div class="item-visual">
                                <?php if ($imgSrc): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Aperçu">
                                <?php else: ?>
                                    <div class="no-img">Pas d'image</div>
                                <?php endif; ?>
                            </div>

                            <div class="item-content">
                                <div class="item-main">
                                    <h4>Mosaïque Personnalisée</h4>
                                    <div class="badges">
                                        <span class="badge size"><?= $item->size ?? '?' ?>x<?= $item->size ?? '?' ?></span>
                                        <span class="badge style"><?= ucfirst($item->style ?? 'Standard') ?></span>
                                    </div>
                                </div>

                                <div class="item-downloads">
                                    <span>Télécharger :</span>
                                    <div class="dl-buttons">
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadPlan/<?= $mosaicId ?>" target="_blank" class="btn-dl pdf" title="Plan PDF">📄 Plan</a>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadCsv/<?= $mosaicId ?>" class="btn-dl csv" title="Liste Excel">📊 CSV</a>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadImage/<?= $mosaicId ?>" download class="btn-dl img" title="Image HD">🖼️ Image</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($briques)): ?>
                    <div class="card inventory-card">
                        <h3 class="card-title">Contenue de la boîte</h3>
                        <div class="table-scroll">
                            <table class="bricks-table">
                                <thead>
                                    <tr>
                                        <th>Couleur</th>
                                        <th>Taille</th>
                                        <th>Couleur</th>
                                        <th class="text-right">Quantité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($briques as $b): ?>
                                        <tr>
                                            <td><span class="brick-dot" style="background-color: <?= $b['color'] ?>;"></span></td>
                                            <td><?= htmlspecialchars($b['size']) ?></td>
                                            <td class="color-code"><?= strtoupper($b['color']) ?></td>
                                            <td class="text-right"><strong>x<?= $b['count'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="inventory-total">
                            Total : <?= array_sum(array_column($briques, 'count')) ?> briques
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="sidebar-column">
                
                <div class="card info-card">
                    <h3>Livraison</h3>
                    <div class="info-group">
                        <span class="label">Destinataire</span>
                        <p class="val"><?= htmlspecialchars($commande->first_name ?? '') ?> <?= htmlspecialchars($commande->last_name ?? '') ?></p>
                    </div>
                    <div class="info-group">
                        <span class="label">Adresse</span>
                        <p class="val address-val">
                            <?= nl2br(htmlspecialchars($commande->adress ?? 'Non renseignée')) ?>
                        </p>
                    </div>
                </div>

                <div class="card summary-card">
                    <h3>Paiement</h3>
                    <div class="row">
                        <span>Sous-total</span>
                        <span><?= number_format($commande->total_amount, 2) ?> €</span>
                    </div>
                    <div class="row">
                        <span>Livraison</span>
                        <span class="free">4,99 €</span>
                    </div>
                    <div class="divider"></div>
                    <div class="row total">
                        <span>Total</span>
                        <span><?= number_format($commande->total_amount, 2) ?> €</span>
                    </div>

                    <a href="<?= $_ENV['BASE_URL'] ?>/payment/confirmation?id=<?= $commande->id_Order ?>" class="btn-invoice">
                        Télécharger la facture
                    </a>
                </div>

                <div class="help-box">
                    <p>Un problème avec cette commande ?</p>
                    <a href="mailto:support@legofactory.com">Contacter le support</a>
                </div>

            </div>

        </div>
    </div>
</div>