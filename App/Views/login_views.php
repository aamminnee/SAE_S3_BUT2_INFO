<div class="login-container">
    <h2><?= $trans['login_title'] ?? 'Connexion' ?></h2>
    
    <?php if (isset($message)): ?>
        <p class="error-msg"><?= $message ?></p>
    <?php endif; ?>

    <form action="<?= $_ENV['BASE_URL'] ?>/user/login" method="POST">
        <div class="form-group">
            <label for="username"><?= $trans['label_username'] ?? "Nom d'utilisateur" ?></label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password"><?= $trans['label_password'] ?? 'Mot de passe' ?></label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="captcha-group">
            <canvas id="captcha-canvas" width="200" height="60" style="background-color: #f0f0f0; border-radius: 5px; cursor: pointer;"></canvas>
            <button id="captcha-refresh" type="button" style="border:none; background-color: #000; cursor:pointer; font-size:1.2em; border-radius: 5px;">↻</button>
            <input type="hidden" id="captcha_token" name="captcha_token" value="">
            <input type="text" name="captcha" placeholder="<?= $trans['placeholder_captcha'] ?? 'Recopier le code' ?>" required>
        </div>
        
        <button type="submit" class="btn-submit"><?= $trans['btn_login'] ?? 'Se connecter' ?></button>
    </form>

    <p class="register-link">
        <?= $trans['no_account'] ?? 'Pas encore de compte ?' ?> 
        <a href="<?= $_ENV['BASE_URL'] ?>/user/register"><?= $trans['link_register'] ?? "S'inscrire" ?></a>
    </p>
    <p class="forgot-link">
        <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword"><?= $trans['link_forgot_pass'] ?? 'Mot de passe oublié ?' ?></a>
    </p>
</div>

<script src="<?= $_ENV['BASE_URL'] ?>/JS/captcha.js"></script>