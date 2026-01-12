<main class="main-content">
    <div class="login-container">
        <h2><?= $t['login_title'] ?? 'Connexion' ?></h2>
        
        <?php if (isset($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/login" method="POST">
            <div class="form-group">
                <label for="username"><?= $t['login_label_username'] ?? "Nom d'utilisateur" ?></label>
                <input type="text" id="username" name="username" required 
                       placeholder="<?= $t['login_placeholder_username'] ?? 'Votre pseudo' ?>" 
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password"><?= $t['login_label_password'] ?? 'Mot de passe' ?></label>
                <input type="password" id="password" name="password" required 
                       placeholder="<?= $t['login_placeholder_password'] ?? 'Votre mot de passe' ?>" 
                       autocomplete="current-password">
            </div>

            <div class="captcha-group">
                <div class="captcha-visual">
                    <canvas id="captcha-canvas" width="200" height="50"></canvas>
                    <button id="captcha-refresh" type="button" title="<?= $t['login_tooltip_refresh'] ?? 'Changer le code' ?>">↻</button>
                </div>
                <input type="hidden" id="captcha_token" name="captcha_token" value="">
                <input type="text" name="captcha" class="captcha-input" 
                       placeholder="<?= $t['login_placeholder_captcha'] ?? 'Recopier le code' ?>" 
                       required autocomplete="off">
            </div>
            
            <button type="submit" class="btn-submit"><?= $t['login_btn_submit'] ?? 'Se connecter' ?></button>
        </form>

        <div class="login-footer">
            <p>
                <?= $t['login_text_no_account'] ?? 'Pas encore de compte ?' ?> 
                <a href="<?= $_ENV['BASE_URL'] ?>/user/register"><?= $t['login_link_register'] ?? "Créer un compte" ?></a>
            </p>
            <p>
                <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword"><?= $t['login_link_forgot'] ?? 'Mot de passe oublié ?' ?></a>
            </p>
        </div>
    </div>
</main>
<script src="<?= $_ENV['BASE_URL'] ?>/JS/captcha.js"></script>