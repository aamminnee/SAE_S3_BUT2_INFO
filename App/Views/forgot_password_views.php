<main class="main-content">
    <div class="login-container"> <h2>Réinitialisation</h2>
        
        <?php if (isset($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPassword" method="POST">
            <div class="form-group">
                <label for="email">Votre adresse email</label>
                <input type="email" id="email" name="email" required placeholder="exemple@email.com">
            </div>
            
            <button type="submit" class="btn-submit">Envoyer le code</button>
        </form>

        <div class="login-footer">
            <p><a href="<?= $_ENV['BASE_URL'] ?>/user/login">Retour à la connexion</a></p>
        </div>
    </div>
</main>