<!-- Edit Item Modal -->
<div id="editItemModal" class="modal is-hidden" style="display: none;">
    <div class="modal-content" style="z-index: 1000;">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Edit Item</h2>
            </div>
            <div class="card__body">
                <form class="form" id="editItemForm">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="form__group">
                        <label class="form__label">Item Name <span class="required-indicator">*</span></label>
                        <input type="text" name="item_name" id="edit_item_name" class="form__input" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Category <span class="required-indicator">*</span></label>
                        <select name="item_category_id" id="edit_item_category_id" class="form__input" required>
                            <option value="">Select Category</option>
                            <?php foreach ($all_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__row">
                        <div class="form__group">
                            <label class="form__label">Quantity <span class="required-indicator">*</span></label>
                            <input type="number" name="item_quantity" id="edit_item_quantity" class="form__input" value="0" min="0" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Unit</label>
                            <input type="text" name="item_unit" id="edit_item_unit" class="form__input" value="pcs">
                        </div>
                    </div>
                    <div class="form__row">
                        <div class="form__group">
                            <label class="form__label">Low Stock Threshold</label>
                            <input type="number" name="item_low_stock_threshold" id="edit_item_low_stock_threshold" class="form__input" value="0" min="0">
                        </div>
                        <div class="form__group">
                            <label class="form__label">Min Stock Level</label>
                            <input type="number" name="item_min_stock_level" id="edit_item_min_stock_level" class="form__input" value="0" min="0">
                        </div>
                    </div>
                    <div class="form__row">
                        <div class="form__group">
                            <label class="form__label">Max Stock Level</label>
                            <input type="number" name="item_max_stock_level" id="edit_item_max_stock_level" class="form__input" value="0" min="0">
                        </div>
                        <div class="form__group">
                            <label class="form__label">Location</label>
                            <input type="text" name="item_location" id="edit_item_location" class="form__input">
                        </div>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="item_description" id="edit_item_description" class="form__input"></textarea>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Reason/Purpose for Update <span class="required-indicator">*</span></label>
                        <textarea name="item_reason" id="edit_item_reason" class="form__input" required></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="editItemModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
