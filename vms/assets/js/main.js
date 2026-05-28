/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: assets/js/main.js
 * Description: Core Vanilla JavaScript Interactions, Consent & Validation
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

    // ------------------------------------------------------------------------
    // 4. Cookie Consent Management
    // ------------------------------------------------------------------------
    initCookieConsent();

    // ------------------------------------------------------------------------
    // 5. Device Date & Time Verification Shield (SSL & Medical Timing Defense)
    // ------------------------------------------------------------------------
    initDateTimeShield();

    // ------------------------------------------------------------------------
    // 6. Prevent Duplicate Form Submissions
    // ------------------------------------------------------------------------
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            // Check if already submitting
            if (form.getAttribute('data-submitting') === 'true') {
                e.preventDefault();
                return false;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !form.noValidate && form.checkValidity()) {
                form.setAttribute('data-submitting', 'true');
                submitBtn.setAttribute('disabled', 'disabled');
                const origText = submitBtn.innerHTML;
                submitBtn.innerHTML = 'Processing...';
                // Reset after 8s fallback in case of validation interruption
                setTimeout(function () {
                    form.removeAttribute('data-submitting');
                    submitBtn.removeAttribute('disabled');
                    submitBtn.innerHTML = origText;
                }, 8000);
            }
        });
    });
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
 * Initializes the Cookie Consent Banner with Accept All & Essential-Only rejection.
 */
function initCookieConsent() {
    const consent = localStorage.getItem('vms_cookie_consent');
    const banner = document.getElementById('vms-cookie-banner');

    if (!consent && banner) {
        banner.style.display = 'block';

        const acceptBtn = document.getElementById('cookie-accept-all');
        const rejectBtn = document.getElementById('cookie-reject');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                localStorage.setItem('vms_cookie_consent', 'all');
                banner.style.display = 'none';
                loadPrivacyAnalytics();
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function () {
                localStorage.setItem('vms_cookie_consent', 'essential');
                banner.style.display = 'none';
            });
        }
    } else if (consent === 'all') {
        loadPrivacyAnalytics();
    }
}

/**
 * Privacy-respecting analytics stub (Runs only when user gave explicit consent).
 * Zero personal data, no third-party tracking, fully privacy-compliant.
 */
function loadPrivacyAnalytics() {
    if (window._vmsAnalyticsLoaded) return;
    window._vmsAnalyticsLoaded = true;

    // Log privacy-safe page view event without personal identifiers
    try {
        const payload = {
            page: window.location.pathname,
            referrer: document.referrer ? (new URL(document.referrer)).hostname : 'direct',
            timestamp: new Date().toISOString()
        };
        // Can be dispatched to a privacy-friendly endpoint if configured
        if (window.console && window.console.info) {
            console.info('[VMS Privacy Analytics] Page view tracked with consent:', payload.page);
        }
    } catch (e) {
        // Safe failover
    }
}

/**
 * Validates whether a phone number follows realistic Pakistani formats (+92 or 03xx).
 *
 * @param {string} phone
 * @returns {boolean}
 */
function isValidPakistaniPhoneJs(phone) {
    if (!phone) return true; // Let required validator handle empty fields
    const cleaned = phone.replace(/[\s\-\(\)]/g, '');
    const regex = /^((\+92|0092|0)?(3[0-9]{2}|[2456789][0-9]))[0-9]{7,8}$/;
    return regex.test(cleaned);
}

/**
 * Validates registration forms before sending POST request.
 *
 * @param {HTMLFormElement} form Form element being submitted
 * @returns {boolean} True if valid, false to abort submission
 */
function validateRegistrationForm(form) {
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="password_confirm"]');
    const phoneInput = form.querySelector('input[name="parent_phone"]') || form.querySelector('input[name="hospital_phone"]');
    const errorContainer = form.querySelector('.form-password-error');

    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            showFormError(errorContainer, 'Passwords do not match. Please re-enter.');
            confirmPassword.focus();
            return false;
        }
        if (password.value.length < 6) {
            showFormError(errorContainer, 'Password must be at least 6 characters long.');
            password.focus();
            return false;
        }
    }

    if (phoneInput && phoneInput.value.trim() !== '') {
        if (!isValidPakistaniPhoneJs(phoneInput.value)) {
            showFormError(errorContainer, 'Please enter a valid Pakistani phone number (e.g. 0300-1234567 or +92 300 1234567).');
            phoneInput.focus();
            return false;
        }
    }

    if (errorContainer) {
        errorContainer.style.display = 'none';
    }
    return true;
}

function showFormError(container, message) {
    if (container) {
        container.textContent = message;
        container.style.display = 'block';
    } else {
        alert(message);
    }
}

/**
 * Verifies that the client's device date & time is synchronized with the server.
 * Disallows / warns users when device clock is inaccurate, protecting SSL & appointments.
 */
function initDateTimeShield() {
    const metaServerTime = document.querySelector('meta[name="vms-server-time"]');
    const shield = document.getElementById('vms-datetime-shield');
    if (!metaServerTime || !shield) return;

    const serverSec = parseInt(metaServerTime.getAttribute('content'), 10);
    if (!serverSec) return;

    function verifyClock() {
        const clientMs = Date.now();
        const serverMs = serverSec * 1000;
        const diffMinutes = Math.abs(clientMs - serverMs) / (1000 * 60);

        // Tolerance: 30 minutes (allows minor clock drift, catches dead CMOS batteries, wrong dates, years)
        if (diffMinutes > 30) {
            shield.style.display = 'flex';
            const clientDisplay = document.getElementById('vms-client-time-display');
            if (clientDisplay) {
                const now = new Date();
                clientDisplay.textContent = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString();
            }

            // Disable sensitive submit buttons while clock is out of sync
            document.querySelectorAll('form button[type="submit"]').forEach(function (btn) {
                btn.setAttribute('data-clock-disabled', 'true');
                btn.setAttribute('disabled', 'disabled');
                btn.setAttribute('title', 'Please synchronize your device date and time to enable submission.');
            });
            return false;
        } else {
            shield.style.display = 'none';
            // Re-enable buttons if previously disabled by clock check
            document.querySelectorAll('button[data-clock-disabled="true"]').forEach(function (btn) {
                btn.removeAttribute('data-clock-disabled');
                btn.removeAttribute('disabled');
                btn.removeAttribute('title');
            });
            return true;
        }
    }

    // Run initial verification
    verifyClock();

    // Re-check button handler
    const recheckBtn = document.getElementById('vms-recheck-clock');
    if (recheckBtn) {
        recheckBtn.addEventListener('click', function () {
            const ok = verifyClock();
            if (ok) {
                alert('Success: Your device clock is now properly synchronized!');
            } else {
                alert('Your device clock is still out of sync. Please check your system date and time settings.');
            }
        });
    }

    // Dismiss / Read-only button
    const dismissBtn = document.getElementById('vms-dismiss-clock-warning');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function () {
            shield.style.display = 'none';
        });
    }
}
