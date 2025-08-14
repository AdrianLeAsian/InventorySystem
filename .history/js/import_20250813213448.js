$(document).ready(function() {
    loadImportHistory();

    // Function to load import history
    function loadImportHistory() {
        $.ajax({
            url: 'includes/ImportHandler.php?action=get_import_history',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const tableBody = $('#importHistoryTable tbody');
                tableBody.empty(); // Clear existing rows

                if (data.length > 0) {
                    data.forEach(function(record) {
                        let statusClass = '';
                        if (record.status === 'success') {
                            statusClass = 'status-success';
                        } else if (record.status === 'failure') {
                            statusClass = 'status-failure';
                        } else if (record.status === 'partial_success') {
                            statusClass = 'status-partial_success';
                        }

                        let errorsHtml = 'No errors';
                        if (record.errors && record.errors !== '[]') {
                            const errors = JSON.parse(record.errors);
                            if (errors.length > 0) {
                                errorsHtml = `<button class="view-errors-btn" data-errors='${JSON.stringify(errors)}'>View Errors</button>`;
                            }
                        }

                        const row = `
                            <tr>
                                <td>${record.import_date}</td>
                                <td>${record.username}</td>
                                <td>${record.file_name}</td>
                                <td class="${statusClass}">${record.status}</td>
                                <td>${record.summary}</td>
                                <td>${errorsHtml}</td>
                            </tr>
                        `;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append('<tr><td colspan="6">No import history found.</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading import history:", status, error);
                $('#importHistoryTable tbody').empty().append('<tr><td colspan="6">Failed to load import history.</td></tr>');
            }
        });
    }

    // Handle "View Errors" button click
    $(document).on('click', '.view-errors-btn', function() {
        const errorsData = $(this).data('errors');
        let errorDetailsHtml = '';
        if (errorsData && errorsData.length > 0) {
            errorsData.forEach(function(error) {
                errorDetailsHtml += `<p><strong>Row ${error.row}:</strong></p>`;
                if (Array.isArray(error.messages)) {
                    errorDetailsHtml += `<ul>`;
                    error.messages.forEach(msg => {
                        errorDetailsHtml += `<li>${msg}</li>`;
                    });
                    errorDetailsHtml += `</ul>`;
                } else {
                    errorDetailsHtml += `<p>${error.message}</p>`;
                }
            });
        } else {
            errorDetailsHtml = '<p>No detailed errors available.</p>';
        }

        $('#errorModalContent').html(errorDetailsHtml);
        $('#errorModal').css('display', 'block');
    });

    // Close modal when close button is clicked
    $('.close-button').on('click', function() {
        $('#errorModal').css('display', 'none');
    });

    // Close modal when clicking outside of the modal content
    $(window).on('click', function(event) {
        if ($(event.target).is('#errorModal')) {
            $('#errorModal').css('display', 'none');
        }
    });

    // Display import status message if redirected from ImportHandler.php
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const summary = urlParams.get('summary');

    if (status && summary) {
        let messageClass = '';
        if (status === 'success') {
            messageClass = 'status-success';
        } else if (status === 'failure') {
            messageClass = 'status-failure';
        } else if (status === 'partial_success') {
            messageClass = 'status-partial_success';
        }
        const messageHtml = `<div class="import-message ${messageClass}">${decodeURIComponent(summary)}</div>`;
        $('.main-content').prepend(messageHtml);

        // Remove the message after a few seconds
        setTimeout(function() {
            $('.import-message').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000); // Message disappears after 5 seconds

        // Clear URL parameters to prevent re-display on refresh
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
