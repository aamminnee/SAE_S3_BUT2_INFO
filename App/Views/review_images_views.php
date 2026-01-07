<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<div class="review-wrapper">
    <div class="review-container">

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="flash-success">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); // On le supprime après affichage ?>
        <?php endif; ?>

        <?php if (isset($error_msg) && $error_msg): ?>
            <div class="alert-box error">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>
        
        <div class="review-top-section">
            
            <div class="header-left">
                <h2><?= $t['review_title'] ?? 'Choisissez votre finition' ?></h2>
                <p class="subtitle">Sélectionnez le style qui correspond le mieux à votre projet.</p>
                
                <div class="lego-scatter">
                    <div class="brick b-red b-2x4"></div>
                    <div class="brick b-blue b-2x2"></div>
                    <div class="brick b-yellow b-2x4"></div>
                    <div class="brick b-green b-2x2"></div>
                    <div class="brick b-red b-2x2"></div>
                </div>
            </div>

            <?php if (isset($image) && !empty($image)): ?>
                <div class="user-image-preview post-it-style">
                    <div class="pin-icon">📍</div>
                    <h3><?= $t['your_original_image'] ?? 'Original' ?></h3>
                    <div class="img-frame">
                        <img src="data:<?= $image['file_type'] ?>;base64,<?= base64_encode($image['file']) ?>" 
                             alt="Original" 
                             class="original-img">
                    </div>
                </div>
            <?php endif; ?>
            
        </div>

        <?php if (isset($image) && !empty($image)): ?>
            <div class="mosaic-options">
                
                <?php 
                $styles = [
                    'cheap'   => ['label' => 'Économique', 'desc' => 'Optimisé pour réduire le coût des pièces.', 'color' => 'var(--lego-green)'],
                    'rupture' => ['label' => 'Classique', 'desc' => 'L\'équilibre parfait entre détail et coût.', 'color' => 'var(--lego-blue)'],
                    'default' => ['label' => 'Avancée', 'desc' => 'Structure renforcée, minimise les lignes droites.', 'color' => 'var(--lego-red)'],
                    'stock'   => ['label' => 'Stock', 'desc' => 'Basé uniquement sur notre inventaire actuel.', 'color' => 'var(--lego-yellow)']
                ];
                ?>

                <?php foreach ($styles as $key => $info): ?>
                    <div class="option-card style-<?= $key ?>">
                        <div class="card-top-bar"></div>

                        <div class="card-header-row">
                            <h3><?= $t['style_' . $key] ?? $info['label'] ?></h3>
                        </div>

                        <div class="preview-box">
                            <?php 
                            if (isset($previews[$key])) {
                                $imgSrc = $previews[$key];
                            } else {
                                $imgSrc = "data:" . $image['file_type'] . ";base64," . base64_encode($image['file']);
                            }
                            ?>
                            <img src="<?= $imgSrc ?>" alt="<?= $info['label'] ?>">
                        </div>

                        <div class="card-stats">
                            <div class="stat-item">
                                <span class="stat-label">Prix estimé</span>
                                <span class="stat-value price">
                                    <?php 
                                        if (isset($prices[$key]) && $prices[$key] > 0) {
                                            echo number_format($prices[$key], 2, ',', ' ') . ' €';
                                        } else {
                                            echo '--,-- €';
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-label">Pièces</span>
                                <span class="stat-value pieces">
                                    <?= (isset($counts[$key]) && $counts[$key] > 0) ? $counts[$key] : '----' ?> p.
                                </span>
                            </div>
                        </div>

                        <p class="desc"><?= $info['desc'] ?></p>
                        
                        <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/cart/add" method="POST" class="card-action-form">
                            <input type="hidden" name="image_id" value="<?= $image['id_Image'] ?>">
                            <button type="submit" name="choice" value="<?= $key ?>" class="btn-select">
                                Ajouter au panier
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>

            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="alert-box warning">
                    <p><?= $t['error_no_image'] ?? "Erreur de chargement." ?></p>
                    <a href="/images" class="btn-retry"><?= $t['btn_retry'] ?? 'Réessayer' ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>