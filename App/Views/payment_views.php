<div class="payment-container">
    <h2 class="payment-title">Finaliser votre commande</h2>
    
    <div class="order-summary">
        <h3>Récapitulatif</h3>
        
        <?php if(isset($mosaicImage) && $mosaicImage): ?>
            <div class="mosaic-preview" style="text-align:center; margin-bottom:15px; background: #fff; padding: 10px; border-radius: 4px;">
                <img src="<?= $mosaicImage ?>" alt="Votre Pavage" style="max-width:100%; max-height: 250px; border:1px solid #ddd; border-radius:4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Aperçu du résultat final</p>
            </div>
        <?php endif; ?>

        <div class="summary-row">
            <span>Pavage LEGO personnalisé</span>
            <span class="total-price">Total : <?= number_format($price, 2) ?> €</span>
        </div>
    </div>

    <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/payment/process" method="POST" class="lego-form">

        <?php // champ téléphone avec valeur par défaut ?>
        <div class="form-group">
            <label for="phone">Téléphone</label>
            <input type="tel" id="phone" name="phone" required placeholder="ex: 06 12 34 56 78" value="07 77 77 77 77">
        </div>

        <?php // champ adresse avec valeur par défaut ?>
        <div class="form-group">
            <label for="address">Adresse de facturation</label>
            <input type="text" id="address" name="address" required placeholder="ex: 12 Rue de la Paix, 75000 Paris" value="12 Rue de la Paix, 75000 Paris">
        </div>
        
        <div class="form-group">
            <label for="card_holder">Nom sur la carte</label>
            <input type="text" id="card_holder" name="card_holder" required placeholder="ex: Jean Dupont" value="Jean Dupont">
        </div>

        <div class="form-group">
            <label for="card_number">Numéro de carte</label>
            <input type="text" id="card_number" name="card_number" required placeholder="0000 0000 0000 0000" maxlength="19" value="4242 4242 4242 4242">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="card_expiry">Expiration</label>
                <input type="month" id="card_expiry" name="card_expiry" required value="2025-12">
            </div>
            
            <div class="form-group">
                <label for="card_cvv">CVV</label>
                <input type="text" id="card_cvv" name="card_cvv" required placeholder="123" maxlength="3" value="123">
            </div>
        </div>

        <button type="submit" class="btn-pay">
            Payer <?= number_format($price, 2) ?> €
        </button>
    </form>
</div>