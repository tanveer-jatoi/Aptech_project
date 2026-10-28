<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: contact.php
 * Description: Public Inquiries, Pediatric Support & Emergency Helpdesk (Pakistan)
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$errors = [];
$formData = [
    'name'    => '',
    'email'   => '',
    'phone'   => '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    }
    // 2. Anti-spam Honeypot Check
    elseif (!verifyHoneypot('website_hp')) {
        $errors[] = 'Spam submission detected and blocked.';
    } else {
        // 3. Rate Limiting Check (Max 5 submissions per 10 minutes)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimit = checkRateLimit('contact_' . $clientIp, 5, 600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many requests submitted. Please wait ' . ceil($rateLimit['retry_after'] / 60) . ' minutes before submitting again.';
        } else {
            $name    = sanitizeInput($_POST['name'] ?? '');
            $email   = sanitizeInput($_POST['email'] ?? '');
            $phone   = sanitizeInput($_POST['phone'] ?? '');
            $subject = sanitizeInput($_POST['subject'] ?? '');
            $message = sanitizeInput($_POST['message'] ?? '');

            $formData['name']    = $name;
            $formData['email']   = $email;
            $formData['phone']   = $phone;
            $formData['subject'] = $subject;
            $formData['message'] = $message;

            if (empty($name)) {
                $errors[] = 'Your full name is required.';
            }
            if (empty($email)) {
                $errors[] = 'Your email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address format.';
            }
            if (!empty($phone) && !isValidPakistaniPhone($phone)) {
                $errors[] = 'Please enter a valid Pakistani phone number (e.g. 0300-1234567 or +92 300 1234567).';
            }
            if (empty($subject)) {
                $errors[] = 'Please select an inquiry topic.';
            }
            if (empty($message)) {
                $errors[] = 'Please enter your message.';
            } elseif (strlen($message) < 10) {
                $errors[] = 'Message is too short. Please provide at least 10 characters.';
            }

            if (empty($errors)) {
                recordRateLimitAttempt('contact_' . $clientIp);
                setFlash('success', 'Thank you, ' . $name . '! Your inquiry has been received. Our pediatric support team in Pakistan will contact you shortly.');
                header('Location: ' . url('contact.php'));
                exit;
            }
        }
    }
}

$pageTitle = 'Contact & Support Helpdesk';
$pageDescription = 'Get in touch with pediatric health coordinators, submit inquiries, or request healthcare assistance in Pakistan.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 750px; margin: 0 auto 2.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">We Are Here to Assist</span>
        <h1 style="font-size: 2.25rem; margin-bottom: 0.75rem;">Contact &amp; Helpdesk</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin: 0;">
            Have questions about your child's immunization schedule, hospital appointments, or clinic enrollment in Pakistan? Reach our coordination team below.
        </p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert" style="max-width: 900px; margin: 0 auto 1.5rem;">
            <div>
                <strong>Please resolve the following issues:</strong>
                <ul style="margin: 0.35rem 0 0 1.2rem; line-height: 1.4;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= e($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-sidebar" style="gap: 2rem; max-width: 960px; margin: 0 auto;">
        <!-- Left: Quick Contact Info -->
        <div>
            <div class="card" style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--primary-dark);">National Helpline</h3>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem; color: var(--text-muted);">
                    <strong>Toll-Free Support:</strong><br>
                    0800-867-00 (0800-VMS-00)<br>
                    <small>Mon &ndash; Sat: 8:00 AM &ndash; 6:00 PM PKT</small>
                </p>
                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    <strong>Emergency Hotline:</strong><br>
                    +92 (21) 111-867-725 (24/7 Response)
                </p>
            </div>

            <div class="card" style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem; color: var(--primary-dark);">&#9993; Email Contacts</h3>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.5rem; color: var(--text-muted);">
                    <strong>Parent Support:</strong><br>
                    support@vms.health.local
                </p>
                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    <strong>Hospital Partnerships:</strong><br>
                    providers@vms.health.local
                </p>
            </div>

            <div class="card">
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary-dark);">Clinic Partnering</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Are you a licensed hospital, pediatric center, or health clinic in Pakistan?
                </p>
                <a href="<?= url('register.php'); ?>" class="btn btn-outline btn-sm btn-block">
                    Register Your Medical Center &rarr;
                </a>
            </div>
        </div>

        <!-- Right: Inquiry Form -->
        <div class="card">
            <div class="card-header">
                <h3>Send Us an Inquiry</h3>
            </div>

            <form action="<?= url('contact.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <?= honeypotField('website_hp'); ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Your Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="e.g. Fatima Khan" value="<?= e($formData['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="e.g. fatima.khan@example.com" value="<?= e($formData['email']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="e.g. 0300-1234567" value="<?= e($formData['phone']); ?>"
                               pattern="^((\+92|0092|0)?(3[0-9]{2}|[2456789][0-9]))[- ]?[0-9]{7,8}$"
                               title="Pakistani phone number (e.g. 0300-1234567 or +92 300 1234567)">
                        <small class="form-hint">Format: 0300-1234567 or +92 300 1234567</small>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label required">Inquiry Topic</label>
                        <select id="subject" name="subject" class="form-select" required>
                            <option value="">Select Topic</option>
                            <option value="Appointment Booking" <?= $formData['subject'] === 'Appointment Booking' ? 'selected' : ''; ?>>Appointment Booking Assistance</option>
                            <option value="Vaccine Schedule Question" <?= $formData['subject'] === 'Vaccine Schedule Question' ? 'selected' : ''; ?>>Child Vaccination Schedule Question</option>
                            <option value="Hospital Enrollment" <?= $formData['subject'] === 'Hospital Enrollment' ? 'selected' : ''; ?>>Healthcare Facility Enrollment</option>
                            <option value="Technical Issue" <?= $formData['subject'] === 'Technical Issue' ? 'selected' : ''; ?>>Technical / Login Issue</option>
                            <option value="General Feedback" <?= $formData['subject'] === 'General Feedback' ? 'selected' : ''; ?>>General Feedback &amp; Inquiries</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label required">Your Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" 
                              placeholder="Please describe your question or issue in detail..." required minlength="10"><?= e($formData['message']); ?></textarea>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                        Submit Message &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
