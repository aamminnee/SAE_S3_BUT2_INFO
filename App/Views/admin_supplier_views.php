<<div class="admin-container">
    <h1><?= $t['supplier_title'] ?? 'Espace Fournisseur & Approvisionnement' ?></h1>

    <?php if (isset($_SESSION['factory_output'])): ?>
        <div class="admin-card" style="background: #1e1e1e; color: #00ff00; font-family: monospace; padding: 15px; margin-bottom: 20px; border-left: 5px solid #00ff00;">
            <h3 style="margin-top:0; color:#fff;">Résultat Opération :</h3>
            <pre style="white-space: pre-wrap; margin: 0;"><?= htmlspecialchars($_SESSION['factory_output'], ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
        <?php unset($_SESSION['factory_output']); ?>
    <?php endif; ?>

    <div class="admin-card" style="margin-bottom: 30px; background: linear-gradient(135deg, #fff 0%, #f9f9f9 100%);">
        
        <div style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #2c3e50;">Portefeuille MyBrickFactory</h2>
            <p style="margin: 5px 0 0 0; color: #7f8c8d;">
                Solde actuel estimé : 
                <strong style="color: #f1c40f; font-size: 1.4em;">
                    <?= isset($_SESSION['last_factory_balance']) ? number_format($_SESSION['last_factory_balance']) : '?' ?> Crédits
                </strong>
            </p>
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/admin/runFactory" method="POST">
                <input type="hidden" name="action" value="refill">
                <button type="submit" class="btn-primary" style="background-color: #f1c40f; color: #333; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    Minage (+ Crédits)
                </button>
            </form>
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/admin/runFactory" method="POST">
                <input type="hidden" name="action" value="proactive">
                <button type="submit" class="btn-primary" style="background-color: #9b59b6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    Auto-Réapprovisionnement (Proactive)
                </button>
            </form>
        </div>
    </div>

    <h1><?= $t['supplier_title'] ?? 'Espace Fournisseur - Historique des Commandes Usine' ?></h1>

    <?php 
    if (isset($orders) && !empty($orders)): 
    ?>
        <div class="factory-orders-list">
            <?php 
            foreach ($orders as $orderId => $data): 
                $orderInfo = $data['info'];
            ?>
                <div class="admin-card">
                    <div class="order-header">
                        <div>
                            <h3>
                                <?= $t['supplier_order'] ?? 'Commande' ?> <span>#<?= htmlspecialchars($orderId) ?></span>
                            </h3>
                            <div>
                                <?= $t['supplier_date'] ?? 'Date :' ?> <strong><?= date('d/m/Y', strtotime($orderInfo->order_date)) ?></strong>
                            </div>
                        </div>
                        <div>
                            <div class="total-price">
                                <?= $t['supplier_total'] ?? 'Total :' ?> <?= htmlspecialchars($orderInfo->total_price) ?> €
                            </div>
                        </div>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= $t['supplier_col_id'] ?? 'ID Article' ?></th>
                                <th><?= $t['supplier_col_shape'] ?? 'Forme' ?></th>
                                <th><?= $t['supplier_col_color'] ?? 'Couleur' ?></th>
                                <th><?= $t['supplier_col_qty'] ?? 'Quantité' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
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
            <p><?= $t['supplier_empty'] ?? 'Aucune commande passée à l\'usine pour le moment.' ?></p>
        </div>
    <?php endif; ?>
</div>