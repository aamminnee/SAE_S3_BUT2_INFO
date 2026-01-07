<div class="reset-wrapper">

    <div class="reset-container">
        <div class="icon-key">🔑</div>
        
        <h2>Nouveau mot de passe</h2>
        <p class="reset-desc">Sécurisez votre compte avec un nouveau mot de passe fort.</p>
        
        <?php if (isset($message) && !empty($message)): ?>
            <div class="alert error-msg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm" method="POST">
            <input type="hidden" name="reset_password" value="true">
            
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn-submit">Changer le mot de passe</button>
        </form>
    </div>

</div>