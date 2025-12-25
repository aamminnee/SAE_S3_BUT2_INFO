// DOM elements
const image = document.getElementById('image');
const cropButton = document.getElementById('cropButton');
const message = document.getElementById('message');
const warnings = document.getElementById('warnings');
const aspectSelect = document.getElementById('aspect');
const sizeSelect = document.getElementById('size');

// Initialize Cropper.js
let cropper = new Cropper(image, {
    aspectRatio: 1, 
    viewMode: 1,
    background: false,
    autoCropArea: 1,
});

// Warn if image is very large
image.onload = () => {
    if (image.naturalWidth > 3000 || image.naturalHeight > 3000) {
        warnings.textContent = "The image is very large. It may be resized in the browser for performance.";
    }
};

// Dynamically change crop aspect ratio
aspectSelect.addEventListener('change', () => {
    const value = aspectSelect.value === "NaN" ? NaN : eval(aspectSelect.value);
    cropper.setAspectRatio(value);
});

// Handle crop and continue
cropButton.addEventListener('click', () => {
    // Get crop data first to check dimensions
    const cropData = cropper.getData(true);
    const cropWidth = Math.round(cropData.width);
    const cropHeight = Math.round(cropData.height);

    const minSize = 100; 

    if (cropWidth < minSize || cropHeight < minSize) {
        message.textContent = "Erreur : La zone sélectionnée est trop petite (min " + minSize + "x" + minSize + "px).";
        message.style.color = "red";
        return; 
    }

    message.textContent = "Processing...";
    message.style.color = "black"; 
    warnings.textContent = "";

    // Chosen board size
    const boardSize = parseInt(sizeSelect.value);

    // Generate cropped canvas (no resizing here)
    const canvasData = cropper.getCroppedCanvas({
        width: cropWidth,
        height: cropHeight
    });

    // Convert canvas to Blob and send via AJAX
    canvasData.toBlob(blob => {
        const formData = new FormData();
        formData.append('cropped_image', blob, 'cropped.png');
        formData.append('original_name', image.dataset.originalName);
        formData.append('size', boardSize); // send size to store in session

        fetch('../control/crop_images_control.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                message.textContent = "Image successfully cropped!";
                // Redirect to review page
                window.location.href = "review_images_views.php?img=" + encodeURIComponent(data.file);
            } else {
                message.textContent = "Error: " + data.message;
            }
        })
        .catch(err => {
            message.textContent = "Error: " + err.message;
        });
    }, 'image/png');
});