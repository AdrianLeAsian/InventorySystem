document.addEventListener('DOMContentLoaded', function() {
    const sendLowStockSmsBtn = document.getElementById('sendLowStockSmsBtn');
    const recipientPhoneNumberInput = document.getElementById('recipientPhoneNumber');
    const smsStatusMessageDiv = document.getElementById('smsStatusMessage');

    if (sendLowStockSmsBtn) {
        sendLowStockSmsBtn.addEventListener('click', function() {
            const recipientPhoneNumber = recipientPhoneNumberInput.value.trim();

            // Basic client-side validation
            if (!recipientPhoneNumber) {
                displayStatusMessage('Please enter a recipient phone number.', 'error');
                return;
            }
            if (!/^\+?[0-9]{10,15}$/.test(recipientPhoneNumber)) {
                displayStatusMessage('Invalid phone number format. Please include country code (e.g., +1234567890).', 'error');
                return;
            }

            displayStatusMessage('Sending SMS...', 'info');
            sendLowStockSmsBtn.disabled = true; // Disable button to prevent multiple clicks

            fetch('ajax/send_low_stock_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'recipient_phone_number=' + encodeURIComponent(recipientPhoneNumber)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStatusMessage(data.message, 'success');
                } else {
                    displayStatusMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayStatusMessage('An error occurred while sending the SMS.', 'error');
            })
            .finally(() => {
                sendLowStockSmsBtn.disabled = false; // Re-enable button
            });
        });
    }

    function displayStatusMessage(message, type) {
        smsStatusMessageDiv.textContent = message;
        smsStatusMessageDiv.style.display = 'block';
        smsStatusMessageDiv.style.color = ''; // Reset color
        smsStatusMessageDiv.style.backgroundColor = ''; // Reset background

        if (type === 'success') {
            smsStatusMessageDiv.style.color = 'green';
            smsStatusMessageDiv.style.backgroundColor = '#e6ffe6';
            smsStatusMessageDiv.style.padding = '10px';
            smsStatusMessageDiv.style.border = '1px solid green';
            smsStatusMessageDiv.style.borderRadius = '5px';
        } else if (type === 'error') {
            smsStatusMessageDiv.style.color = 'red';
            smsStatusMessageDiv.style.backgroundColor = '#ffe6e6';
            smsStatusMessageDiv.style.padding = '10px';
            smsStatusMessageDiv.style.border = '1px solid red';
            smsStatusMessageDiv.style.borderRadius = '5px';
        } else if (type === 'info') {
            smsStatusMessageDiv.style.color = 'blue';
            smsStatusMessageDiv.style.backgroundColor = '#e6f2ff';
            smsStatusMessageDiv.style.padding = '10px';
            smsStatusMessageDiv.style.border = '1px solid blue';
            smsStatusMessageDiv.style.borderRadius = '5px';
        }
    }
});
