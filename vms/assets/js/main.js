/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: assets/js/main.js
 * Description: Core Vanilla JavaScript Interactions & Form Validation
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    // ------------------------------------------------------------------------
    // 1. Mobile Navigation Menu Toggle
    // ------------------------------------------------------------------------
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', !isExpanded);
            navMenu.classList.toggle('is-active');
        });

        // Close navigation menu when clicking outside
        document.addEventListener('click', function (event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('is-active');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ------------------------------------------------------------------------
    // 2. Flash Success Toast Auto-Dismissal
    // ------------------------------------------------------------------------
    const toast = document.getElementById('vms-toast');
    if (toast) {
        // Automatically hide toast after 4.5 seconds
        setTimeout(function () {
            dismissVmsToast();
        }, 4500);
    }

    // ------------------------------------------------------------------------
    // 3. Dual-Tab Registration Switcher (Parent vs Hospital)
    // ------------------------------------------------------------------------
    const tabParentBtn = document.getElementById('tabParentBtn');
    const tabHospitalBtn = document.getElementById('tabHospitalBtn');
    const formParent = document.getElementById('registerParentForm');
    const formHospital = document.getElementById('registerHospitalForm');

    if (tabParentBtn && tabHospitalBtn && formParent && formHospital) {
        tabParentBtn.addEventListener('click', function () {
            tabParentBtn.classList.add('is-active');
            tabParentBtn.setAttribute('aria-selected', 'true');
            tabHospitalBtn.classList.remove('is-active');
            tabHospitalBtn.setAttribute('aria-selected', 'false');

            formParent.style.display = 'block';
            formHospital.style.display = 'none';
        });

        tabHospitalBtn.addEventListener('click', function () {
            tabHospitalBtn.classList.add('is-active');
            tabHospitalBtn.setAttribute('aria-selected', 'true');
            tabParentBtn.classList.remove('is-active');
            tabParentBtn.setAttribute('aria-selected', 'false');

            formHospital.style.display = 'block';
            formParent.style.display = 'none';
        });
    }
});

/**
 * Dismisses the active flash notification toast with a smooth slide-out animation.
 */
function dismissVmsToast() {
    const toast = document.getElementById('vms-toast');
    if (!toast) return;

    toast.classList.add('is-hiding');
    setTimeout(function () {
        const container = document.getElementById('vms-toast-container');
        if (container) {
            container.remove();
        } else {
            toast.remove();
        }
    }, 320);
}

/**
 * Validates password match on registration forms before sending POST request.
 *
 * @param {HTMLFormElement} form Form element being submitted
 * @returns {boolean} True if valid, false to abort submission
 */
function validateRegistrationForm(form) {
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="password_confirm"]');
    const errorContainer = form.querySelector('.form-password-error');

    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            if (errorContainer) {
                errorContainer.textContent = 'Passwords do not match. Please re-enter.';
                errorContainer.style.display = 'block';
            } else {
                alert('Passwords do not match. Please re-enter.');
            }
            confirmPassword.focus();
            return false;
        }
        if (password.value.length < 6) {
            if (errorContainer) {
                errorContainer.textContent = 'Password must be at least 6 characters long.';
                errorContainer.style.display = 'block';
            } else {
                alert('Password must be at least 6 characters long.');
            }
            password.focus();
            return false;
        }
    }
    return true;
}
