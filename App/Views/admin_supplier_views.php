<div class="admin-container">
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