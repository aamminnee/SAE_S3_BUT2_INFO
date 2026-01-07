<?php 
$baseUrl = $_ENV['BASE_URL'] ?? ''; 
$isLoggedIn = isset($_SESSION['user_id']); // // on vérifie l'état de connexion
$accountStatus = $_SESSION['status'] ?? 'invalide'; // // on récupère le statut du compte
?>

<div class="main-container">
    <div class="upload-card">
        
        <div class="card-header">
            <h2>Nouvelle Mosaïque</h2>
            <p>Importez votre image pour commencer la création</p>
        </div>

        <form action="<?= $baseUrl ?>/images/upload" method="post" enctype="multipart/form-data" id="upload-form">
            
            <input type="hidden" id="is-logged-in" value="<?= $isLoggedIn ? '1' : '0' ?>">
            <input type="hidden" id="account-status" value="<?= htmlspecialchars($accountStatus) ?>">

            <div id="drop-zone" class="drop-zone">
                <div class="drop-content">
                    <svg class="upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="drop-text">Glissez votre image ici</p>
                    <span class="browse-text">ou cliquez pour parcourir</span>
                </div>
                <img id="image-preview" src="" alt="Aperçu" style="display: none;">
            </div>

            <input type="file" name="image_input" id="file-upload" style="display: none;" accept="image/png, image/jpeg, image/jpg, image/webp">

            <div id="action-area" class="action-area hidden">
                <button type="button" id="submit-btn" class="btn-primary">
                    <span>Continuer</span>
                    <svg style="width:20px; margin-left:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>

        </form>
    </div>
</div>

<script src="<?= $baseUrl ?>/JS/drag_drop.js"></script>

<script>
document.getElementById('submit-btn').addEventListener('click', function(e) {
    const isLoggedIn = document.getElementById('is-logged-in').value === '1';
    const accountStatus = document.getElementById('account-status').value;
    
    if (!isLoggedIn) {
        e.preventDefault();
        alert("Vous devez être connecté pour continuer.");
        window.location.href = "<?= $baseUrl ?>/user/login";
        return;
    } 
    
    // // vérification si le compte est activé
    if (accountStatus !== 'valide') {
        e.preventDefault();
        if(confirm("Votre compte n'est pas activé. Voulez-vous l'activer maintenant ?")) {
            window.location.href = "<?= $baseUrl ?>/compte";
        }
        return;
    }

    // // si connecté et activé, on soumet le formulaire
    document.getElementById('upload-form').submit();
});
</script>