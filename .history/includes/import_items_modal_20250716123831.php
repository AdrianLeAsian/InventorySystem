<!-- Import Items Modal -->
<div id="importItemsModal" class="modal is-hidden">
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="modal__title">Import Inventory Items</h3>
            <button class="modal__close-btn cancel-modal-btn" data-modal-id="importItemsModal">&times;</button>
        </div>
        <div class="modal__body">
            <form id="importItemsForm" enctype="multipart/form-data">
                <div class="form__group">
                    <label for="excelFile" class="form__label">Upload Excel/CSV File:</label>
                    <input type="file" id="excelFile" name="excelFile" accept=".xlsx, .csv" class="form__input" required>
                    <small class="form__text-muted">Supported formats: .xlsx, .csv</small>
                </div>
                <div id="importResult" class="mb-3" style="display: none;"></div>
                <div class="d-flex justify-end gap-2">
                    <button type="submit" class="btn btn--primary">Import</button>
                    <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="importItemsModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
