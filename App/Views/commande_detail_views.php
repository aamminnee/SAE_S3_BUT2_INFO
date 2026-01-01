<?php
// // calcul de l'état de la livraison
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
    $classEtat = "state-shipping";
} elseif ($joursPasses >= 3 && $joursPasses <= 7) {
    $statusLivraison = "En cours de livraison";
    $progressWidth = "66%";
    $classEtat = "state-transit";
} else {
    $statusLivraison = "Livrée";
    $progressWidth = "100%";
    $classEtat = "state-delivered";
}
?>

<div class="detail-container">
    <div class="detail-header">
        <a href="<?= $_ENV['BASE_URL'] ?>/commande" class="btn-back">&larr; Retour à mes commandes</a>
        
        <h1>Commande #<?= htmlspecialchars($commande->id_Order) ?></h1>
        
        <p class="order-date-full">
            Effectuée le <strong><?= date('d/m/Y à H:i', strtotime($commande->order_date)) ?></strong>
        </p>
    </div>

    <div class="detail-content">
        <div class="col-visual">
            <div class="image-box">
                <?php if ($visuel): ?>
                    <img src="<?= htmlspecialchars($visuel) ?>" alt="Votre Pavage">
                <?php else: ?>
                    <div class="no-image">Visuel non disponible</div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3>Informations</h3>
                <div class="info-item">
                    <span class="label">Prix Total :</span>
                    <span class="value price"><?= number_format($commande->total_amount, 2) ?> €</span>
                </div>
                
                <div class="info-item address-item">
                    <span class="label">Adresse de livraison :</span>
                    <p class="value address-text">
                        <?= nl2br(htmlspecialchars($commande->adress ?? 'Adresse non disponible')) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-info">
            <div class="tracking-box">
                <h3>Suivi de livraison</h3>
                
                <div class="tracking-status">
                    État : <strong class="<?= $classEtat ?>"><?= $statusLivraison ?></strong>
                </div>

                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $progressWidth ?>;"></div>
                </div>
                
                <div class="steps">
                    <div class="step <?= $joursPasses >= 0 ? 'active' : '' ?>">
                        <span class="icon">📦</span>
                        <span class="text">Expédition<br>(< 3j)</span>
                    </div>
                    <div class="step <?= $joursPasses >= 3 ? 'active' : '' ?>">
                        <span class="icon">🚚</span>
                        <span class="text">En transit<br>(3-7j)</span>
                    </div>
                    <div class="step <?= $joursPasses > 7 ? 'active' : '' ?>">
                        <span class="icon">🏠</span>
                        <span class="text">Livrée<br>(> 7j)</span>
                    </div>
                </div>
            </div>

            <div class="actions-box">
                <a href="<?= $_ENV['BASE_URL'] ?>/payment/confirmation?id=<?= $commande->id_Order ?>" class="btn-action btn-invoice">
                    Télécharger la facture
                </a>
                <br>
                <a href="mailto:<?= htmlspecialchars($_ENV['SUPPORT_EMAIL']) ?>?subject=Support Commande #<?= $commande->id_Order ?>" class="btn-action btn-support">
                    Contacter le support client
                </a>
            </div>
        </div>
    </div>
</div>