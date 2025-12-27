<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?? 'SAE_S3_BUT2_INFO' ?></title>
    
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/style.css">
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/header.css">
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/footer.css">

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