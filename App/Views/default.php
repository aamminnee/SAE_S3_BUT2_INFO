<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?? 'SAE_S3_BUT2_INFO' ?></title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="/css/footer.css">

    <?php if(isset($css)): ?>
        <link rel="stylesheet" href="/css/<?= $css ?>">
    <?php endif; ?>
    
    <link rel="icon" href="/images/logo.png">
</head>
<body>
    
    <?php require_once ROOT . '/App/Views/header.php'; ?>

    <main class="main-container">
        <?= $content ?>
    </main>

    <?php require_once ROOT . '/App/Views/footer.html'; ?>

    </body>
</html>