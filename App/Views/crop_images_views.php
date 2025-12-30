<?php
$baseUrl = $_ENV['BASE_URL'] ?? '';
$tr = $t ?? [];
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
</head>
<h1>Recadrer votre image</h1>

<div class="img-container">
    <?php if (isset($image) && !empty($image->id_Image)): ?>
        <img id="image-to-crop" 
                src="<?= $baseUrl ?>/images/view/<?= $image->id_Image ?>" 
                alt="<?= htmlspecialchars($image->filename ?? 'Image') ?>">
    <?php else: ?>
        <p>Erreur : Aucune image chargée. <a href="<?= $baseUrl ?>/index.php">Réessayer</a></p>
    <?php endif; ?>
</div>

<aside id="options-panel" class="lego-box">
    <h3><?= $t['render_options'] ?? 'Settings' ?></h3>

    <div class="option-group">
        <label for="size"><?= $t['board_size'] ?? 'Board size (Studs)' ?></label>
        <select id="size">
            <option value="32">32 x 32 (Small)</option>
            <option value="48">48 x 48 (Medium)</option>
            <option value="64" selected>64 x 64 (Large)</option>
            <option value="96">96 x 96 (Extra Large)</option>
            <option value="128">128 x 128 (Jumbo)</option>
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
</aside>

<div style="text-align:center; margin-top:20px;">
    <button id="btn-crop" class="btn btn-lego">Valider le recadrage</button>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="<?= $baseUrl ?>/JS/crop_images.js"></script>