<div class="container-center">
    <h2>Nouveau mot de passe</h2>
    <p class="info-text">
        Votre mot de passe doit respecter les recommandations de la CNIL : 
        12 caractères min, majuscule, minuscule, chiffre, caractère spécial.
        Il doit être différent de votre ancien mot de passe.
    </p>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm">
        
        <input type="hidden" name="reset_password" value="1">

        <div class="form-group">
            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" name="password" id="new_password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="password_confirm" id="confirm_password" required>
        </div>

        <button type="submit" class="btn-primary">Valider</button>
    </form>
</div>