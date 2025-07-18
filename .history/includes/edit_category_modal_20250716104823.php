<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal is-hidden">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Edit Category</h2>
            </div>
            <div class="card__body">
                <form class="form" id="editCategoryForm">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form__group">
                        <label class="form__label">Category Name</label>
                        <input type="text" name="category_name" id="edit_category_name" class="form__input" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="category_description" id="edit_category_description" class="form__input"></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="editCategoryModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
