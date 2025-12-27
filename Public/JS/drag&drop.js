// SAE_S3_BUT2_INFO/Public/JS/drag&drop.js

// sélection des éléments du dom
const dropArea = document.getElementById('drop-zone');
const input = document.getElementById('file-upload');
const form = dropArea ? dropArea.closest('form') : null;

// Vérification de sécurité
if (!dropArea || !input) {
    console.error("Erreur: La zone de drop ou l'input n'a pas été trouvé dans le HTML.");
} else {

    // prévention des comportements par défaut
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Styles CSS au survol
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    // 1. GESTION DU DROP (Glisser-Déposer)
    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files; // Récupère les fichiers physiques
        
        console.log("Fichiers détectés au drop:", files.length);

        if (files.length > 0) {
            handleFiles(files);
        }
    }, false);

    // 2. GESTION DU COPIER-COLLER (Ctrl+V)
    window.addEventListener('paste', (e) => {
        // On cherche dans le presse-papier
        if (e.clipboardData && e.clipboardData.files.length > 0) {
            e.preventDefault(); // Empêche de coller le fichier comme du texte ailleurs
            console.log("Fichier détecté au collage (Paste)");
            handleFiles(e.clipboardData.files);
        }
    });

    // 3. GESTION DU CLIC CLASSIQUE
    dropArea.addEventListener('click', () => {
        input.click();
    });

    input.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFiles(this.files);
        }
    });
}

// FONCTION PRINCIPALE
function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0]; // On ne prend QUE le premier fichier
        
        // Vérification que c'est une image
        if (!file.type.startsWith('image/')) {
            alert("Erreur : Le fichier n'est pas une image valide.");
            return;
        }

        // --- PARTIE CRITIQUE : Mise à jour de l'input form ---
        // On crée une nouvelle liste de fichiers contenant UNIQUEMENT l'image choisie
        // Cela garantit que l'envoi du formulaire ne contient qu'un seul fichier propre.
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files; 

        console.log("Input mis à jour avec le fichier :", input.files[0].name);

        // Affichage
        previewFile(file);
    }
}

function previewFile(file) {
    const reader = new FileReader();
    
    reader.readAsDataURL(file);
    reader.onloadend = function() {
        const img = document.createElement('img');
        img.src = reader.result;
        
        // Styles de l'image
        img.style.maxWidth = '100%';
        img.style.maxHeight = '300px';
        img.style.borderRadius = '8px';
        img.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        img.style.objectFit = 'contain'; // Assure que l'image ne soit pas déformée
        
        dropArea.innerHTML = ''; 
        dropArea.appendChild(img);
        
        const changeText = document.createElement('p');
        changeText.textContent = "Cliquez ou collez (Ctrl+V) pour changer l'image";
        changeText.style.marginTop = '10px';
        changeText.style.fontSize = '0.9rem';
        changeText.style.color = '#64748b';
        dropArea.appendChild(changeText);
    }
}