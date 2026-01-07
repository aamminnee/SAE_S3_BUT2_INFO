<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2>Mon Compte</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Informations Personnelles</h4>
                    <?php 
                        // // gestion des objets ou tableaux selon le retour du modèle
                        $username = is_object($user) ? $user->username : ($user['username'] ?? '');
                        $email = is_object($user) ? $user->email : ($user['email'] ?? '');
                        $etat = is_object($user) ? $user->etat : ($user['etat'] ?? 'invalide');
                    ?>
                    
                    <p><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($username) ?></p>
                    <p><strong>Email :</strong> <?= htmlspecialchars($email) ?></p>
                    <p><strong>Statut du compte :</strong> 
                        <?php if ($etat === 'valide'): ?>
                            <span class="badge bg-success">Validé</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Non validé</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="col-md-6 border-start">
                    <h4>Actions</h4>
                    
                    <?php if ($etat !== 'valide'): ?>
                        <div class="alert alert-info">
                            Votre compte n'est pas encore activé.
                            <br>
                            <a href="<?= $_ENV['BASE_URL'] ?>/compte/activer" class="btn btn-warning mt-2">
                                Activer mon compte
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="<?= $_ENV['BASE_URL'] ?>/user/logout" class="btn btn-danger">Se déconnecter</a>
                        <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>