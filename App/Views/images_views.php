<form action="<?= $baseUrl ?>/images/upload" method="post" enctype="multipart/form-data">
    <div id="drop-zone">
        <p>Glissez une image ici, collez-la (Ctrl+V) ou cliquez pour sélectionner.</p>
    </div>

    <input type="file" name="image" id="file-upload" style="display: none;" accept="image/*">

    <button type="submit" class="btn">Envoyer</button>
</form>

<script src="<?= $baseUrl ?>/JS/drag&drop.js"></script>
<script>
    const UPLOAD_URL = '/images/upload';
    const CROP_URL = '/cropImages/process';
</script>