<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal is-hidden">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Add New Category</h2>
            </div>
            <div class="card__body">
                <form class="form" id="addCategoryForm">
                    <div class="form__group">
                        <label class="form__label">Category Name</label>
                        <input type="text" name="category_name" class="form__input" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="category_description" class="form__input"></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" class="btn btn--primary">Add Category</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="addCategoryModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
