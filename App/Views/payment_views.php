<div class="payment-container">
    <h2><?= $trans['payment_title'] ?? 'Paiement' ?></h2>

    <?php if (isset($error)): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <form action="/payment/process" method="POST" class="payment-form">
        <div class="form-section">
            <h3><?= $trans['shipping_address'] ?? 'Adresse de livraison' ?></h3>
            
            <div class="form-group">
                <label><?= $trans['label_address'] ?? 'Adresse' ?></label>
                <input type="text" name="address" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?= $trans['label_postal'] ?? 'Code Postal' ?></label>
                    <input type="text" name="postal" required>
                </div>
                <div class="form-group">
                    <label><?= $trans['label_city'] ?? 'Ville' ?></label>
                    <input type="text" name="city" required>
                </div>
            </div>

            <div class="form-group">
                <label><?= $trans['label_country'] ?? 'Pays' ?></label>
                <input type="text" name="country" required>
            </div>
            
            <div class="form-group">
                <label><?= $trans['label_phone'] ?? 'Téléphone' ?></label>
                <input type="tel" name="phone" required>
            </div>
        </div>

        <div class="form-section">
            <h3><?= $trans['payment_info'] ?? 'Informations bancaires' ?></h3>
            
            <div class="form-group">
                <label><?= $trans['label_card_num'] ?? 'Numéro de carte' ?></label>
                <input type="text" name="card_number" maxlength="16" placeholder="0000 0000 0000 0000" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?= $trans['label_expiry'] ?? 'Expiration' ?></label>
                    <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="form-group">
                    <label>CVC</label>
                    <input type="text" name="cvc" maxlength="3" required>
                </div>
            </div>
        </div>

        <div class="total-display">
            <?= $trans['total_to_pay'] ?? 'Total à payer :' ?> <strong>12.99 €</strong>
        </div>

        <button type="submit" class="btn-pay"><?= $trans['btn_pay'] ?? 'Payer' ?></button>
    </form>
</div>