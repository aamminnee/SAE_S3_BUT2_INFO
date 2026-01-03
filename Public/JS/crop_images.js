// // éléments du dom
const image = document.getElementById('image-to-crop');
const cropButton = document.getElementById('btn-crop');
const aspectSelect = document.getElementById('aspect');
const sizeSelect = document.getElementById('size');

// // création dynamique des conteneurs de message s'ils sont absents
let message = document.getElementById('message');
let warnings = document.getElementById('warnings');

// // fonction utilitaire pour créer les div de notification
if (!message) {
    message = document.createElement('div');
    message.id = 'message';
    message.style.textAlign = 'center';
    message.style.marginTop = '10px';
    message.style.fontWeight = 'bold';
    cropButton.parentElement.insertBefore(message, cropButton);
}

if (!warnings) {
    warnings = document.createElement('div');
    warnings.id = 'warnings';
    warnings.style.color = '#e67e22'; // // orange
    warnings.style.textAlign = 'center';
    warnings.style.marginBottom = '10px';
    message.parentElement.insertBefore(warnings, message);
}

// // initialisation de cropper.js
let cropper = new Cropper(image, {
    aspectRatio: 1, // // carré par défaut
    viewMode: 1,
    background: false,
    autoCropArea: 1,
    ready() {
        // // applique le ratio sélectionné au chargement si différent
        const initialAspect = parseFloat(aspectSelect.value);
        this.cropper.setAspectRatio(initialAspect);
    }
});

// // avertissement si l'image est très grande
image.addEventListener('load', () => {
    if (image.naturalWidth > 3000 || image.naturalHeight > 3000) {
        warnings.textContent = "L'image est très grande, les performances peuvent être réduites.";
    }
});

// // changement dynamique du ratio de recadrage via l'aside
aspectSelect.addEventListener('change', () => {
    const value = parseFloat(aspectSelect.value);
    cropper.setAspectRatio(value);
});

// // gestion du clic sur le bouton de validation
cropButton.addEventListener('click', () => {
    // // récupération des données de recadrage
    const cropData = cropper.getData(true);
    const cropWidth = Math.round(cropData.width);
    const cropHeight = Math.round(cropData.height);

    const minSize = 50; 

    // // vérification de la taille minimale
    if (cropWidth < minSize || cropHeight < minSize) {
        message.textContent = "Erreur : la zone sélectionnée est trop petite.";
        message.style.color = "#E3000B"; // // rouge lego
        return; 
    }

    message.textContent = "Traitement en cours...";
    message.style.color = "#333"; 
    warnings.textContent = "";
    cropButton.disabled = true;

    // // récupération de la taille de plateau choisie dans l'aside
    const boardSize = parseInt(sizeSelect.value);

    // // génération du canvas recadré
    const canvasData = cropper.getCroppedCanvas({
        width: cropWidth,
        height: cropHeight
    });

    if (!canvasData) {
        message.textContent = "Erreur lors de la génération de l'image.";
        cropButton.disabled = false;
        return;
    }

    // // conversion en blob et envoi via ajax
    canvasData.toBlob(blob => {
        const formData = new FormData();
        formData.append('cropped_image', blob, 'cropped.png');
        
        // // récupération du nom original
        const originalName = image.getAttribute('alt') || 'image';
        formData.append('original_name', originalName);
        
        // // récupération de l'id de l'image (ajout important pour éviter les erreurs)
        const imageId = image.getAttribute('data-id');
        if (imageId) {
            formData.append('image_id', imageId);
        }
        
        // // envoi de la taille choisie
        formData.append('size', boardSize); 

        // // envoi vers la méthode process du contrôleur
        fetch('cropImages/process', { 
            method: 'POST',
            body: formData
        })
        .then(res => {
            // // vérification si la réponse est ok avant de parser le json
            if (!res.ok) {
                throw new Error("Erreur serveur (code " + res.status + ")");
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                message.textContent = "Image recadrée avec succès !";
                message.style.color = "#006DB7"; // // bleu lego
                // // redirection
                window.location.href = "reviewImages?img=" + encodeURIComponent(data.file);
            } else {
                message.textContent = "Erreur : " + (data.message || "Erreur inconnue");
                message.style.color = "#E3000B";
                cropButton.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            message.textContent = "Erreur : " + err.message + ". Vérifiez ImagesModel.php.";
            message.style.color = "#E3000B";
            cropButton.disabled = false;
        });
    }, 'image/png');
});