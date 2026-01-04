<div class="review-container">

    <?php if (isset($error_msg) && $error_msg): ?>
        <div class="alert alert-danger" style="background: red; color: white; padding: 15px; margin-bottom: 20px;">
            <?= $error_msg ?>
        </div>
    <?php endif; ?>
    
    <h2><?= $t['review_title'] ?? 'Choisissez votre pavage' ?></h2>

    <?php if (isset($image) && !empty($image)): ?>
        
      
        <div class="user-image-preview">
            <h3><?= $t['your_original_image'] ?? 'Image Originale' ?></h3>
            <img src="data:<?= $image['file_type'] ?>;base64,<?= base64_encode($image['file']) ?>" 
                 alt="Original" 
                 class="original-img">
        </div>

        
        <div class="mosaic-options">
            
            <?php 
            // list of available paving strategies
            $styles = [
                'rupture' => ['label' => 'Rupture', 'desc' => 'Minimise les alignements pour une structure plus solide.'],
                'cheap'   => ['label' => 'Économique', 'desc' => 'Optimise le coût en utilisant moins de pièces onéreuses.'],
                'stock'   => ['label' => 'Stock', 'desc' => 'Utilise uniquement les pièces actuellement disponibles en stock.'],
                'default' => ['label' => 'Classique', 'desc' => 'Algorithme de pavage standard équilibré.']
            ];
            ?>

            <?php foreach ($styles as $key => $info): ?>
                <div class="option-card">
                    

                    <h3><?= $t['style_' . $key] ?? $info['label'] ?></h3>

                    <?php if (isset($counts[$key]) && $counts[$key] > 0): ?>
                        <div class="brick-badge">
                            <?= $counts[$key] ?> briques utilisées
                        </div>
                    <?php endif; ?>

                    <div class="preview-box">
                        <?php 
                        // check if preview exists, otherwise fallback to original
                        if (isset($previews[$key])) {
                            $imgSrc = $previews[$key];
                        } else {
                            $imgSrc = "data:" . $image['file_type'] . ";base64," . base64_encode($image['file']);
                        }
                        ?>
                        
                        <img src="<?= $imgSrc ?>" alt="<?= $info['label'] ?>">
                    </div>
                    <p class="desc"><?= $info['desc'] ?></p>
                    
                    <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/reviewImages/save" method="POST">
                        <input type="hidden" name="image_id" value="<?= $image['id_Image'] ?>">
                        <button type="submit" name="choice" value="<?= $key ?>" class="btn-select">
                            <?= $t['btn_choose'] ?? 'Choisir ce style' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>

        </div>

    <?php else: ?>
        <div class="error-message">
            <p><?= $t['error_no_image'] ?? "Une erreur est survenue lors du chargement de l'image." ?></p>
            <a href="/images" class="btn-retry"><?= $t['btn_retry'] ?? 'Réessayer' ?></a>
        </div>
    <?php endif; ?>
</div>