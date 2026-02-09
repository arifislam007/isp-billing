/**
 * ISP Billing System JavaScript
 */

// Utility Functions
const App = {
    // Show confirmation dialog
    confirmDelete: function(message = 'Are you sure you want to delete this item?') {
        return Swal.fire({
            title: 'Confirm Delete',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        });
    },
    
    // Show success message
    showSuccess: function(message, title = 'Success!') {
        Swal.fire({
            title: title,
            text: message,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    },
    
    // Show error message
    showError: function(message, title = 'Error!') {
        Swal.fire({
            title: title,
            text: message,
            icon: 'error'
        });
    },
    
    // Show loading spinner
    showLoading: function(message = 'Loading...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },
    
    // Close loading spinner
    closeLoading: function() {
        Swal.close();
    },
    
    // Format currency
    formatCurrency: function(amount) {
        return '৳ ' + Number(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    },
    
    // AJAX POST request
    post: function(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        }).then(response => response.json());
    },
    
    // AJAX form submission
    submitForm: function(formId, url, successMessage = 'Operation completed successfully!') {
        const form = document.getElementById(formId);
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            App.showLoading('Processing...');
            
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            App.post(url, data)
                .then(response => {
                    App.closeLoading();
                    if (response.success) {
                        App.showSuccess(successMessage);
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 1500);
                        }
                        if (response.reset) {
                            form.reset();
                        }
                    } else {
                        App.showError(response.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    App.closeLoading();
                    App.showError('Network error occurred');
                    console.error(error);
                });
        });
    },
    
    // Delete item via AJAX
    deleteItem: function(url, id, message = 'Item deleted successfully!') {
        App.confirmDelete().then(result => {
            if (result.isConfirmed) {
                App.showLoading('Deleting...');
                App.post(url, { id: id })
                    .then(response => {
                        App.closeLoading();
                        if (response.success) {
                            App.showSuccess(message);
                            if (response.redirect) {
                                setTimeout(() => {
                                    window.location.href = response.redirect;
                                }, 1500);
                            } else {
                                // Remove row from table
                                const row = document.querySelector(`tr[data-id="${id}"]`);
                                if (row) {
                                    row.remove();
                                }
                            }
                        } else {
                            App.showError(response.message || 'Delete failed');
                        }
                    })
                    .catch(error => {
                        App.closeLoading();
                        App.showError('Network error occurred');
                    });
            }
        });
    },
    
    // Initialize DataTable
    initDataTable: function(tableId, options = {}) {
        if (typeof $.fn.DataTable !== 'undefined') {
            return $(`#${tableId}`).DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: '<i class="fas fa-search"></i> Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries available',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        previous: '<i class="fas fa-angle-left"></i>'
                    }
                },
                ...options
            });
        }
    },
    
    // Initialize select2
    initSelect2: function(selector) {
        if (typeof $.fn.select2 !== 'undefined') {
            $(selector).select2({
                theme: 'bootstrap-5',
                placeholder: 'Select an option'
            });
        }
    }
};

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirm before leaving page with unsaved changes
    const forms = document.querySelectorAll('form[data-confirm-leave]');
    forms.forEach(form => {
        let unsavedChanges = false;
        
        form.addEventListener('input', () => {
            unsavedChanges = true;
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (unsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', () => {
            unsavedChanges = false;
        });
    });
    
    // Add loading state to buttons
    const buttons = document.querySelectorAll('.btn-loading');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `<span class="loading-spinner me-2"></span>${originalText}`;
            
            setTimeout(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            }, 2000);
        });
    });
    
    // Print button functionality
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Dynamic table row click
    const tableRows = document.querySelectorAll('.table-clickable tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (!e.target.closest('a, button, .no-click')) {
                const link = this.dataset.link;
                if (link) {
                    window.location.href = link;
                }
            }
        });
    });
});

// Phone number formatting
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        } else {
            value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
        }
    }
    input.value = value;
}

// Export table to CSV
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
