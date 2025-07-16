<!-- Import Items Modal -->
<div id="importItemsModal" class="modal">
    <div class="modal-content">
        <div class="modal__header">
            <h3 class="modal__title">Import Inventory Items</h3>
            <button class="modal__close-btn cancel-modal-btn" data-modal-id="importItemsModal">&times;</button>
        </div>
        <div class="modal__body">
            <form id="importItemsForm" enctype="multipart/form-data">
                <div class="form__group">
                    <label for="excelFile" class="form__label">Upload Excel/CSV File:</label>
                    <p class="form__text-muted mb-3">
                        Please use our template for importing items. You can download it here:
                        <a href="assets/templates/sample_inventory_template.csv" download class="text-link">Download Sample Template (CSV)</a>
                    </p>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" id="excelFile" name="excelFile" accept=".xlsx, .csv" class="file-upload-area__input" required>
                        <p class="file-upload-area__text">Drag & Drop your file here or <span class="text-link">Browse</span></p>
                        <p class="file-upload-area__info">Supported formats: .xlsx, .csv</p>
                    </div>
                    <div id="fileNameDisplay" class="file-upload-area__file-name" style="display: none;"></div>
                </div>
                <div id="importProgress" class="progress-bar-container mb-3" style="display: none;">
                    <div class="progress-bar" id="progressBar"></div>
                    <div class="progress-text" id="progressText">0%</div>
                </div>
                <div id="importResult" class="alert" style="display: none;"></div>
                <div class="d-flex justify-end gap-2">
                    <button type="submit" class="btn btn--primary" id="importSubmitBtn">Import</button>
                    <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="importItemsModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
