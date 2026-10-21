/**
 * Smart Complaint Management System
 * Main Frontend JavaScript (Form Validation, Sidebar Toggle, Confirmations, Image Preview)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Dashboard Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('dashboardSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });
    }

    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    // 2. Public Navbar Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const publicNavLinks = document.getElementById('publicNavLinks');

    if (mobileMenuToggle && publicNavLinks) {
        mobileMenuToggle.addEventListener('click', function () {
            publicNavLinks.classList.toggle('show');
        });
    }

    // 3. Dismissible Alert Boxes
    const alertCloseBtns = document.querySelectorAll('.alert-close');
    alertCloseBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const alertBox = btn.closest('.alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(function () {
                    alertBox.remove();
                }, 200);
            }
        });
    });

    // Auto-dismiss success alerts after 6 seconds
    const successAlerts = document.querySelectorAll('.alert-success[data-auto-dismiss="true"]');
    successAlerts.forEach(function (alertBox) {
        setTimeout(function () {
            alertBox.style.transition = 'opacity 0.4s ease';
            alertBox.style.opacity = '0';
            setTimeout(function () {
                alertBox.remove();
            }, 400);
        }, 6000);
    });

    // 4. Image Upload Live Preview & Client-side Size/Type Validation
    const imageInput = document.getElementById('complaintImageInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreviewElement = document.getElementById('imagePreviewElement');
    const imageErrorElement = document.getElementById('imageUploadError');

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            if (imageErrorElement) {
                imageErrorElement.textContent = '';
            }
            const file = this.files[0];
            if (!file) {
                if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            const maxSize = 3 * 1024 * 1024; // 3MB

            if (!allowedTypes.includes(file.type)) {
                if (imageErrorElement) {
                    imageErrorElement.textContent = 'Invalid file type. Please select a JPG, PNG, WEBP, or GIF image.';
                }
                this.value = '';
                if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
                return;
            }

            if (file.size > maxSize) {
                if (imageErrorElement) {
                    imageErrorElement.textContent = 'File is too large (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB). Maximum allowed size is 3 MB.';
                }
                this.value = '';
                if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
                return;
            }

            if (imagePreviewContainer && imagePreviewElement) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreviewElement.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 5. Confirmation Dialogs for Sensitive Actions (Withdraw, Deactivate, etc.)
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 6. Client-Side Registration Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const errorBox = document.getElementById('clientValidationError');

            if (password && confirmPassword) {
                if (password.value.length < 6) {
                    e.preventDefault();
                    if (errorBox) {
                        errorBox.style.display = 'flex';
                        errorBox.querySelector('span').textContent = 'Password must be at least 6 characters long.';
                    }
                    return;
                }
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    if (errorBox) {
                        errorBox.style.display = 'flex';
                        errorBox.querySelector('span').textContent = 'Passwords do not match. Please verify both password fields.';
                    }
                    return;
                }
            }
        });
    }

    // 7. Quick Table Search Filter (Client-Side Instant Search helper)
    const quickSearchInput = document.getElementById('quickTableSearch');
    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function () {
            const filter = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.searchable-table tbody tr');
            rows.forEach(function (row) {
                if (row.classList.contains('empty-row')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
