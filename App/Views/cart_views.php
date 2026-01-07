<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<div class="cart-wrapper">
    <div class="cart-container">
        
        <div class="cart-header">
            <h1>Mon Panier</h1>
            <p>
                <?php 
                $count = is_array($items) ? count($items) : 0;
                echo $count > 0 ? "Vous avez $count création(s) en attente." : "Votre panier est vide.";
                ?>
            </p>
        </div>

        <?php if (empty($items)): ?>
            
            <div class="empty-cart-state">
                <div class="empty-illustration">
                    🧱
                </div>
                <h3>C'est bien vide ici !</h3>
                <p>Commencez par créer votre première mosaïque personnalisée.</p>
                <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn-create">
                    <span class="icon">+</span> Créer une Mosaïque
                </a>
            </div>

        <?php else: ?>

            <div class="cart-layout">
                
                <div class="cart-items-list">
                    <?php foreach ($items as $item): 
                        // Gestion si $item est un objet (BDD) ou array (Session)
                        $i_id = is_object($item) ? $item->id_cart : $item['id_unique'];
                        $i_style = is_object($item) ? $item->style : $item['style'];
                        $i_size = is_object($item) ? $item->size : $item['size'];
                        $i_pieces = is_object($item) ? $item->pieces_count : $item['pieces_count'];
                        $i_price = is_object($item) ? $item->price : $item['price'];
                        // Pour l'image, on gère les deux cas
                        $imgData = is_object($item) ? base64_encode($item->file) : $item['image_data'];
                        $imgType = is_object($item) ? $item->file_type : $item['image_type'];
                    ?>
                        <div class="cart-card style-<?= $i_style ?>">
                            <div class="card-visual">
                                <img src="data:<?= $imgType ?>;base64,<?= $imgData ?>" alt="Aperçu Mosaïque">
                            </div>
                            
                            <div class="card-info">
                                <div class="info-top">
                                    <h3>Mosaïque Personnalisée</h3>
                                    <span class="badge badge-<?= $i_style ?>"><?= ucfirst($item['style'] ?? 'Standard') ?></span>

                                </div>
                                
                                <div class="specs-grid">
                                    <div class="spec">
                                        <span class="label">Taille</span>
                                        <span class="val"><?= $i_size ?>x<?= $i_size ?></span>
                                    </div>
                                    <div class="spec">
                                        <span class="label">Pièces</span>
                                        <span class="val"><?= $i_pieces ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-price-action">
                                <div class="price"><?= number_format($i_price, 2, ',', ' ') ?> €</div>
                                
                                <form action="<?= $_ENV['BASE_URL'] ?>/cart/remove" method="POST">
                                    <input type="hidden" name="cart_id" value="<?= $i_id ?>">
                                    <button type="submit" class="btn-remove" title="Supprimer">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Récapitulatif</h3>
                        
                        <div class="summary-row">
                            <span class="label">Sous-total (<?= count($items) ?> articles)</span>
                            <span class="value"><?= number_format($subTotal, 2) ?> €</span>
                        </div>
                        
                        <div class="summary-row highlight">
                            <span class="label">Livraison standard</span>
                            <span class="value">4,99 €</span>
                        </div>

                        <div class="divider"></div>

                        <div class="summary-total">
                            <span>Total à payer</span>
                            <span class="total-amount"><?= number_format($total, 2) ?> €</span>
                        </div>

                        <a href="<?= $_ENV['BASE_URL'] ?>/payment" class="btn-checkout">Payer maintenant</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>