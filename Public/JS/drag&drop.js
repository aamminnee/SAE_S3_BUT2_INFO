// class to handle drag & drop, paste, and file selection
class DragDropController {
    constructor(isValidUser) {
        // dom elements
        this.dropZone = document.getElementById("drop-zone");
        this.fileInput = document.getElementById("fileInput");
        this.fileLabel = document.getElementById("fileLabel");
        this.message = document.getElementById("message");
        this.continueButton = document.getElementById("continueButton");
        this.preview = document.getElementById("preview");

        // internal state
        this.selectedFile = null;
        this.isValidUser = isValidUser;

        // ensure correct context for asynchronous callbacks
        this.showError = this.showError.bind(this);
        this.uploadFile = this.uploadFile.bind(this);
        this.handleFile = this.handleFile.bind(this);

        // setup events
        this.setupEvents();
    }

    // setup all event listeners
    setupEvents() {
        if (!this.dropZone || !this.fileInput || !this.message || !this.continueButton) {
            console.error("missing one or more required dom elements.");
            return;
        }

        // drag & drop events
        this.dropZone.addEventListener("dragover", e => { 
            e.preventDefault(); 
            this.dropZone.classList.add("dragover"); 
        });
        this.dropZone.addEventListener("dragleave", () => this.dropZone.classList.remove("dragover"));
        this.dropZone.addEventListener("drop", e => {
            e.preventDefault(); 
            this.dropZone.classList.remove("dragover");
            const file = e.dataTransfer.files[0]; 
            if (file) this.handleFile(file);
        });

        // paste events
        document.addEventListener("paste", e => {
            const items = e.clipboardData.items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].kind === "file") {
                    this.handleFile(items[i].getAsFile()); 
                    break;
                }
            }
        });

        // file input change
        this.fileInput.addEventListener("change", e => { 
            const file = e.target.files[0]; 
            if (file) this.handleFile(file); 
        });

        // continue button click
        this.continueButton.addEventListener("click", () => {
            console.log("selectedFile before upload:", this.selectedFile);
            if (!this.selectedFile) { 
                this.showError('no image selected.'); 
                return; 
            }
            if (!this.isValidUser) { 
                this.showError('you must be logged in and validated to upload an image.'); 
                return; 
            }
            this.uploadFile();
        });
    }

    // handle a selected file
    handleFile(file) {
        const allowedTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
        const maxSize = 2 * 1024 * 1024; // 2mb

        this.message.textContent = ""; 
        this.message.style.color = "black";

        if (!allowedTypes.includes(file.type)) { 
            this.showError('unsupported file type. allowed: jpg, png, webp.'); 
            return; 
        }
        if (file.size > maxSize) { 
            this.showError('image too large (>2mb).'); 
            return; 
        }

        const img = new Image();
        img.onload = () => {
            if (img.width < 512 || img.height < 512) { 
                this.showError('image too small (min 512x512).'); 
                return; 
            }

            // file is valid
            this.selectedFile = file;
            this.message.textContent = "image successfully loaded, click continue.";
            this.message.style.color = "green";
            this.continueButton.style.display = "inline-block";

            // display preview
            this.dropZone.innerHTML = "";
            img.style.width = "100%";
            img.style.height = "100%";
            img.style.objectFit = "contain";
            img.style.borderRadius = "8px";
            this.dropZone.appendChild(img);
        };
        img.src = URL.createObjectURL(file);
    }

    // show an error message
    showError(msg) {
        console.log("showError called", {
            message: this.message,
            preview: this.preview,
            continueButton: this.continueButton
        });
        this.message.textContent = msg;
        this.message.style.color = "red";
        this.continueButton.style.display = "none";
        this.preview.style.display = "none";
        this.selectedFile = null;
    }


    // upload file to server
    uploadFile() {
        const formData = new FormData();
        formData.append("image_input", this.selectedFile);
        formData.append("upload", true);

        this.message.textContent = "uploading..."; 
        this.message.style.color = "orange";

        fetch("../control/images_control.php", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                window.location.href = "crop_images_views.php?img=" + encodeURIComponent(data.file);
            } else {
                this.showError("error: " + data.message);
            }
        })
        .catch(err => {
            this.showError("error: " + err.message);
        });
    }
}

// initialize controller on dom ready
document.addEventListener("DOMContentLoaded", () => { 
    new DragDropController(isValidUser); 
});
