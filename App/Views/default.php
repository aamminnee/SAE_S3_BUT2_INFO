<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?></title>
    
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

    <script>
        var _paq = window._paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u="//localhost/SAE_S3_BUT2_INFO/matomo/";
            _paq.push(['setTrackerUrl', u+'matomo.php']);
            _paq.push(['setSiteId', '1']);
            var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
        </script>
</body>
</html>