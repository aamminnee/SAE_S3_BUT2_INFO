<?php 
$baseUrl = $_ENV['BASE_URL'] ?? ''; 
// on vérifie l'état de connexion (logique amine)
$isLoggedIn = isset($_SESSION['user_id']);
// on récupère le statut du compte (logique amine)
$accountStatus = $_SESSION['status'] ?? 'invalide';
?>

<div class="home-wrapper">

    <div class="hero-section">
        
        <div class="hero-presentation">
            <h1 class="main-title">Turn your photo into a LEGO® mosaic</h1>
            
            <p class="hero-subtitle">Transform your favorite photos into stunning LEGO® mosaics. Upload an image, choose your size and colors, and receive a clear and precise plan to assemble at home.</p>
            
            <div class="example-card">
                <div class="img-container">
                    <span class="img-label">Original</span>
                    <img src="<?= $baseUrl ?>/images/joconde.png" alt="The original Mona Lisa">
                </div>
                <div class="transformation-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </div>
                <div class="img-container">
                    <span class="img-label">Lego Art</span>
                    <img src="<?= $baseUrl ?>/images/joconde_lego.png" alt="The Mona Lisa in LEGO version">
                </div>
            </div>

            <div class="hero-text-block">
                <h4>Create your unique masterpiece</h4>
                <p>Start now and build your memories brick by brick.</p>
            </div>
        </div>

        <div class="hero-action">
            <div class="upload-card">
                <div class="card-header">
                    <h2>New Mosaic</h2>
                    <p>Import your image to start creating</p>
                </div>

                <form action="<?= $baseUrl ?>/images/upload" method="post" enctype="multipart/form-data" id="upload-form">
                    
                    <input type="hidden" id="is-logged-in" value="<?= $isLoggedIn ? '1' : '0' ?>">
                    <input type="hidden" id="account-status" value="<?= htmlspecialchars($accountStatus) ?>">

                    <div id="drop-zone" class="drop-zone">
                        <div class="drop-content">
                            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p class="drop-text">Drop your image here</p>
                            <span class="browse-text">or click to browse</span>
                        </div>
                        <img id="image-preview" src="" alt="Preview" style="display: none;">
                    </div>

                    <input type="file" name="image_input" id="file-upload" style="display: none;" accept="image/png, image/jpeg, image/jpg, image/webp">

                    <div id="action-area" class="action-area hidden" style="display:none;">
                        <button type="button" id="submit-btn" class="btn-primary">
                            <span>Continue</span>
                            <svg style="width:20px; margin-left:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <section class="how-it-works">
        <div class="section-header">
            <h3>How does it work?</h3>
            <p class="intro">It's simple: upload your photo, customize your mosaic, and receive a LEGO® kit ready to assemble.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-img">
                    <img src="<?= $baseUrl ?>/images/televerser.png" alt="Upload"> 
                </div>
                <h5>Upload</h5> <p>Choose your favorite photo</p> 
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-img">
                    <img src="<?= $baseUrl ?>/images/joconde_demi.png" alt="Create"> 
                </div>
                <h5>Create</h5> <p>Customize your LEGO® mosaic</p> 
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-img">
                    <img src="<?= $baseUrl ?>/images/commander.png" alt="Order"> 
                </div>
                <h5>Order</h5> <p>Receive your kit and assemble your artwork</p> 
            </div>
        </div>
        
        <div style="text-align:center; margin-top:50px;">
            <a href="#"><button onclick="document.getElementById('file-upload').click();" class="btn-primary">
                Create yours </button></a>
        </div>
    </section>

</div>

<script src="<?= $baseUrl ?>/JS/drag_drop.js"></script>

<script>
// --- logique d'affichage du bouton sans toucher au js externe ---

// éléments du dom
const actionArea = document.getElementById('action-area');
const imagePreview = document.getElementById('image-preview');
const fileInput = document.getElementById('file-upload');
const dropZone = document.getElementById('drop-zone');

// fonction pour afficher le bouton
function showContinueButton() {
    actionArea.classList.remove('hidden');
    actionArea.style.display = 'block'; 
}

// méthode 1 : detection via mutationobserver (surveille l'image gérée par drag_drop.js)
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'style' || mutation.attributeName === 'src') {
            if (imagePreview.style.display !== 'none') {
                showContinueButton();
            }
        }
    });
});

if (imagePreview) {
    observer.observe(imagePreview, { attributes: true });
}

// méthode 2 : écouteur classique sur l'input file
if (fileInput) {
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            showContinueButton();
        }
    });
}

// méthode 3 : écouteur sur le drop
if (dropZone) {
    dropZone.addEventListener('drop', function() {
        setTimeout(showContinueButton, 100);
    });
}

// --- fin logique d'affichage ---

// --- modification importante ici pour gérer la réponse json ---
document.getElementById('submit-btn').addEventListener('click', function(e) {
    e.preventDefault(); // // on empêche le comportement par défaut

    const isLoggedIn = document.getElementById('is-logged-in').value === '1';
    const accountStatus = document.getElementById('account-status').value;
    
    // vérification de connexion
    if (!isLoggedIn) {
        alert("You must be logged in to continue.");
        window.location.href = "<?= $baseUrl ?>/user/login";
        return;
    } 
    
    // vérification du statut du compte
    if (accountStatus !== 'valide') {
        if(confirm("Your account is not activated. Do you want to activate it now?")) {
            window.location.href = "<?= $baseUrl ?>/compte";
        }
        return;
    }

    // // utilisation de fetch pour envoyer le formulaire et gérer le json
    const form = document.getElementById('upload-form');
    const formData = new FormData(form);

    // // on change le texte du bouton pour indiquer le chargement
    const btn = document.getElementById('submit-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span>Processing...</span>';
    btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json()) // // on convertit la réponse en objet json
    .then(data => {
        if (data.status === 'success') {
            // // redirection manuelle vers l'url fournie par le php
            window.location.href = data.redirect;
        } else {
            // // gestion des erreurs
            alert(data.message || 'An error occurred');
            
            // // si le serveur demande une redirection vers le compte
            if (data.redirect_account) {
                window.location.href = "<?= $baseUrl ?>/compte";
            }
            
            // // on remet le bouton
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur de communication est survenue.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});
</script>