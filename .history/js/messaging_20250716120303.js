document.addEventListener('DOMContentLoaded', function() {
    // Elements for SMS Sending
    const sendLowStockSmsBtn = document.getElementById('sendLowStockSmsBtn');
    const smsRecipientSelect = document.getElementById('smsRecipientSelect');
    const smsStatusMessageDiv = document.getElementById('smsStatusMessage');

    // Elements for Recipient Management
    const addRecipientForm = document.getElementById('addRecipientForm');
    const newRecipientNameInput = document.getElementById('newRecipientName');
    const newRecipientPhoneNumberInput = document.getElementById('newRecipientPhoneNumber');
    const addRecipientStatusMessageDiv = document.getElementById('addRecipientStatusMessage');
    const recipientsTableBody = document.getElementById('recipientsTableBody');

    // --- SMS Sending Logic ---
    if (sendLowStockSmsBtn) {
        sendLowStockSmsBtn.addEventListener('click', function() {
            const recipientId = smsRecipientSelect.value;

            if (!recipientId) {
                displayStatusMessage(smsStatusMessageDiv, 'Please select a recipient.', 'error');
                return;
            }

            displayStatusMessage(smsStatusMessageDiv, 'Sending SMS...', 'info');
            sendLowStockSmsBtn.disabled = true;

            fetch('ajax/send_low_stock_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'recipient_id=' + encodeURIComponent(recipientId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStatusMessage(smsStatusMessageDiv, data.message, 'success');
                } else {
                    displayStatusMessage(smsStatusMessageDiv, data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayStatusMessage(smsStatusMessageDiv, 'An error occurred while sending the SMS.', 'error');
            })
            .finally(() => {
                sendLowStockSmsBtn.disabled = false;
            });
        });
    }

    // --- Recipient Management Logic ---
    if (addRecipientForm) {
        addRecipientForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const name = newRecipientNameInput.value.trim();
            const phoneNumber = newRecipientPhoneNumberInput.value.trim();

            if (!name || !phoneNumber) {
                displayStatusMessage(addRecipientStatusMessageDiv, 'Both name and phone number are required.', 'error');
                return;
            }
            if (!/^\+?[0-9]{10,15}$/.test(phoneNumber)) {
                displayStatusMessage(addRecipientStatusMessageDiv, 'Invalid phone number format. Please include country code (e.g., +1234567890).', 'error');
                return;
            }

            displayStatusMessage(addRecipientStatusMessageDiv, 'Adding recipient...', 'info');
            
            fetch('ajax/add_sms_recipient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'name=' + encodeURIComponent(name) + '&phone_number=' + encodeURIComponent(phoneNumber)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStatusMessage(addRecipientStatusMessageDiv, data.message, 'success');
                    newRecipientNameInput.value = ''; // Clear form
                    newRecipientPhoneNumberInput.value = '';
                    loadRecipients(); // Reload recipients list
                } else {
                    displayStatusMessage(addRecipientStatusMessageDiv, data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayStatusMessage(addRecipientStatusMessageDiv, 'An error occurred while adding the recipient.', 'error');
            });
        });
    }

    // Delegate event listener for delete buttons
    if (recipientsTableBody) {
        recipientsTableBody.addEventListener('click', function(event) {
            if (event.target.classList.contains('delete-recipient-btn')) {
                const recipientId = event.target.dataset.id;
                if (confirm('Are you sure you want to delete this recipient?')) {
                    deleteRecipient(recipientId);
                }
            }
        });
    }

    function loadRecipients() {
        fetch('ajax/get_sms_recipients.php')
            .then(response => response.json())
            .then(data => {
                recipientsTableBody.innerHTML = ''; // Clear current table
                smsRecipientSelect.innerHTML = '<option value="">Select a recipient</option>'; // Clear current dropdown

                if (data.success && data.recipients.length > 0) {
                    data.recipients.forEach(recipient => {
                        // Populate table
                        const row = recipientsTableBody.insertRow();
                        row.innerHTML = `
                            <td>${recipient.name}</td>
                            <td>${recipient.phone_number}</td>
                            <td><button class="btn btn--danger btn--sm delete-recipient-btn" data-id="${recipient.id}">Delete</button></td>
                        `;

                        // Populate dropdown
                        const option = document.createElement('option');
                        option.value = recipient.id;
                        option.textContent = `${recipient.name} (${recipient.phone_number})`;
                        smsRecipientSelect.appendChild(option);
                    });
                } else {
                    recipientsTableBody.innerHTML = '<tr><td colspan="3">No recipients added yet.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading recipients:', error);
                recipientsTableBody.innerHTML = '<tr><td colspan="3" style="color: red;">Failed to load recipients. Please check your server connection.</td></tr>';
            });
    }

    function deleteRecipient(id) {
        fetch('ajax/delete_sms_recipient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStatusMessage(addRecipientStatusMessageDiv, data.message, 'success');
                loadRecipients(); // Reload recipients list
            } else {
                displayStatusMessage(addRecipientStatusMessageDiv, data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting recipient:', error);
            displayStatusMessage(addRecipientStatusMessageDiv, 'An error occurred while deleting the recipient.', 'error');
        });
    }

    // --- Utility Function for Status Messages ---
    function displayStatusMessage(element, message, type) {
        element.textContent = message;
        element.style.display = 'block';
        element.style.color = ''; // Reset color
        element.style.backgroundColor = ''; // Reset background
        element.style.padding = '10px';
        element.style.border = '1px solid';
        element.style.borderRadius = '5px';

        if (type === 'success') {
            element.style.color = 'green';
            element.style.backgroundColor = '#e6ffe6';
            element.style.borderColor = 'green';
        } else if (type === 'error') {
            element.style.color = 'red';
            element.style.backgroundColor = '#ffe6e6';
            element.style.borderColor = 'red';
        } else if (type === 'info') {
            element.style.color = 'blue';
            element.style.backgroundColor = '#e6f2ff';
            element.style.borderColor = 'blue';
        }
    }

    // Initial load of recipients when the page loads
    loadRecipients();
});
