<div class="page">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Messaging Page</h2>
        </div>
        <div class="card__body">
            <div class="card mb-4">
                <div class="card__header">
                    <h3 class="card__title">Manage SMS Recipients</h3>
                </div>
                <div class="card__body">
                    <form class="form" id="addRecipientForm">
                        <div class="form__row">
                            <div class="form__group col-6">
                                <label class="form__label" for="newRecipientName">Recipient Name</label>
                                <input type="text" id="newRecipientName" class="form__input" placeholder="e.g., Warehouse Manager" required>
                            </div>
                            <div class="form__group col-6">
                                <label class="form__label" for="newRecipientPhoneNumber">Phone Number</label>
                                <input type="tel" id="newRecipientPhoneNumber" class="form__input" placeholder="e.g., +1234567890" pattern="^\+?[0-9]{10,15}$" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn--primary">Add Recipient</button>
                        </div>
                    </form>
                    <div id="addRecipientStatusMessage" class="mt-3" style="display: none;"></div>

                    <h4 class="mt-4">Existing Recipients</h4>
                    <div class="table-responsive">
                        <table class="table table--striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recipientsTableBody">
                                <!-- Recipients will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">Send Low Stock SMS</h3>
                </div>
                <div class="card__body">
                    <div class="form__group">
                        <label class="form__label" for="smsRecipientSelect">Select Recipient</label>
                        <select id="smsRecipientSelect" class="form__input" required>
                            <option value="">Select a recipient</option>
                            <!-- Options will be loaded here by JavaScript -->
                        </select>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" id="sendLowStockSmsBtn" class="btn btn--primary">Send Low Stock Alert</button>
                    </div>
                    <div id="smsStatusMessage" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/messaging.js"></script>
