<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: contact.php
 * Description: Public inquiry form. Messages are now saved in `contact_messages`
 *              (run database/contact_messages.sql once).
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

$topics = [
    'Appointment Booking'      => 'Appointment booking assistance',
    'Vaccine Schedule Question'=> 'Vaccination schedule question',
    'Hospital Enrollment'      => 'Healthcare facility enrollment',
    'Technical Issue'          => 'Technical or login issue',
    'General Feedback'         => 'General feedback and inquiries',
];

$errors   = [];
$formData = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

// Prefill from links such as 404.php -> contact.php?subject=broken-link
$prefill = $_GET['subject'] ?? '';
if ($prefill === 'broken-link') {
    $formData['subject'] = 'Technical Issue';
    $formData['message'] = 'I found a broken link: ';
} elseif ($prefill === 'password') {
    $formData['subject'] = 'Technical Issue';
    $formData['message'] = 'I cannot sign in and need help resetting my password. My account email is: ';
}

/** Trim, drop control characters, cap length (multibyte safe). Escape on OUTPUT with e(), not here. */
function cleanField($value, int $max): string
{
    $value = trim((string)$value);
    $value = preg_replace('/[^\P{C}\n\r\t]+/u', '', $value) ?? '';
    return mb_substr($value, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } elseif (!verifyHoneypot('website_hp')) {
        // Bots get a normal-looking success page and nothing is saved.
        setFlash('success', 'Thank you! Your message has been received.');
        header('Location: ' . url('contact.php'));
        exit;
    } else {
        $rateLimit = checkRateLimit('contact_' . $clientIp, 5, 600);
        if (!$rateLimit['allowed']) {
            $errors[] = 'Too many messages sent. Please wait ' . max(1, (int)ceil($rateLimit['retry_after'] / 60)) . ' minute(s) and try again.';
        } else {
            $formData['name']    = cleanField($_POST['name'] ?? '', 100);
            $formData['email']   = cleanField($_POST['email'] ?? '', 150);
            $formData['phone']   = cleanField($_POST['phone'] ?? '', 30);
            $formData['subject'] = cleanField($_POST['subject'] ?? '', 80);
            $formData['message'] = cleanField($_POST['message'] ?? '', 2000);

            if ($formData['name'] === '') {
                $errors[] = 'Your full name is required.';
            }
            if ($formData['email'] === '') {
                $errors[] = 'Your email address is required.';
            } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address, for example name@example.com.';
            }
            if ($formData['phone'] !== '' && !isValidPakistaniPhone($formData['phone'])) {
                $errors[] = 'Enter a valid Pakistani phone number (e.g. 0300-1234567 or +92 300 1234567).';
            }
            if (!isset($topics[$formData['subject']])) {
                $errors[] = 'Please choose an inquiry topic.';
            }
            if ($formData['message'] === '') {
                $errors[] = 'Please enter your message.';
            } elseif (mb_strlen($formData['message']) < 10) {
                $errors[] = 'Your message is too short. Please write at least 10 characters.';
            }

            if (empty($errors)) {
                try {
                    $pdo  = getDBConnection();
                    $stmt = $pdo->prepare(
                        'INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, \'new\', NOW())'
                    );
                    $stmt->execute([
                        $formData['name'],
                        $formData['email'],
                        $formData['phone'] !== '' ? $formData['phone'] : null,
                        $formData['subject'],
                        $formData['message'],
                        $clientIp,
                    ]);

                    recordRateLimitAttempt('contact_' . $clientIp);
                    setFlash('success', 'Thank you! Your message has been received. Our team will reply by email or phone.');
                    header('Location: ' . url('contact.php'));
                    exit;
                } catch (PDOException $ex) {
                    error_log('[VMS CONTACT SAVE ERROR] ' . $ex->getMessage());
                    $errors[] = 'We could not save your message right now. Please try again in a few minutes or call the helpline.';
                }
            }
        }
    }
}

