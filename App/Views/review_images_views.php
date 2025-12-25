<div class="review-container">
    <h2><?= $trans['review_title'] ?? 'Choisissez votre style' ?></h2>

    <?php if (isset($_SESSION['last_image'])): ?>
        <div class="mosaic-options">
            
            <div class="option-card">
                <h3><?= $trans['style_blue'] ?? 'Bleu' ?></h3>
                <img src="/images/examples/blue_preview.png" alt="Blue Style">
                <form action="/images/mosaic" method="POST">
                    <button type="submit" name="choice" value="blue" class="btn-select">
                        <?= $trans['btn_choose'] ?? 'Choisir ce style' ?>
                    </button>
                </form>
            </div>

            <div class="option-card">
                <h3><?= $trans['style_red'] ?? 'Rouge' ?></h3>
                <img src="/images/examples/red_preview.png" alt="Red Style">
                <form action="/images/mosaic" method="POST">
                    <button type="submit" name="choice" value="red" class="btn-select">
                        <?= $trans['btn_choose'] ?? 'Choisir ce style' ?>
                    </button>
                </form>
            </div>

            <div class="option-card">
                <h3><?= $trans['style_bw'] ?? 'Noir & Blanc' ?></h3>
                <img src="/images/examples/bw_preview.png" alt="BW Style">
                <form action="/images/mosaic" method="POST">
                    <button type="submit" name="choice" value="bw" class="btn-select">
                        <?= $trans['btn_choose'] ?? 'Choisir ce style' ?>
                    </button>
                </form>
            </div>

        </div>
    <?php else: ?>
        <p><?= $trans['error_no_image'] ?? "Aucune image n'a été chargée. Veuillez recommencer." ?></p>
        <a href="/images" class="btn"><?= $trans['btn_retry'] ?? 'Recommencer' ?></a>
    <?php endif; ?>
</div>