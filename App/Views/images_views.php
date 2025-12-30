<?php $baseUrl = $_ENV['BASE_URL'] ?? ''; ?>

<form action="<?= $baseUrl ?>/images/upload" method="post" enctype="multipart/form-data">
    <div id="drop-zone">
        <p>Glissez une image ici, collez-la (Ctrl+V) ou cliquez pour sélectionner.</p>
    </div>

    <input type="file" name="image_input" id="file-upload" style="display: none;" accept="image/*">

    <button type="submit" class="btn">Envoyer</button>
</form>

<script src="<?= $baseUrl ?>/JS/drag_drop.js?v=<?= time() ?>"></script>

<script>
    // définition des url pour l'usage dans les scripts externes si besoin
    const UPLOAD_URL = '<?= $baseUrl ?>/images/upload';
    const CROP_URL = '<?= $baseUrl ?>/cropImages';
</script>
