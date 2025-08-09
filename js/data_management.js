document.addEventListener('DOMContentLoaded', function() {
    const exportDataBtn = document.getElementById('exportDataBtn');
    const importDataBtn = document.getElementById('importDataBtn');
    const importCsvFile = document.getElementById('importCsvFile');

    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', function() {
            // Trigger the export PHP script
            window.location.href = 'ajax/export_all_data.php';
        });
    }

    if (importDataBtn) {
        importDataBtn.addEventListener('click', function() {
            importCsvFile.click(); // Trigger the hidden file input click
        });
    }

    const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');
    if (downloadTemplateBtn) {
        downloadTemplateBtn.addEventListener('click', function() {
            window.location.href = 'ajax/export_template.php';
        });
    }

    if (importCsvFile) {
        importCsvFile.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csvContent = e.target.result;
                    uploadCsvData(csvContent);
                };
                reader.readAsText(file);
            }
        });
    }

    function uploadCsvData(csvContent) {
        const formData = new FormData();
        formData.append('csv_data', csvContent);

        fetch('ajax/import_all_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Data imported successfully: ' + data.message);
                // Optionally, refresh the page or update UI
                location.reload();
            } else {
                alert('Error importing data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during import.');
        });
    }
});
