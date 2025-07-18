<!-- Import Items Modal -->
<div class="modal fade" id="importItemsModal" tabindex="-1" aria-labelledby="importItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importItemsModalLabel">Import Items from Excel/CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Upload Excel/CSV File</label>
                        <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx, .csv" required>
                        <div class="form-text">Accepted formats: .xlsx, .csv</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="updateExisting" name="updateExisting">
                        <label class="form-check-label" for="updateExisting">Update existing items if duplicates are found</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
                <div id="importSummary" class="mt-4" style="display: none;">
                    <h6>Import Summary:</h6>
                    <p>Total items processed: <span id="totalProcessed"></span></p>
                    <p>Items successfully added: <span id="itemsAdded"></span></p>
                    <p>Items skipped: <span id="itemsSkipped"></span></p>
                    <div id="skippedLogLink" style="display: none;">
                        <a href="#" id="downloadLog" target="_blank">Download Skipped Entries Log</a>
                    </div>
                </div>
                <div id="importError" class="mt-4 text-danger" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
