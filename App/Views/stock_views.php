<div class="admin-container">
    <div class="admin-header">
        <h1><?= $t['stock_title'] ?? 'Gestion de l\'Inventaire' ?></h1>
    </div>

    <div class="admin-content">
        
        <div class="card-admin">
            <h2><?= $t['stock_movement_title'] ?? 'Mouvement de Stock' ?></h2>
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock/add" method="POST" class="form-admin">
                <div class="form-group">
                    <label for="item_search"><?= $t['stock_label_search'] ?? 'Rechercher une pièce (Nom ou ID) :' ?></label>
                    <input type="text" list="items_list" id="item_search" class="form-control" 
                           placeholder="<?= $t['stock_placeholder_search'] ?? "Tapez '2x4' ou 'Bleu'..." ?>" 
                           required autocomplete="off">
                    
                    <input type="hidden" name="item_id" id="real_item_id"> 
                    
                    <datalist id="items_list">
                        <?php if(!empty($allItems)): ?>
                            <?php foreach ($allItems as $item): ?>
                                <option value="<?= htmlspecialchars($item->id_Item) ?> - <?= htmlspecialchars($item->label) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="quantity"><?= $t['stock_label_qty'] ?? 'Quantité (+/-) :' ?></label>
                    <input type="number" name="quantity" id="quantity" required class="form-control" 
                           placeholder="<?= $t['stock_placeholder_qty'] ?? 'Ex: 50 ou -10' ?>">
                </div>

                <button type="submit" class="btn-primary"><?= $t['stock_btn_update'] ?? 'Mettre à jour' ?></button>
            </form>
        </div>

        <hr class="separator">

        <div class="card-admin">
            <div class="card-header-flex">
                <h2><?= $t['stock_status_title'] ?? 'État du Stock' ?></h2>
                
                <form method="GET" action="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock" class="filters-bar">
                    
                    <select name="filter_shape" class="form-control-sm">
                        <option value=""><?= $t['stock_filter_shapes'] ?? 'Toutes les formes' ?></option>
                        <?php if(!empty($shapesList)): ?>
                            <?php foreach ($shapesList as $s): ?>
                                <option value="<?= htmlspecialchars($s->name) ?>" 
                                    <?= (isset($_GET['filter_shape']) && $_GET['filter_shape'] == $s->name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <select name="filter_color" class="form-control-sm">
                        <option value=""><?= $t['stock_filter_colors'] ?? 'Toutes les couleurs' ?></option>
                        <?php if(!empty($colorsList)): ?>
                            <?php foreach ($colorsList as $c): ?>
                                <option value="<?= htmlspecialchars($c->name) ?>" 
                                    <?= (isset($_GET['filter_color']) && $_GET['filter_color'] == $c->name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c->name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <button type="submit" class="btn-secondary"><?= $t['stock_btn_filter'] ?? 'Filtrer' ?></button>
                    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock" class="btn-secondary" style="text-decoration:none; padding: 9px 12px;">✖</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table-stock">
                    <thead>
                        <tr>
                            <th><?= $t['stock_col_id'] ?? 'ID' ?></th>
                            <th><?= $t['stock_col_preview'] ?? 'Aperçu' ?></th>
                            <th><?= $t['stock_col_shape'] ?? 'Forme' ?></th>
                            <th><?= $t['stock_col_color'] ?? 'Couleur' ?></th>
                            <th><?= $t['stock_col_price'] ?? 'Prix Unit.' ?></th>
                            <th><?= $t['stock_col_stock'] ?? 'Stock Réel' ?></th>
                            <th><?= $t['stock_col_action'] ?? 'Action' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stocks)): ?>
                            <?php foreach ($stocks as $row): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row->id_Item) ?></td>
                                    <td>
                                        <div class="color-preview" style="background-color: #<?= htmlspecialchars($row->hex_color) ?>;"></div>
                                    </td>
                                    <td><?= htmlspecialchars($row->shape_name) ?></td>
                                    <td><?= htmlspecialchars($row->color_name) ?></td>
                                    <td><?= htmlspecialchars($row->price) ?> €</td>
                                    <td>
                                        <span class="badge <?= $row->current_stock < 10 ? 'badge-danger' : 'badge-success' ?>">
                                            <?= $row->current_stock ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn-icon" 
                                        onclick="
                                            document.getElementById('item_search').value = '<?= $row->id_Item ?> - <?= htmlspecialchars($row->shape_name . ' ' . $row->color_name) ?>'; 
                                            document.getElementById('real_item_id').value = '<?= $row->id_Item ?>'; 
                                            window.scrollTo({ top: 0, behavior: 'smooth' }); 
                                            return false;
                                        "
                                        title="<?= $t['stock_tooltip_edit'] ?? 'Modifier le stock' ?>">
                                        ✏️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 20px; color: #666;"><?= $t['stock_empty_msg'] ?? 'Aucune pièce trouvée avec ces filtres.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <?php 
                $currentPage = (int)($_GET['page'] ?? 1);
                $totalPages = $totalPages ?? 1; 

                // Fonction pour reconstruire l'URL en gardant les filtres actuels
                function getPageLink($page) {
                    $params = $_GET; 
                    $params['page'] = $page; 
                    return '?' . http_build_query($params); 
                }
                ?>
                
                <?php if ($currentPage > 1): ?>
                    <a href="<?= getPageLink($currentPage - 1) ?>" class="page-link">&laquo; <?= $t['stock_pagination_prev'] ?? 'Précédent' ?></a>
                <?php endif; ?>

                <span class="page-info">
                    <?= sprintf(($t['stock_pagination_info'] ?? 'Page %s sur %s'), $currentPage, $totalPages) ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= getPageLink($currentPage + 1) ?>" class="page-link"><?= $t['stock_pagination_next'] ?? 'Suivant' ?> &raquo;</a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<script>
    // Ce script permet de récupérer l'ID réel quand on sélectionne une option dans la liste
    document.getElementById('item_search').addEventListener('input', function() {
        var val = this.value;
        var opts = document.getElementById('items_list').childNodes;
        var found = false;
        
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].value === val) {
                // On extrait l'ID (Format supposé: "ID - Label")
                var id = val.split(' - ')[0];
                document.getElementById('real_item_id').value = id;
                found = true;
                break;
            }
        }
        // Sécurité : si l'utilisateur vide le champ, on vide l'ID
        if (!found && val === '') {
            document.getElementById('real_item_id').value = '';
        }
    });

    // Validation avant envoi pour être sûr qu'un ID est sélectionné
    document.querySelector('.form-admin').addEventListener('submit', function(e) {
        var id = document.getElementById('real_item_id').value;
        if (!id) {
            e.preventDefault();
            // Traduction de l'alerte JS via PHP
            alert("<?= $t['stock_js_alert'] ?? 'Veuillez sélectionner une pièce valide dans la liste déroulante.' ?>");
        }
    });
</script>