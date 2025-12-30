// on attend que toute la page soit chargée avant de lancer le script
document.addEventListener("DOMContentLoaded", () => {
    const dropArea = document.getElementById('drop-zone');
    const input = document.getElementById('file-upload');
    const form = dropArea ? dropArea.closest('form') : null;

    // --- sécurité critique ---
    // on empêche le navigateur d'ouvrir l'image si on rate la zone
    window.addEventListener('dragover', e => e.preventDefault(), false);
    window.addEventListener('drop', e => e.preventDefault(), false);

    if (!dropArea || !input) {
        console.error("erreur critique : la zone de drop ou l'input est introuvable.");
        return;
    }

    // fonctions pour empêcher le comportement par défaut sur la zone
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // activation des écouteurs sur la zone
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    // effets visuels (ajout/retrait classe css)
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    // 1. gestion du drop
    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) handleFiles(files);
    });

    // 2. gestion du clic
    dropArea.addEventListener('click', () => input.click());

    input.addEventListener('change', function() {
        if (this.files.length > 0) handleFiles(this.files);
    });

    // 3. gestion du coller (paste)
    window.addEventListener('paste', (e) => {
        if (e.clipboardData && e.clipboardData.files.length > 0) {
            e.preventDefault();
            handleFiles(e.clipboardData.files);
        }
    });

    // traitement des fichiers
    function handleFiles(files) {
        const file = files[0];
        if (file.type.startsWith('image/')) {
            // mise à jour de l'input caché pour l'envoi formulaire
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
            
            previewFile(file);
        } else {
            alert("ce n'est pas une image valide !");
        }
    }

    // affichage de la prévisualisation
    function previewFile(file) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            // création de l'image
            const img = document.createElement('img');
            img.src = reader.result;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '300px';
            img.style.objectFit = 'contain';
            
            dropArea.innerHTML = ''; // on vide le texte
            dropArea.appendChild(img); // on met l'image
        }
    }

    // 4. envoi du formulaire
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (input.files.length === 0) {
                alert("veuillez sélectionner une image.");
                return;
            }

            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const oldText = btn.innerText;
            btn.innerText = "envoi...";
            btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = "cropImages"; // ou data.redirect si renvoyé
                } else {
                    alert("erreur: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("erreur technique lors de l'envoi.");
            })
            .finally(() => {
                btn.innerText = oldText;
                btn.disabled = false;
            });
        });
    }
});