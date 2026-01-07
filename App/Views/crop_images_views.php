<?php
$baseUrl = $_ENV['BASE_URL'] ?? '';
?>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
</head>

<div class="crop-workspace">
    
    <div class="workspace-header">
        <h1>Crop & Configure</h1>
        <p>Select the area to transform into bricks.</p>
    </div>

    <?php if (isset($image) && !empty($image) && isset($image['file'])): ?>
        
        <div class="crop-interface">
            
            <div class="editor-zone">
                <div class="image-container">
                    <img id="image-to-crop" 
                         src="data:<?= $image['file_type'] ?>;base64,<?= base64_encode($image['file']) ?>" 
                         alt="<?= htmlspecialchars($image['filename']) ?>"
                         data-id="<?= $image['id_Image'] ?>"> 
                </div>
            </div>

            <aside id="options-panel" class="settings-sidebar">
                <div class="settings-card">
                    <div class="card-deco-bar"></div>

                    <h3>Settings</h3>
                    
                    <div class="option-group">
                        <label for="size">
                            Board Size
                        </label>
                        <div class="select-wrapper">
                            <select id="size">
                                <option value="32">32 x 32 (Small)</option>
                                <option value="48">48 x 48 (Medium)</option>
                                <option value="64" selected>64 x 64 (Large)</option>
                                <option value="96">96 x 96 (Extra Large)</option>
                                <option value="128">128 x 128 (Giant)</option>
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>
                    </div>

                    <div class="option-group">
                        <label for="aspect">
                            Aspect Ratio
                        </label>
                        <div class="select-wrapper">
                            <select id="aspect">
                                <option value="1" selected>Square (1:1)</option>
                                <option value="1.33333">Landscape (4:3)</option>
                                <option value="1.77777">Cinema (16:9)</option>
                                <option value="0.75">Portrait (3:4)</option>
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>
                    </div>

                    <div class="action-footer">
                        <button id="btn-crop" class="btn-validate">
                            Generate Mosaic
                        </button>
                    </div>
                </div>
            </aside>

        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        <script src="<?= $baseUrl ?>/JS/crop_images.js"></script>

    <?php else: ?>
        <div class="empty-state">
            <div class="alert-box">
                <p>Oops, no image found.</p>
                <a href="<?= $baseUrl ?>/images" class="btn-validate">Upload an image</a>
            </div>
        </div>
    <?php endif; ?>

</div>