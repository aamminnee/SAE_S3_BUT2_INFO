<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="invoice-controls">
    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/commande" class="btn-back">
        &larr; Retour aux commandes
    </a>
    <button onclick="downloadPDF()" class="btn-download">
        Télécharger la facture (PDF)
    </button>
</div>

<div id="invoice-content" class="invoice-paper">
    
    <div class="paper-header">
        <div class="company-section">
            <img src="<?= $_ENV['BASE_URL'] ?>/Public/img/logo.png" alt="MyBrixStore" style="height: 60px;">
            <div class="company-address">
                <strong>MyBrixStore</strong><br>
                123 Rue des Briques<br>
                75000 Paris, France<br>
                SIRET: 123 456 789 00000
            </div>
        </div>
        
        <div class="invoice-meta">
            <h2 class="invoice-title">FACTURE</h2>
            <table class="meta-table">
                <tr>
                    <th>N° Facture :</th>
                    <td><?= htmlspecialchars($order['invoice_number'] ?? $order['id_Order']) ?></td>
                </tr>
                <tr>
                    <th>Date :</th>
                    <td><?= date('d/m/Y', strtotime($order['issue_date'] ?? $order['order_date'] ?? 'now')) ?></td>
                </tr>
                <tr>
                    <th>Réf. Commande :</th>
                    <td>#<?= $order['id_Order'] ?></td>
                </tr>
            </table>
        </div>
    </div>

    <hr class="separator">

    <div class="client-section">
        <div class="client-box">
            <h3 class="section-title">Facturé à :</h3>
            <div class="client-details">
                <strong><?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></strong><br>
                <?= nl2br(htmlspecialchars($order['adress'] ?? 'Adresse non renseignée')) ?><br>
                Email : <?= htmlspecialchars($order['email'] ?? '') ?><br>
            </div>
        </div>
    </div>

    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc">Description</th>
                    <th class="col-qty">Pièces</th>
                    <th class="col-qty">Quantité</th>
                    <th class="col-price">Prix Unitaire</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($items) && !empty($items)): ?>
                    <?php foreach ($items as $item): 
                        // Gestion hybride objet/tableau
                        $isObj = is_object($item);
                        $price = $isObj ? ($item->price ?? 0) : ($item['price'] ?? 0);
                        $pieces = $isObj ? ($item->pieces ?? 0) : ($item['pieces'] ?? 0);
                        $idMosaic = $isObj ? $item->id_Mosaic : $item['id_Mosaic'];
                    ?>
                    <tr class="item">
                        <td>
                            <strong>Mosaïque Personnalisée</strong>
                            <br>
                            <span style="font-size: 0.8em; color: #666; font-style: italic;">
                                Réf: MS-<?= $idMosaic ?> 
                                (Dont <?= number_format($handlingUnit ?? 4.99, 2) ?> € de frais de préparation inclus)
                            </span>
                        </td>
                        <td class="text-center"><?= $pieces ?> briques</td>
                        <td class="text-right">1</td>
                        <td class="text-right"><?= number_format($price, 2) ?> €</td>
                        <td class="text-right"><?= number_format($price, 2) ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <tr class="item delivery-row" style="background-color: #f9f9f9;">
                    <td colspan="3" class="text-right" style="padding-right: 20px; font-weight: 500; color: #555;">
                        Livraison Standard
                    </td>
                    <td class="text-right"><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                    <td class="text-right"><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="totals-section" style="margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-start;">
        
        <div class="invoice-notes" style="width: 50%; font-size: 0.85rem; color: #666;">
            <?php if(isset($totalHandling) && $totalHandling > 0): ?>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #eee;">
                    <strong>Note informative :</strong><br>
                    Le montant des articles inclut <?= number_format($totalHandling, 2) ?> € 
                    de frais de préparation (tri, ensachage, notice).<br>
                    <em>TVA applicable sur l'ensemble : 20.00%</em>
                </div>
            <?php endif; ?>
        </div>

        <div class="financial-totals" style="width: 45%;">
            <table class="totals-table" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <th style="text-align: left; padding: 5px; color: #555; font-weight: normal;">Total Articles HT</th>
                    <td style="text-align: right; padding: 5px;"><?= number_format($itemsHT ?? 0, 2) ?> €</td>
                </tr>
                
                <tr>
                    <th style="text-align: left; padding: 5px; color: #555; font-weight: normal;">Frais de port HT</th>
                    <td style="text-align: right; padding: 5px;"><?= number_format($deliveryHT ?? 0, 2) ?> €</td>
                </tr>
                
                <tr>
                    <td colspan="2"><hr style="border: 0; border-top: 1px solid #ddd; margin: 5px 0;"></td>
                </tr>

                <tr>
                    <th style="text-align: left; padding: 5px;">Total HT Net</th>
                    <td style="text-align: right; padding: 5px; font-weight: bold;"><?= number_format($totalHT ?? 0, 2) ?> €</td>
                </tr>

                <tr>
                    <th style="text-align: left; padding: 5px; color: #555; font-weight: normal;">TVA (20%)</th>
                    <td style="text-align: right; padding: 5px;"><?= number_format($totalTVA ?? 0, 2) ?> €</td>
                </tr>

                <tr class="grand-total" style="background-color: #f4f4f4; border-top: 2px solid #333;">
                    <th style="text-align: left; padding: 10px; font-size: 1.1em; color: #000;">Net à payer (TTC)</th>
                    <td style="text-align: right; padding: 10px; font-size: 1.1em; font-weight: bold; color: #D92328;">
                        <?= number_format($totalTTC ?? 0, 2) ?> €
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="paper-footer">
        <p>Merci pour votre confiance !</p>
        <p class="small">MyBrixStore - Capital de 10 000 €</p>
    </div>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('invoice-content');
    const opt = {
        margin:       10,
        filename:     'Facture_<?= $order['invoice_number'] ?? 'Lego' ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>