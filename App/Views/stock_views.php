<div class="admin-container">
    <div class="admin-header">
        <h1>Inventory Management</h1>
    </div>

    <div class="admin-content">
        
        <div class="card-admin">
            <h2>Stock Movement</h2>
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock/add" method="POST" class="form-admin">
                <div class="form-group">
                    <label for="item_id">Select Item:</label>
                    <select name="item_id" id="item_id" required class="form-control">
                        <option value="">-- Select a Brick --</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= htmlspecialchars($item->id_Item) ?>">
                                ID <?= $item->id_Item ?> : <?= htmlspecialchars($item->label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (negative to remove):</label>
                    <input type="number" name="quantity" id="quantity" required class="form-control" placeholder="Ex: 50 or -10">
                </div>

                <button type="submit" class="btn-primary">Update Stock</button>
            </form>
        </div>

        <hr class="separator">

        <div class="card-admin">
            <h2>Current Stock Status</h2>
            <div class="table-responsive">
                <table class="table-stock">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Shape</th>
                            <th>Color</th>
                            <th>Unit Price</th>
                            <th>Real Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stocks)): ?>
                            <?php foreach ($stocks as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row->id_Item) ?></td>
                                    <td>
                                        <div style="width: 20px; height: 20px; background-color: #<?= htmlspecialchars($row->hex_color) ?>; border: 1px solid #ccc;"></div>
                                    </td>
                                    <td><?= htmlspecialchars($row->shape_name) ?></td>
                                    <td><?= htmlspecialchars($row->color_name) ?></td>
                                    <td><?= htmlspecialchars($row->price) ?> €</td>
                                    <td style="font-weight: bold; color: <?= $row->current_stock < 10 ? 'red' : 'green' ?>;">
                                        <?= $row->current_stock ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    /* css comments in lowercase */
    /* stock table style */
    .table-stock {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .table-stock th, .table-stock td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    .table-stock th {
        background-color: #f8f9fa;
    }
    .card-admin {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
</style>