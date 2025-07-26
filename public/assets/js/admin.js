/**
 * Admin Dashboard JavaScript
 * Common functionality for admin interface
 */

// Global admin object
window.Admin = {
    // Configuration
    config: {
        confirmDelete: 'Bạn có chắc chắn muốn xóa?',
        confirmBulkDelete: 'Bạn có chắc chắn muốn xóa {count} mục đã chọn?',
        loadingText: 'Đang xử lý...',
        errorText: 'Có lỗi xảy ra, vui lòng thử lại.',
        successText: 'Thao tác thành công!'
    },

    // Initialize admin functionality
    init: function() {
        this.initTooltips();
        this.initConfirmDialogs();
        this.initFormValidation();
        this.initTableFeatures();
        this.initFileUploads();
        this.initAjaxForms();
        this.initSidebar();
    },

    // Initialize Bootstrap tooltips
    initTooltips: function() {
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },

    // Initialize confirmation dialogs
    initConfirmDialogs: function() {
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm]')) {
                const message = e.target.getAttribute('data-confirm') || Admin.config.confirmDelete;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    },

    // Initialize form validation
    initFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },

    // Initialize table features
    initTableFeatures: function() {
        // Sortable headers
        document.querySelectorAll('.sortable').forEach(function(header) {
            header.addEventListener('click', function() {
                Admin.sortTable(this);
            });
        });

        // Select all checkboxes
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                Admin.updateBulkActions();
            });
        }

        // Individual checkboxes
        document.querySelectorAll('.row-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                Admin.updateBulkActions();
            });
        });
    },

    // Sort table by column
    sortTable: function(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = !header.classList.contains('sort-asc');

        // Sort rows
        rows.sort(function(a, b) {
            const aText = a.children[columnIndex].textContent.trim();
            const bText = b.children[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aText.localeCompare(bText, 'vi', { numeric: true });
            } else {
                return bText.localeCompare(aText, 'vi', { numeric: true });
            }
        });

        // Update DOM
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        // Update sort indicators
        table.querySelectorAll('.sortable').forEach(function(h) {
            h.classList.remove('sort-asc', 'sort-desc');
        });
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    },

    // Update bulk actions visibility
    updateBulkActions: function() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const bulkActions = document.querySelector('.bulk-actions');
        
        if (bulkActions) {
            bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
        }

        // Update select all checkbox state
        const selectAllCheckbox = document.getElementById('selectAll');
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        
        if (selectAllCheckbox && allCheckboxes.length > 0) {
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allCheckboxes.length;
            selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length;
        }
    },

    // Initialize file upload previews
    initFileUploads: function() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
            input.addEventListener('change', function() {
                Admin.previewFile(this);
            });
        });
    },

    // Preview uploaded file
    previewFile: function(input) {
        const file = input.files[0];
        const previewContainer = document.getElementById(input.id + '_preview');
        
        if (!file || !previewContainer) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const fileType = file.type.split('/')[0];
            let previewHTML = '';

            switch (fileType) {
                case 'image':
                    previewHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">`;
                    break;
                case 'audio':
                    previewHTML = `<audio controls class="w-100"><source src="${e.target.result}"></audio>`;
                    break;
                case 'video':
                    previewHTML = `<video controls class="w-100" style="max-height: 300px;"><source src="${e.target.result}"></video>`;
                    break;
                default:
                    previewHTML = `<p class="text-muted">File: ${file.name}</p>`;
            }

            previewContainer.innerHTML = previewHTML;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    },

    // Initialize AJAX forms
    initAjaxForms: function() {
        document.querySelectorAll('.ajax-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Admin.submitAjaxForm(this);
            });
        });
    },

    // Submit form via AJAX
    submitAjaxForm: function(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.textContent;

        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = Admin.config.loadingText;

        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Admin.showToast(data.message || Admin.config.successText, 'success');
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                Admin.showToast(data.message || Admin.config.errorText, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Admin.showToast(Admin.config.errorText, 'error');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    },

    // Initialize sidebar toggle
    initSidebar: function() {
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    },

    // Show toast notification
    showToast: function(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        // Add to toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);

        // Show toast
        if (typeof bootstrap !== 'undefined') {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
    },

    // Utility functions
    utils: {
        // Format file size
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        // Format date
        formatDate: function(date) {
            return new Date(date).toLocaleDateString('vi-VN');
        },

        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Admin.init();
});

// Export for use in other scripts
window.Admin = Admin;
