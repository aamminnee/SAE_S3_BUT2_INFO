<div class="verify-wrapper">
    
    <div class="verify-container">
        <div class="icon-lock">🔒</div>
        
        <h2>Vérification</h2>
        <p class="verify-desc">Un code de sécurité a été envoyé à votre adresse email.</p>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/verify" method="POST">
            <div class="form-group">
                <input type="text" id="token" name="token" required 
                       class="code-input" 
                       placeholder="000000" 
                       maxlength="6" 
                       autocomplete="off">
            </div>
            <button type="submit" class="btn-submit">Valider le code</button>
        </form>
        
        <div class="verify-footer">
            <a href="<?= $_ENV['BASE_URL'] ?>/user/login" class="back-link">
                &larr; Retour à la connexion
            </a>
        </div>
    </div>

</div>