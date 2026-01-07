<?php 
// // sécurité : on s'assure que $cart est un tableau
$items = isset($cart) ? (array)$cart : [];
// // on définit total s'il n'existe pas pour éviter les erreurs
$total = $total ?? 0;
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<div class="payment-wrapper">
    <div class="payment-layout">
        
        <div class="payment-form-container">
            <h2 class="payment-title">Finaliser votre commande</h2>
            
            <form action="<?= $_ENV['BASE_URL'] ?>/payment/process" method="POST" class="lego-form">

                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" required 
                           placeholder="ex: 06 12 34 56 78" 
                           value="07 77 77 77 77">
                </div>

                <div class="form-group">
                    <label for="adress">Adresse complète</label>
                    <input type="text" id="adress" name="adress" required 
                           placeholder="ex: 12 Rue de la Paix, 75000 Paris" 
                           value="12 Rue de la Paix, 75002 Paris">
                </div>

                <div class="form-group">
                    <label for="card_number">Numéro de carte</label>
                    <div class="input-icon">
                        <span class="icon">💳</span>
                        <input type="text" id="card_number" name="card_number" required 
                               placeholder="0000 0000 0000 0000" maxlength="19" 
                               value="4242 4242 4242 4242">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="card_expiry">Expiration</label>
                        <input type="month" id="card_expiry" name="card_expiry" required 
                               value="2025-12">
                    </div>
                    
                    <div class="form-group">
                        <label for="card_cvv">CVV</label>
                        <div class="input-icon">
                            <input type="text" id="card_cvv" name="card_cvv" required 
                                   placeholder="123" maxlength="3" 
                                   value="123">
                        </div>
                    </div>
                </div>

                <p style="font-size: 0.85rem; color: #666; margin-top: 15px; font-style: italic;">
                    Mode de paiement simulé dans le cadre du projet (aucun paiement réel)
                </p>

                <button type="submit" class="btn-pay">
                    Payer <?= number_format($total, 2, ',', ' ') ?> €
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h3>Récapitulatif</h3>
            
            <div class="summary-items">
                <?php foreach ($items as $item): 
                    $item = (array)$item;
                    $imgSrc = "data:" . ($item['image_type'] ?? 'image/png') . ";base64," . $item['image_data'];
                ?>
                    <div class="mosaic-preview">
                        <img src="<?= $imgSrc ?>" alt="Votre Pavage">
                        <div class="preview-info">
                            <p class="preview-title">Pavage LEGO®</p>
                            <p class="preview-details"><?= $item['size'] ?>x<?= $item['size'] ?> - <?= ucfirst($item['style']) ?></p>
                            <p class="preview-price"><?= number_format($item['price'], 2) ?> €</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row total-row">
                <span>Total à payer</span>
                <span class="total-price"><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>
        </div>

    </div>
</div>