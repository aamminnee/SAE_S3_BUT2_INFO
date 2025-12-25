<div class="reset-container">
    <h2>Réinitialisation du mot de passe</h2>
    
    <?php if (isset($message)): ?>
        <p class="error-msg"><?= $message ?></p>
    <?php endif; ?>

    <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm" method="POST">
        <input type="hidden" name="reset_password" value="true">
        
        <div class="form-group">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
        
        <button type="submit" class="btn-submit">Changer le mot de passe</button>
    </form>
</div>