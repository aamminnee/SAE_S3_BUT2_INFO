<main class="main-content">
    <div class="register-container">
        <h2><?= $t['register_title'] ?? 'Inscription' ?></h2>

        <?php if (isset($_SESSION['register_message'])): ?>
            <p class="error-msg"><?= $_SESSION['register_message'] ?></p>
            <?php unset($_SESSION['register_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/register" method="POST">
            
            <div class="form-group">
                <label for="username"><?= $t['register_label_username'] ?? "Nom d'utilisateur" ?></label>
                <input type="text" id="username" name="username" required 
                       placeholder="<?= $t['register_placeholder_username'] ?? 'Choisis ton pseudo' ?>">
            </div>

            <div class="form-group">
                <label for="lastname"><?= $t['register_label_lastname'] ?? 'Nom de famille' ?></label>
                <input type="text" name="lastname" id="lastname" required 
                       placeholder="<?= $t['register_placeholder_lastname'] ?? 'Ton nom de famille' ?>">
            </div>

            <div class="form-group">
                <label for="email"><?= $t['register_label_email'] ?? 'Adresse Email' ?></label>
                <input type="email" id="email" name="email" required 
                       placeholder="<?= $t['register_placeholder_email'] ?? 'exemple@email.com' ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><?= $t['register_label_password'] ?? 'Mot de passe' ?></label>
                <input type="password" id="password" name="password" required 
                       placeholder="<?= $t['register_placeholder_password'] ?? '••••••••' ?>">
                <small><?= $t['register_password_req'] ?? 'Min. 8 caractères, 1 majuscule, 1 chiffre et 1 caractère spécial.' ?></small>
            </div>

            <button type="submit" class="btn-submit"><?= $t['register_btn_submit'] ?? "Créer mon compte" ?></button>
        </form>

        <div class="login-footer">
            <p>
                <?= $t['register_have_account'] ?? 'Déjà un compte ?' ?> 
                <a href="<?= $_ENV['BASE_URL'] ?>/user/login"><?= $t['register_link_login'] ?? 'Se connecter' ?></a>
            </p>
        </div>
    </div>
</main>