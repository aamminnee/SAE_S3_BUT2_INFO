<?php
// start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// include required models and configuration files
require_once __DIR__ . '/../control/config.php';
require_once __DIR__ . '/../models/translation_models.php';
require_once __DIR__ . '/../models/images_models.php';

// redirect to home if no order id exists in session
if (empty($_SESSION['order_id'])) {
    header("Location: " . $BASE_URL . "/views/images_views.php");
    exit;
}

// initialize translation model and load language strings
$translationModel = new TranslationModel();
$t = $translationModel->getTranslations($_SESSION['lang'] ?? 'en');

// retrieve image details for the last ordered item
$images_model = new ImagesModel();
$lastImage = $_SESSION['last_image'];
$imageData = $images_model->getImageType($lastImage);
$type = $imageData['type'];

// determine css filter based on image type
$filterCSS = match($type) {
    'blue' => 'contrast(1.1) hue-rotate(180deg) saturate(1.2)',
    'red'  => 'contrast(1.1) hue-rotate(340deg) saturate(1.5)',
    'bw'   => 'grayscale(100%) contrast(1.1)',
    default => 'none'
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['order_confirmation'] ?? 'Order Confirmation' ?></title>
    
    <link rel="stylesheet" href="<?= $BASE_URL ?>/views/CSS/style.css">
    <link rel="stylesheet" href="CSS/confirm_views.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="confirm-container">
    
    <h1 class="page-title"><?= $t['thank_you_order'] ?? 'Thank you for your order!' ?></h1>
    
    <p class="subtitle">
        <?= $t['order_processing'] ?? 'Your mosaic is being prepared.<br>You will receive a confirmation email shortly.' ?>
    </p>

    <div class="lego-box">
        
        <div class="summary-content">
            <div class="img-wrapper">
                <img src="<?= $BASE_URL ?>/control/get_image.php?img=<?= urlencode($lastImage) ?>" 
                     style="filter: <?= htmlspecialchars($filterCSS, ENT_QUOTES, 'UTF-8') ?>;"
                     alt="Mosaic Preview">
            </div>
            
            <div class="details">
                <div class="detail-row">
                    <span class="label"><?= $t['order_number'] ?? 'Order Number:' ?></span>
                    <span class="value ref">#<?= htmlspecialchars($_SESSION['order_id']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label"><?= $t['amount_paid'] ?? 'Amount Paid:' ?></span>
                    <span class="value price"><?= number_format($_SESSION['price'] ?? 12.99, 2, ',', ' ') ?> €</span>
                </div>

                <hr>

                <div class="address-block">
                    <strong><?= $t['shipping_address'] ?? 'Shipping Address' ?></strong>
                    <p>
                        <?= htmlspecialchars($_SESSION['address'] ?? '') ?><br>
                        <?= htmlspecialchars(($_SESSION['postal'] ?? '') . ' ' . ($_SESSION['city'] ?? '')) ?><br>
                        <?= htmlspecialchars($_SESSION['country'] ?? '') ?>
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    <form action="<?= $BASE_URL ?>/views/images_views.php" method="POST">
        <button type="submit" class="return-btn"><?= $t['return_home'] ?? 'Return to Home' ?></button>
    </form>

</main>

<?php include __DIR__ . '/footer.html'; ?>
</body>
</html>