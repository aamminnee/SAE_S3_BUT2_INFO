<div class="settings-container">
    <h1><?= $t['settings_title'] ?? 'Paramètres' ?></h1>

    <?php if (isset($message)): ?>
        <p class="info-msg"><?= $message ?></p>
    <?php endif; ?>

    <div class="setting-section">
        <h3><?= $t['settings_lang_title'] ?? 'Langue / Language' ?></h3>
        
        <div class="language-toggle">
            <a href="<?= $_ENV['BASE_URL'] ?>/setting/setLanguage?lang=fr" class="btn-primary" style="text-decoration: none; margin-right: 10px;">Français</a>
            <a href="<?= $_ENV['BASE_URL'] ?>/setting/setLanguage?lang=en" class="btn-primary" style="text-decoration: none;">English</a>
        </div>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        
        <div class="setting-section">
            <h3><?= $t['settings_security_title'] ?? 'Sécurité' ?></h3>
            
            <div class="security-toggle">
                <p>
                    <?= $t['settings_2fa_label'] ?? 'Double authentification (2FA) :' ?> 
                    <strong>
                        <?php 
                            if (($_SESSION['mode'] ?? '') === '2FA') {
                                echo $t['settings_status_enabled'] ?? 'Activé';
                            } else {
                                echo $t['settings_status_disabled'] ?? 'Désactivé';
                            }
                        ?>
                    </strong>
                </p>
                
                <form action="<?= $_ENV['BASE_URL'] ?>/user/toggle2FA" method="POST">
                    <?php if (($_SESSION['mode'] ?? '') === '2FA'): ?>
                        <input type="hidden" name="mode" value="disable">
                        <button type="submit" class="btn-warning"><?= $t['settings_btn_disable_2fa'] ?? 'Désactiver 2FA' ?></button>
                    <?php else: ?>
                        <input type="hidden" name="mode" value="enable">
                        <button type="submit" class="btn-primary"><?= $t['settings_btn_enable_2fa'] ?? 'Activer 2FA' ?></button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="setting-section">
            <h3><?= $t['settings_pwd_section_title'] ?? 'Mot de passe' ?></h3>
            <p><?= $t['settings_pwd_desc'] ?? 'Pour modifier votre mot de passe' ?></p>
            <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword" class="btn-primary">
                <?= $t['settings_btn_reset_link'] ?? 'Réinitialiser mon mot de passe' ?>
            </a>
        </div>
        
    <?php endif; ?>
</div>