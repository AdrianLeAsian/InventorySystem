<div class="page">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Messaging Page</h2>
        </div>
        <div class="card__body">
            <div class="form__group">
                <label class="form__label" for="recipientPhoneNumber">Recipient Phone Number</label>
                <input type="tel" id="recipientPhoneNumber" class="form__input" placeholder="e.g., +1234567890" pattern="^\+?[0-9]{10,15}$" required>
                <small class="form__text-muted">Enter the phone number including country code (e.g., +1234567890).</small>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="button" id="sendLowStockSmsBtn" class="btn btn--primary">Send Low Stock Alert</button>
            </div>
            <div id="smsStatusMessage" class="mt-3" style="display: none;"></div>
        </div>
    </div>
</div>
<script src="js/messaging.js"></script>
