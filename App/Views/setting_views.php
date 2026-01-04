<div class="settings-container">
    <h1><?= $trans['settings_title'] ?? 'Paramètres' ?></h1>

    <?php if (isset($message)): ?>
        <p class="info-msg"><?= $message ?></p>
    <?php endif; ?>

    <div class="setting-section">
        <h3><?= $trans['language_title'] ?? 'Langue / Language' ?></h3>
        
        <div class="language-toggle">
            <a href="<?= $_ENV['BASE_URL'] ?>/setting?action=setLanguage&lang=fr" class="btn-primary" style="text-decoration: none; margin-right: 10px;">Français</a>
            <a href="<?= $_ENV['BASE_URL'] ?>/setting?action=setLanguage&lang=en" class="btn-primary" style="text-decoration: none;">English</a>
        </div>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="setting-section">
            <h3><?= $trans['security_title'] ?? 'Sécurité' ?></h3>
            
            <div class="2fa-toggle">
                <p>Double authentification (2FA) : 
                    <strong><?= ($_SESSION['mode'] === '2FA') ? 'Activé' : 'Désactivé' ?></strong>
                </p>
                
                <form action="<?= $_ENV['BASE_URL'] ?>/user/toggle2FA" method="POST">
                    <?php if ($_SESSION['mode'] === '2FA'): ?>
                        <input type="hidden" name="mode" value="disable">
                        <button type="submit" class="btn-warning">Désactiver 2FA</button>
                    <?php else: ?>
                        <input type="hidden" name="mode" value="enable">
                        <button type="submit" class="btn-primary">Activer 2FA</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="setting-section">
            <h3>Changer de mot de passe</h3>
            <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm" method="POST">
                <input type="hidden" name="reset_password" value="1">
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" required>
                </div>
                <button type="submit" class="btn-submit">Mettre à jour</button>
            </form>
        </div>
    <?php endif; ?>
</div>