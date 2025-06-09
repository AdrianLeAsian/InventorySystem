<div class="card">
    <div class="card__header">
        <h1 class="card__title">Cart</h2>
    </div>
    <div class="card__body">
        <p>Your cart items will appear here.</p>
        <!-- Cart items will be dynamically added here -->
        <div id="cart-items" class="cart-items-container">
            <!-- Example: -->
            <!-- <div class="card cart-item-card mb-2">...</div> -->
        </div>
        <div class="cart-summary mt-3">
            <h5>Total: $<span id="cart-total">0.00</span></h5>
            <button class="btn btn--primary btn--block mt-2" id="checkout-btn">Checkout</button>
        </div>
    </div>
</div>
