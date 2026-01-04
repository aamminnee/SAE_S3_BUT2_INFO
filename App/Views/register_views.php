<div class="register-container">
    <h2><?= $trans['register_title'] ?? 'Inscription' ?></h2>

    <?php if (isset($_SESSION['register_message'])): ?>
        <p class="error-msg"><?= $_SESSION['register_message'] ?></p>
        <?php unset($_SESSION['register_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
        <p class="error-msg"><?= $message ?></p>
    <?php endif; ?>

    <form action="<?= $_ENV['BASE_URL'] ?>/user/register" method="POST">
        
        <div class="form-group">
            <label for="username"><?= $trans['label_username'] ?? "Nom d'utilisateur" ?></label>
            <input type="text" id="username" name="username" required placeholder="Choisis ton pseudo">
        </div>

        <div class="form-group">
            <label for="lastname">Nom de famille</label>
            <input type="text" name="lastname" id="lastname" required placeholder="Ton nom de famille">
        </div>

        <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" required placeholder="exemple@email.com">
        </div>
        
        <div class="form-group">
            <label for="password"><?= $trans['label_password'] ?? 'Mot de passe' ?></label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
            <small><?= $trans['password_requirements'] ?? 'Doit contenir 8 caractères min., 1 majuscule, 1 chiffre et 1 caractère spécial.' ?></small>
        </div>

        <button type="submit" class="btn-submit"><?= $trans['btn_register'] ?? "Créer mon compte" ?></button>
    </form>

    <p class="login-link">
        <?= $trans['have_account'] ?? 'Déjà un compte ?' ?> 
        <a href="<?= $_ENV['BASE_URL'] ?>/user/login"><?= $trans['link_login'] ?? 'Se connecter' ?></a>
    </p>
</div>