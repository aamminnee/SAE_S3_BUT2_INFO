<div class="login-container">
    <h2><?= $trans['login_title'] ?? 'Connexion' ?></h2>
    
    <?php if (isset($message)): ?>
        <p class="error-msg"><?= $message ?></p>
    <?php endif; ?>

    <form action="<?= $_ENV['BASE_URL'] ?>/user/login" method="POST">
        <div class="form-group">
            <label for="username"><?= $trans['label_username'] ?? "Nom d'utilisateur" ?></label>
            <input type="text" id="username" name="username" required placeholder="Votre pseudo">
        </div>
        
        <div class="form-group">
            <label for="password"><?= $trans['label_password'] ?? 'Mot de passe' ?></label>
            <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
        </div>

        <div class="captcha-group">
            <div class="captcha-visual">
                <canvas id="captcha-canvas" width="200" height="50"></canvas>
                <button id="captcha-refresh" type="button" title="Changer le code">↻</button>
            </div>
            
            <input type="hidden" id="captcha_token" name="captcha_token" value="">
            <input type="text" name="captcha" class="captcha-input" placeholder="<?= $trans['placeholder_captcha'] ?? 'Recopier le code' ?>" required autocomplete="off">
        </div>
        
        <button type="submit" class="btn-submit"><?= $trans['btn_login'] ?? 'Se connecter' ?></button>
    </form>

    <div class="login-footer">
        <p class="register-link">
            <?= $trans['no_account'] ?? 'Pas encore de compte ?' ?> 
            <a href="<?= $_ENV['BASE_URL'] ?>/user/register"><?= $trans['link_register'] ?? "S'inscrire" ?></a>
        </p>
        <p class="forgot-link">
            <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword"><?= $trans['link_forgot_pass'] ?? 'Mot de passe oublié ?' ?></a>
        </p>
    </div>
</div>

<script src="<?= $_ENV['BASE_URL'] ?>/JS/captcha.js"></script>