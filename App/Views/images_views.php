<div class="upload-container">
    <h1><?= $trans['home_title'] ?? 'Transformez vos images en briques !' ?></h1>
    
    <div class="drop-zone" id="drop-zone">
        <p><?= $trans['drop_instruction'] ?? 'Glissez votre image ici ou cliquez pour sélectionner' ?></p>
        <input type="file" id="file-input" hidden accept="image/*">
    </div>

    <div id="preview-container" style="display:none;">
        <img id="preview-image" src="" alt="Aperçu">
        <button id="crop-btn" class="btn"><?= $trans['btn_crop_validate'] ?? 'Recadrer & Valider' ?></button>
    </div>
</div>

<script src="/js/drag&drop.js"></script>
<script>
    const UPLOAD_URL = '/images/upload';
    const CROP_URL = '/cropImages/process';
</script>