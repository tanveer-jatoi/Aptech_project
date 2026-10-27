<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: contact.php
 * Description: Public Inquiries, Pediatric Support & Emergency Helpdesk
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$errors = [];
$successSent = false;
$formData = [
    'name'    => '',
    'email'   => '',
    'phone'   => '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
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

        if (empty($name))    $errors[] = 'Your full name is required.';
        if (empty($email))   $errors[] = 'Your email address is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address format.';
        if (empty($subject)) $errors[] = 'Please select an inquiry topic.';
        if (empty($message)) $errors[] = 'Please enter your message.';

        if (empty($errors)) {
            // Set success flash toast
            setFlash('success', 'Thank you, ' . $name . '! Your inquiry has been received. Our pediatric support team will contact you shortly.');
            header('Location: ' . url('contact.php'));
            exit;
        }
    }
}

$pageTitle = 'Contact & Support Helpdesk';
$pageDescription = 'Get in touch with pediatric health coordinators, submit inquiries, or request healthcare assistance.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 750px; margin: 0 auto 2.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">We Are Here to Assist</span>
        <h1 style="font-size: 2.25rem; margin-bottom: 0.75rem;">Contact & Helpdesk</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin: 0;">
            Have questions about your child's immunization schedule, hospital appointments, or clinic enrollment? Reach our coordination team below.
        </p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert" style="max-width: 900px; margin: 0 auto 1.5rem;">
            <div>
                <strong>Please resolve the following:</strong>
                <ul style="margin: 0.25rem 0 0 1.2rem;">
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
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--primary-dark);">&#128222; National Helpline</h3>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem; color: var(--text-muted);">
                    <strong>Toll-Free Support:</strong><br>
                    1-800-VMS-KIDS (867-5437)<br>
                    <small>Mon &ndash; Fri: 8:00 AM &ndash; 6:00 PM</small>
                </p>
                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    <strong>Emergency Hotline:</strong><br>
                    +1-555-KIDS-911 (24/7 Response)
                </p>
            </div>

            <div class="card" style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem; color: var(--primary-dark);">&#9993; Email Contacts</h3>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.5rem; color: var(--text-muted);">
                    <strong>Parent Support:</strong><br>
                    support@vms.health.local
                </p>
                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    <strong>Clinic Partnerships:</strong><br>
                    providers@vms.health.local
                </p>
            </div>

            <div class="card">
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary-dark);">&#127973; Clinic Partnering</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Are you a licensed hospital, pediatric center, or health clinic?
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

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Your Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="e.g. Sarah Jenkins" value="<?= e($formData['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="e.g. sarah@example.com" value="<?= e($formData['email']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+1-555-0123" value="<?= e($formData['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label required">Inquiry Topic</label>
                        <select id="subject" name="subject" class="form-select" required>
                            <option value="">Select Topic</option>
                            <option value="Appointment Booking" <?= $formData['subject'] === 'Appointment Booking' ? 'selected' : ''; ?>>Appointment Booking Assistance</option>
                            <option value="Vaccine Schedule Question" <?= $formData['subject'] === 'Vaccine Schedule Question' ? 'selected' : ''; ?>>Child Vaccination Schedule Question</option>
                            <option value="Hospital Enrollment" <?= $formData['subject'] === 'Hospital Enrollment' ? 'selected' : ''; ?>>Healthcare Facility Enrollment</option>
                            <option value="Technical Issue" <?= $formData['subject'] === 'Technical Issue' ? 'selected' : ''; ?>>Technical / Login Issue</option>
                            <option value="General Feedback" <?= $formData['subject'] === 'General Feedback' ? 'selected' : ''; ?>>General Feedback & Inquiries</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label required">Your Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" 
                              placeholder="Please describe your question or issue in detail..." required><?= e($formData['message']); ?></textarea>
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
