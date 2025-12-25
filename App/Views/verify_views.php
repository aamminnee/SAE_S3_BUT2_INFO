<div class="verify-container">
    <h2>Vérification</h2>
    <p>Un code a été envoyé à votre adresse email.</p>

    <form action="<?= $_ENV['BASE_URL'] ?>/user/verify" method="POST">
        <div class="form-group">
            <label for="token">Code de vérification</label>
            <input type="text" id="token" name="token" required placeholder="123456">
        </div>
        <button type="submit" class="btn-submit">Valider</button>
    </form>
    
    <p><a href="<?= $_ENV['BASE_URL'] ?>/user/login">Retour à la connexion</a></p>
</div>