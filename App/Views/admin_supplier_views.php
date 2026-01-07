<div class="admin-container">
    <h1>Espace Fournisseur - Historique des Commandes Usine</h1>

    <?php 
    // // vérification si on a des commandes à afficher
    if (isset($orders) && !empty($orders)): 
    ?>
        <div class="factory-orders-list">
            <?php 
            // // on boucle sur chaque commande groupée
            foreach ($orders as $orderId => $data): 
                $orderInfo = $data['info'];
            ?>
                <div class="admin-card">
                    
                    <div class="order-header">
                        <div>
                            <h3>
                                Commande <span>#<?= htmlspecialchars($orderId) ?></span>
                            </h3>
                            <div>
                                Date : <strong><?= date('d/m/Y', strtotime($orderInfo->order_date)) ?></strong>
                            </div>
                        </div>
                        <div>
                            <div class="total-price">
                                Total : <?= htmlspecialchars($orderInfo->total_price) ?> €
                            </div>
                        </div>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Article</th>
                                <th>Forme</th>
                                <th>Couleur</th>
                                <th>Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // // liste des articles
                            foreach ($data['items'] as $item): 
                            ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($item->id_Item) ?></td>
                                    <td><?= htmlspecialchars($item->shape_name) ?></td>
                                    <td><?= htmlspecialchars($item->color_name) ?></td>
                                    <td>
                                        <span class="qty-badge"><?= htmlspecialchars($item->quantity) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="admin-card">
            <p>Aucune commande passée à l'usine pour le moment.</p>
        </div>
    <?php endif; ?>
</div>