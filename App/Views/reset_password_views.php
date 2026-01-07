<div class="reset-wrapper">

    <div class="reset-container">
        <div class="icon-key">🔑</div>
        
        <h2>Nouveau mot de passe</h2>
        
        <p class="reset-desc" style="font-size: 0.9em; color: #555;">
            Votre mot de passe doit respecter les recommandations de la CNIL : 
            12 caractères min, majuscule, minuscule, chiffre, caractère spécial.
        </p>
        
        <?php 
        $msg = $error ?? ($message ?? null);
        if (!empty($msg)): 
        ?>
            <div class="alert error-msg">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm" method="POST">
            <input type="hidden" name="reset_password" value="1">
            
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn-submit">Valider</button>
        </form>
    </div>

</div>