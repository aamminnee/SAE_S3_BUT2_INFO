<?php 
$items = isset($cart) ? (array)$cart : [];
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<div class="payment-wrapper">
    <div class="payment-layout">
        
        <div class="payment-form-container">
            <h2 class="payment-title"><?= $t['payment_title'] ?? 'Finaliser votre commande' ?></h2>
            
            <form action="<?= $_ENV['BASE_URL'] ?>/payment/process" method="POST" class="lego-form">

                <div class="form-group">
                    <label for="phone"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></label>
                    <input type="tel" id="phone" name="phone" required 
                           placeholder="<?= $t['payment_placeholder_phone'] ?? 'ex: 06 12 34 56 78' ?>" 
                           value="07 77 77 77 77">
                </div>

                <div class="form-group">
                    <label for="adress"><?= $t['payment_label_address'] ?? 'Adresse complète' ?></label>
                    <input type="text" id="adress" name="adress" required 
                           placeholder="<?= $t['payment_placeholder_address'] ?? 'ex: 12 Rue de la Paix, 75000 Paris' ?>" 
                           value="12 Rue de la Paix, 75002 Paris">
                </div>

                <div class="form-group">
                    <label for="card_number"><?= $t['payment_label_card'] ?? 'Numéro de carte' ?></label>
                    <div class="input-icon">
                        <span class="icon">💳</span>
                        <input type="text" id="card_number" name="card_number" required 
                               placeholder="0000 0000 0000 0000" maxlength="19" 
                               value="4242 4242 4242 4242">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="card_expiry"><?= $t['payment_label_expiry'] ?? 'Expiration' ?></label>
                        <input type="month" id="card_expiry" name="card_expiry" required 
                               value="2025-12">
                    </div>
                    
                    <div class="form-group">
                        <label for="card_cvv"><?= $t['payment_label_cvv'] ?? 'CVV' ?></label>
                        <div class="input-icon">
                            <input type="text" id="card_cvv" name="card_cvv" required 
                                   placeholder="123" maxlength="3" 
                                   value="123">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-pay">
                    <?= sprintf($t['payment_btn_pay'] ?? 'Payer %s €', number_format($total, 2, ',', ' ')) ?>
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h3><?= $t['payment_summary_title'] ?? 'Récapitulatif' ?></h3>
            
            <div class="summary-items">
                <?php foreach ($items as $item): 
                    $item = (array)$item;
                    $imgSrc = "data:" . ($item['image_type'] ?? 'image/png') . ";base64," . $item['image_data'];
                ?>
                    <div class="mosaic-preview">
                        <img src="<?= $imgSrc ?>" alt="Votre Pavage">
                        <div class="preview-info">
                            <p class="preview-title"><?= $t['payment_product_title'] ?? 'Pavage LEGO®' ?></p>
                            <p class="preview-details"><?= $item['size'] ?>x<?= $item['size'] ?> - <?= ucfirst($item['style']) ?></p>
                            <p class="preview-price"><?= number_format($item['price'], 2) ?> €</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row total-row">
                <span><?= $t['payment_total_label'] ?? 'Total à payer' ?></span>
                <span class="total-price"><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>
        </div>

    </div>
</div>