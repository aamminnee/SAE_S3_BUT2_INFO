<div class="orders-container">
    
    <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn-home-back">&larr; Retour à l'accueil</a>
    <br>
    <h1 class="page-title">Mes Commandes</h1>

    <?php if (empty($commandes)): ?>
        <div class="empty-state">
            <p>Vous n'avez pas encore passé de commande.</p>
            <a href="<?= $_ENV['BASE_URL'] ?>/images" class="btn-action">Créer mon premier pavage</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($commandes as $c): ?>
                <div class="order-card">
                    <div class="order-visual">
                        <?php if (!empty($c->visuel)): ?>
                            <img src="<?= htmlspecialchars($c->visuel) ?>" alt="Pavage #<?= $c->id_commande ?>">
                        <?php else: ?>
                            <div class="no-image">Pas d'aperçu</div>
                        <?php endif; ?>
                    </div>

                    <div class="order-info">
                        <div class="info-header">
                            <span class="order-ref">Commande #<?= htmlspecialchars($c->id_commande) ?></span>&nbsp;
                            <span class="order-date"><?= date('d/m/Y', strtotime($c->date_commande)) ?></span>
                        </div>
                        
                        <div class="info-status">
                            <span class="status-badge status-<?= strtolower($c->status) ?>">
                                <?= htmlspecialchars($c->status) ?>
                            </span>
                        </div>

                        <div class="info-price">
                            Prix : <span class="price-val"><?= number_format($c->montant, 2) ?> €</span>
                        </div>
                    </div>

                    <div class="order-actions">
                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/detail/<?= $c->id_commande ?>" class="btn-details">
                            Voir le détail
                        </a>
                        <?php if ($c->status === 'Payée'): ?>
                            <a href="<?= $_ENV['BASE_URL'] ?>/payment/confirmation?id=<?= $c->id_commande ?>" class="btn-invoice">
                                Facture
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>