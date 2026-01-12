<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?? 'MyBrixStore' ?></title>
    
    <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/footer.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <?php if(isset($css)): ?>
        <link rel="stylesheet" href="<?=$_ENV['BASE_URL']?>/CSS/<?= $css ?>">
    <?php endif; ?>

    <?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        echo '<link rel="stylesheet" href="'.$_ENV['BASE_URL'].'/CSS/header_admin.css">';
    } else {
        echo '<link rel="stylesheet" href="'.$_ENV['BASE_URL'].'/CSS/header.css">';
    }
    ?>
    
    <link rel="icon" href="<?=$_ENV['BASE_URL']?>/img/logo.png">

</head>
<body>
    
    <?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        require_once ROOT . '/App/Views/header_admin.php';
    } else {
        require_once ROOT . '/App/Views/header.php';
    }
    ?>

    <main class="main-container">
        <?= $content ?>
    </main>

    <?php require_once ROOT . '/App/Views/footer.html';?>

    <script>
        var _paq = window._paq = window._paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u="//localhost/matomo/";
            _paq.push(['setTrackerUrl', u+'matomo.php']);
            _paq.push(['setSiteId', '1']);
            var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
    </script>
    </body>
</html>