$pageTitle = 'Contact & Support Helpdesk';
$pageDescription = 'Contact the VMS helpdesk about vaccination schedules, appointments, or clinic enrollment.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">

    <?php uiPageHeader(
        'Contact the helpdesk',
        'Questions about a vaccination schedule, an appointment, or enrolling your clinic? Send us a message or call.',
        'We are here to help'
    ); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert" style="max-width: 960px; margin: 0 auto 1.5rem;">
            <div>
                <strong>Please fix the following:</strong>
                <ul style="margin: 0.35rem 0 0 1.2rem; line-height: 1.4;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-sidebar" style="gap: 2rem; max-width: 960px; margin: 0 auto;">
        <aside>
            <div class="card" style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.1rem; margin-bottom: .9rem; color: var(--primary-dark);"><?= uiIcon('phone', 18); ?> Helpline</h3>
                <p style="font-size: .9rem; line-height: 1.6; margin: 0 0 .75rem; color: var(--text-muted);">
                    <strong>Toll-free support</strong><br>
                    <a href="tel:080086700">0800-867-00</a><br>
                    <small>Mon&ndash;Sat, 8:00 AM&ndash;6:00 PM PKT</small>
                </p>
                <p style="font-size: .9rem; line-height: 1.6; margin: 0; color: var(--text-muted);">
                    <strong>Emergency line (24/7)</strong><br>
                    <a href="tel:+9221111867725">+92 (21) 111-867-725</a>
                </p>
            </div>

            <div class="card" style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.1rem; margin-bottom: .75rem; color: var(--primary-dark);"><?= uiIcon('mail', 18); ?> Email</h3>
                <p style="font-size: .9rem; line-height: 1.6; margin: 0 0 .5rem; color: var(--text-muted);">
                    <strong>Parent support</strong><br>
                    <a href="mailto:support@vms.health.local">support@vms.health.local</a>
                </p>
                <p style="font-size: .9rem; line-height: 1.6; margin: 0; color: var(--text-muted);">
                    <strong>Hospital partnerships</strong><br>
                    <a href="mailto:providers@vms.health.local">providers@vms.health.local</a>
                </p>
            </div>

            <div class="card">
                <h3 style="font-size: 1.1rem; margin-bottom: .5rem; color: var(--primary-dark);"><?= uiIcon('hospital', 18); ?> Clinic partnering</h3>
                <p style="font-size: .88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Run a hospital, pediatric center, or health clinic in Pakistan?
                </p>
                <a href="<?= url('register.php?type=hospital'); ?>" class="btn btn-outline btn-sm btn-block">Register your center</a>
            </div>
        </aside>

        <div class="card">
            <div class="card-header">
                <h3>Send us a message</h3>
            </div>

            <form action="<?= url('contact.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <?= honeypotField('website_hp'); ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Full name</label>
                        <input type="text" id="name" name="name" class="form-control" maxlength="100"
                               autocomplete="name" placeholder="e.g. Fatima Khan" value="<?= e($formData['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Email address</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150"
                               autocomplete="email" placeholder="e.g. fatima.khan@example.com" value="<?= e($formData['email']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone (optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" maxlength="30"
                               autocomplete="tel" placeholder="e.g. 0300-1234567" value="<?= e($formData['phone']); ?>"
                               pattern="^((\+92|0092|0)?(3[0-9]{2}|[2456789][0-9]))[\- ]?[0-9]{7,8}$"
                               title="Pakistani phone number, e.g. 0300-1234567 or +92 300 1234567">
                        <small class="form-hint">Format: 0300-1234567 or +92 300 1234567</small>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label required">Topic</label>
                        <select id="subject" name="subject" class="form-select" required>
                            <option value="">Select a topic</option>
                            <?php foreach ($topics as $value => $label): ?>
                                <option value="<?= e($value); ?>" <?= $formData['subject'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label required">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" required
                              minlength="10" maxlength="2000"
                              placeholder="Describe your question or problem in as much detail as you can."><?= e($formData['message']); ?></textarea>
                    <div class="ui-counter"><span id="msgCount">0</span> / 2000</div>
                </div>

                <div style="margin-top: 1.25rem; text-align: right;">
                    <button type="submit" class="btn btn-primary" style="padding: .75rem 2rem;">Send message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.getElementById('message');
    var out = document.getElementById('msgCount');
    if (!box || !out) { return; }
    function update() { out.textContent = box.value.length; }
    box.addEventListener('input', update);
    update();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>