<?php
require_once 'config/db.php';

$items = [];
$search_query = '';

if (isset($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT id, name, price, quantity, unit FROM items WHERE name LIKE '%$search_query%' OR barcode LIKE '%$search_query%' ORDER BY name ASC";
} else {
    $sql = "SELECT id, name, price, quantity, unit FROM items ORDER BY name ASC";
}

if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    mysqli_free_result($result);
}
?>

<div class="container pos-layout">
    <div class="main-content">
        <div class="page">
            <div class="card">
                <div class="card__header">
                    <h1 class="card__title">Available Items</h2>
                </div>
                <div class="card__body">
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="page" value="pos">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name or barcode..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn--primary">Search</button>
                        </div>
                    </form>

                    <?php if (!empty($items)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Item Name</th>
                                    <th class="table__cell">Price</th>
                                    <th class="table__cell">Quantity</th>
                                    <th class="table__cell">Unit</th>
                                    <th class="table__cell">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr class="table__row">
                                    <td class="table__cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="table__cell">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="table__cell"><?php echo number_format($item['quantity']); ?></td>
                                    <td class="table__cell"><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td class="table__cell">
                                    <button class="btn btn--success btn--sm add-to-cart-btn" data-item-id="<?php echo $item['id']; ?>" data-item-name="<?php echo htmlspecialchars($item['name']); ?>" data-item-price="<?php echo $item['price']; ?>">Add</button>
                                    <button class="btn btn--primary btn--sm edit-item-btn" data-item-id="<?php echo $item['id']; ?>">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">No items found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="right-sidebar">
        <?php include 'includes/pos_sidebar.php'; ?>
    </div>
</div>

<?php include 'includes/edit_item_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const editItemButtons = document.querySelectorAll('.edit-item-btn');
    const cartItemsList = document.getElementById('cart-items');
    const cartTotalSpan = document.getElementById('cart-total');
    let cart = []; // Array to store cart items

    // Edit Item Modal elements
    const editItemModal = document.getElementById('editItemModal');
    const closeEditItemModal = document.querySelector('#editItemModal .close-button');
    const editItemIdInput = document.getElementById('edit_item_id');
    const editItemNameInput = document.getElementById('edit_item_name');
    const editItemPriceInput = document.getElementById('edit_item_price');
    const editItemQuantityInput = document.getElementById('edit_item_quantity');
    const editItemUnitInput = document.getElementById('edit_item_unit');
    const editItemBarcodeInput = document.getElementById('edit_item_barcode');
    const editItemCategorySelect = document.getElementById('edit_item_category');
    const editItemForm = document.getElementById('editItemForm');
    const editItemMessage = document.getElementById('editItemMessage');

    function updateCartDisplay() {
        cartItemsList.innerHTML = ''; // Clear current display
        let total = 0;

        cart.forEach(item => {
            const cartItemCard = document.createElement('div');
            cartItemCard.classList.add('card', 'cart-item-card', 'mb-2'); // Add mb-2 for spacing between cards
            cartItemCard.innerHTML = `
                <div class="card__body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card__title mb-1">${item.name}</h6>
                        <p class="text-muted mb-0">$${item.price.toFixed(2)}</p>
                    </div>
                    <div class="item-quantity-controls d-flex align-items-center gap-2">
                        <button class="btn btn--sm btn--secondary quantity-decrease" data-item-id="${item.id}">-</button>
                        <span class="quantity-display">${item.quantity}</span>
                        <button class="btn btn--sm btn--primary quantity-increase" data-item-id="${item.id}">+</button>
                        <button class="btn btn--danger btn--sm remove-from-cart-btn" data-item-id="${item.id}">Remove</button>
                    </div>
                </div>
            `;
            cartItemsList.appendChild(cartItemCard);
            total += item.price * item.quantity;
        });

        cartTotalSpan.textContent = total.toFixed(2);
        attachCartItemListeners(); // Re-attach listeners after updating cart display
    }

    function attachCartItemListeners() {
        document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
            button.onclick = function() {
                const itemIdToRemove = this.dataset.itemId;
                cart = cart.filter(item => item.id !== itemIdToRemove);
                updateCartDisplay();
            };
        });

        document.querySelectorAll('.quantity-increase').forEach(button => {
            button.onclick = function() {
                const itemIdToIncrease = this.dataset.itemId;
                const item = cart.find(item => item.id === itemIdToIncrease);
                if (item) {
                    item.quantity += 1;
                    updateCartDisplay();
                }
            };
        });

        document.querySelectorAll('.quantity-decrease').forEach(button => {
            button.onclick = function() {
                const itemIdToDecrease = this.dataset.itemId;
                const item = cart.find(item => item.id === itemIdToDecrease);
                if (item) {
                    item.quantity -= 1;
                    if (item.quantity <= 0) {
                        cart = cart.filter(i => i.id !== itemIdToDecrease); // Remove if quantity is 0 or less
                    }
                    updateCartDisplay();
                }
            };
        });
    }

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            const itemPrice = parseFloat(this.dataset.itemPrice);

            // Check if item already exists in cart
            const existingItem = cart.find(item => item.id === itemId);

            if (existingItem) {
                existingItem.quantity += 1; // Increment quantity
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    quantity: 1
                });
            }
            updateCartDisplay();
        });
    });

    // Edit Item Modal functionality
    editItemButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            editItemIdInput.value = itemId; // Set the hidden ID field

            // Fetch item data via AJAX
            fetch(`ajax/get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.item;
                        editItemNameInput.value = item.name;
                        editItemPriceInput.value = item.price;
                        editItemQuantityInput.value = item.quantity;
                        editItemUnitInput.value = item.unit;
                        editItemBarcodeInput.value = item.barcode;
                        // Select the correct category in the dropdown
                        if (editItemCategorySelect) {
                            // Clear existing options first if they are not static
                            // For dynamic options, you might need to fetch categories first
                            // For now, assume options are pre-populated or handle dynamically
                            Array.from(editItemCategorySelect.options).forEach(option => {
                                if (option.value == item.category_id) {
                                    option.selected = true;
                                } else {
                                    option.selected = false;
                                }
                            });
                        }
                        editItemMessage.textContent = ''; // Clear any previous messages
                        editItemModal.style.display = 'flex'; // Show the modal
                    } else {
                        editItemMessage.textContent = data.message || 'Failed to fetch item data.';
                        editItemMessage.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Error fetching item data:', error);
                    editItemMessage.textContent = 'An error occurred while fetching item data.';
                    editItemMessage.style.color = 'red';
                });
        });
    });

    // Close modal when close button is clicked
    if (closeEditItemModal) {
        closeEditItemModal.onclick = function() {
            editItemModal.style.display = 'none';
        }
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == editItemModal) {
            editItemModal.style.display = 'none';
        }
    }

    // Handle Edit Item Form Submission
    if (editItemForm) {
        editItemForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(editItemForm);

            fetch('ajax/update_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    editItemMessage.textContent = data.message;
                    editItemMessage.style.color = 'green';
                    // Optionally, refresh the item list or update the specific item in the table
                    // For now, we'll just close the modal and let the user refresh the page if needed.
                    // A more robust solution would involve re-fetching the item list or updating the DOM directly.
                    setTimeout(() => {
                        editItemModal.style.display = 'none';
                        // Reload the page to reflect changes, or implement dynamic table update
                        location.reload();
                    }, 1000);
                } else {
                    editItemMessage.textContent = data.message || 'Failed to update item.';
                    editItemMessage.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Error updating item:', error);
                editItemMessage.textContent = 'An error occurred while updating item.';
                editItemMessage.style.color = 'red';
            });
        });
    }

    // Initial display update in case of pre-loaded items (though none for now)
    updateCartDisplay();
});
</script>
