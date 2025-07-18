<!-- Log Stock Modal -->
<div id="logStockModal" class="modal is-hidden">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Log Stock Movement</h2>
            </div>
            <div class="card__body">
                <div class="form__group mb-4">
                    <label class="form__label">Scan Barcode</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="barcode_scanner_input" class="form__input" placeholder="Click here and scan barcode...">
                        <span id="barcode_status" class="text-muted"></span>
                    </div>
                </div>

                <form class="form" id="logStockForm">
                    <div class="form__row">
                        <div class="form__group">
                            <label class="form__label">Select Item <span class="required-indicator">*</span></label>
                            <select name="item_id" class="form__input" required>
                                <option value="">-- Select Item --</option>
                                <?php foreach ($items_options as $item_opt): ?>
                                    <option value="<?php echo $item_opt['id']; ?>">
                                        <?php echo htmlspecialchars($item_opt['name']); ?> 
                                        (Current Stock: <?php echo htmlspecialchars($item_opt['quantity']); ?> <?php htmlspecialchars($item_opt['unit']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form__group">
                            <label class="form__label">Quantity Change <span class="required-indicator">*</span></label>
                            <input type="number" name="quantity_change" class="form__input" min="1" required>
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Reason/Note <span class="required-indicator">*</span></label>
                        <input type="text" name="reason" class="form__input" placeholder="e.g., New Shipment, Used for X, Spoilage" required>
                    </div>

                    <div class="d-flex modal-buttons mt-4">
                        <button type="submit" class="btn btn--success" name="stock_type" value="stock_in">Stock In</button>
                        <button type="submit" class="btn btn--danger" name="stock_type" value="stock_out">Stock Out</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="logStockModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
