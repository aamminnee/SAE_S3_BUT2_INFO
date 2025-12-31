<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="invoice-controls">
    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/index.php" class="btn-back">
        &larr; Retour au menu
    </a>
    <button onclick="downloadPDF()" class="btn-download">
        Télécharger la facture (PDF)
    </button>
</div>

<div id="invoice-content" class="invoice-paper">
    
    <div class="paper-header">
        <div class="company-section">
            <h1 class="logo-text">LEGO <span class="highlight">FACTORY</span></h1>
            <div class="company-address">
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
                    <td><?= htmlspecialchars($order['invoice_number'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Date :</th>
                    <td><?= date('d/m/Y', strtotime($order['issue_date'] ?? 'now')) ?></td>
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
                <br>
                Email : <?= htmlspecialchars($order['email'] ?? '') ?><br>
                Adress : <?= htmlspecialchars($order['adress'] ?? '') ?><br>
            </div>
        </div>
    </div>

    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc">Description</th>
                    <th class="col-qty">Quantité</th>
                    <th class="col-price">Prix Unitaire</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pavage LEGO Personnalisé</td>
                    <td class="text-center">1</td>
                    <td class="text-right"><?= number_format($order['total_amount'], 2) ?> €</td>
                    <td class="text-right bold"><?= number_format($order['total_amount'], 2) ?> €</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <th>Total HT</th>
                <td><?= number_format($order['total_amount'] / 1.2, 2) ?> €</td>
            </tr>
            <tr>
                <th>TVA (20%)</th>
                <td><?= number_format($order['total_amount'] - ($order['total_amount'] / 1.2), 2) ?> €</td>
            </tr>
            <tr class="grand-total">
                <th>Total TTC</th>
                <td><?= number_format($order['total_amount'], 2) ?> €</td>
            </tr>
        </table>
    </div>

    <div class="paper-footer">
        <p>Merci pour votre confiance !</p>
        <p class="small">Lego Factory SAS - Capital de 10 000 €</p>
    </div>
</div>


<script>
function downloadPDF() {
    // On sélectionne uniquement la feuille blanche
    const element = document.getElementById('invoice-content');
    
    // Configuration pour un rendu A4 propre
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