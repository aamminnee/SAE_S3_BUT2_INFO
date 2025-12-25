<?php
if (session_status() === PHP_SESSION_NONE) {
    // start session if not already started
    session_start();
}

// include configuration and translation models
require_once __DIR__ . '/../control/config.php';
require_once __DIR__ . '/../models/translation_models.php';

// initialize translation model and load language data
$translationModel = new TranslationModel();
$t = $translationModel->getTranslations($_SESSION['lang'] ?? 'en');

// ensure base url is defined
if (!isset($BASE_URL)) {
    $BASE_URL = defined('BASE_URL') ? BASE_URL : ($_ENV['BASE_URL']);
}

// validate user authentication and status
if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
    header("Location: " . $BASE_URL . "/views/login_views.php");
    exit;
}

// verify that an image parameter is provided
if (!isset($_GET['img'])) {
    echo "<p class='error-box'>" . ($t['no_image_selected'] ?? "No image selected.") . "</p>";
    exit;
}
$image = $_GET['img'];
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['crop_preview_title'] ?? 'Crop and Preview' ?></title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

    <link rel="stylesheet" href="<?= $BASE_URL ?>/views/CSS/style.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/views/CSS/crop_images_views_style.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/views/CSS/footer.css">
</head>

<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <main class="crop-container">
        
        <section id="main-panel" class="lego-box">
            <h2><?= $t['crop_your_image'] ?? 'Crop your image' ?></h2>
            
            <div id="image-wrapper">
                <img 
                    id="image" 
                    src="<?= $BASE_URL ?>/control/get_image.php?img=<?= urlencode($image)?>" 
                    data-original-name="<?= htmlspecialchars($image) ?>" 
                    alt="Image to crop">
            </div>

            <button id="cropButton"><?= $t['apply_continue'] ?? 'Apply and Continue' ?></button>
            <div id="message"></div>
        </section>

        <aside id="options-panel" class="lego-box">
            <h3><?= $t['render_options'] ?? 'Settings' ?></h3>

            <div class="option-group">
                <label for="size"><?= $t['board_size'] ?? 'Board size (Studs)' ?></label>
                <select id="size">
                    <option value="32">32 x 32 (Small)</option>
                    <option value="48">48 x 48 (Medium)</option>
                    <option value="64" selected>64 x 64 (Large)</option>
                    <option value="96">96 x 96 (Extra Large)</option>
                </select>
            </div>

            <div class="option-group">
                <label for="aspect"><?= $t['crop_ratio'] ?? 'Aspect Ratio' ?></label>
                <select id="aspect">
                    <option value="1" selected><?= $t['ratio_square'] ?? 'Square (1:1)' ?></option>
                    <option value="1.33333"><?= $t['ratio_43'] ?? '4:3' ?></option>
                    <option value="1.77777"><?= $t['ratio_169'] ?? '16:9' ?></option>
                </select>
            </div>

            <div id="warnings"></div>
        </aside>

    </main>

    <script>
        // pass the image name to javascript safely
        const imageName = "<?= htmlspecialchars($image) ?>";
    </script>
    <script src="<?= $BASE_URL ?>/views/JS/crop_images.js"></script>

    <?php include __DIR__ . '/footer.html'; ?>
</body>
</html>