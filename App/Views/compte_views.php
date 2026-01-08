<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2><?= $t['account_title'] ?? 'Mon Compte' ?></h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4><?= $t['account_personal_info'] ?? 'Informations Personnelles' ?></h4>
                    <?php 
                        // // gestion des objets ou tableaux selon le retour du modèle
                        $username = is_object($user) ? $user->username : ($user['username'] ?? '');
                        $email = is_object($user) ? $user->email : ($user['email'] ?? '');
                        $etat = is_object($user) ? $user->etat : ($user['etat'] ?? 'invalide');
                    ?>
                    
                    <p><strong><?= $t['account_label_username'] ?? 'Nom d\'utilisateur :' ?></strong> <?= htmlspecialchars($username) ?></p>
                    <p><strong><?= $t['account_label_email'] ?? 'Email :' ?></strong> <?= htmlspecialchars($email) ?></p>
                    <p><strong><?= $t['account_label_status'] ?? 'Statut du compte :' ?></strong> 
                        <?php if ($etat === 'valide'): ?>
                            <span class="badge bg-success"><?= $t['account_status_valid'] ?? 'Validé' ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><?= $t['account_status_invalid'] ?? 'Non validé' ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="col-md-6 border-start">
                    <h4><?= $t['account_actions'] ?? 'Actions' ?></h4>
                    
                    <?php if ($etat !== 'valide'): ?>
                        <div class="alert alert-info">
                            <?= $t['account_msg_activate'] ?? 'Votre compte n\'est pas encore activé.' ?>
                            <br>
                            <a href="<?= $_ENV['BASE_URL'] ?>/compte/activer" class="btn btn-warning mt-2">
                                <?= $t['account_btn_activate'] ?? 'Activer mon compte' ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="<?= $_ENV['BASE_URL'] ?>/user/logout" class="btn btn-danger"><?= $t['account_btn_logout'] ?? 'Se déconnecter' ?></a>
                        <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn btn-secondary"><?= $t['account_btn_home'] ?? 'Retour à l\'accueil' ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>