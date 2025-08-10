
<!-- Import Items Modal -->
<div id="importItemsModal" class="modal is-hidden" style="display: none;">
    <div class="modal-content" style="z-index: 1000;">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Import Items from Excel/CSV</h2>
            </div>
            <div class="card__body">
                <form class="form" id="importForm" enctype="multipart/form-data">
                    <div class="form__group">
                        <label class="form__label" for="excelFile">Upload Excel/CSV File <span class="required-indicator">*</span></label>
                        <input class="form__input" type="file" id="excelFile" name="excelFile" accept=".xlsx, .xls, .csv" required>
                        <div class="form-text" style="margin-top: 0.5em; color: var(--text-color-muted);">Accepted formats: <b>.xlsx</b>, <b>.xls</b>, <b>.csv</b></div>
                    </div>
                    <div class="form__group">
                        <input type="checkbox" class="form-check-input" id="updateExisting" name="updateExisting">
                        <label class="form-check-label" for="updateExisting">Update existing items if duplicates are found</label>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" class="btn btn--primary">Import</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="importItemsModal">Cancel</button>
                    </div>
                </form>
                <div id="importSummary" class="mt-4" style="display: none; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 1em;">
                    <h6 style="font-weight: 700; color: var(--primary-color);">Import Summary:</h6>
                    <p>Total items processed: <span id="totalProcessed"></span></p>
                    <p>Items successfully added: <span id="itemsAdded"></span></p>
                    <p>Items skipped: <span id="itemsSkipped"></span></p>
                    <div id="skippedLogLink" style="display: none;">
                        <a href="#" id="downloadLog" target="_blank" style="color: var(--secondary-color); text-decoration: underline;">Download Skipped Entries Log</a>
                    </div>
                </div>
                <div id="importError" class="mt-4 text-danger" style="display: none; font-weight: 600;"></div>
            </div>
        </div>
    </div>
</div>
