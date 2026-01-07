<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?></title>
    
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/header.css">
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/footer.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <?php if(isset($css)): ?>
        <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/<?= $css ?>">
    <?php endif; ?>
    
    <link rel="icon" href="<?=$_ENV['BASE_URL']?>/images/logo.png">
</head>
<body>
    
    <?php require_once ROOT . '/App/Views/header.php'; ?>

    <main class="main-container">
        <?= $content ?>
    </main>

    <?php require_once ROOT . '/App/Views/footer.html'; ?>

    </body>
</html>