<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/footer.php
 * Description: Master HTML5 Footer & Shared JavaScript Loader
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}
?>
    </main> <!-- End .main-content -->

    <!-- Master Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="grid grid-3" style="gap: 2rem;">
                <!-- About Initiative -->
                <div>
                    <h4>Child Vaccination System</h4>
                    <p style="font-size: 0.88rem; color: #94a3b8; line-height: 1.6;">
                        A centralized healthcare initiative bridging parents, pediatric healthcare centers, and public health authorities across Pakistan to guarantee that every child receives vital, life-saving immunizations on schedule.
                    </p>
                    <div style="margin-top: 1rem;">
                        <a href="<?= url('privacy.php'); ?>" style="font-size: 0.82rem; color: #94a3b8; margin-right: 0.75rem;">Privacy Policy</a>
                        <a href="<?= url('terms.php'); ?>" style="font-size: 0.82rem; color: #94a3b8; margin-right: 0.75rem;">Terms &amp; Conditions</a>
                        <a href="#" onclick="localStorage.removeItem('vms_cookie_consent'); const b=document.getElementById('vms-cookie-banner'); if(b) b.style.display='block'; return false;" style="font-size: 0.82rem; color: #94a3b8;">Cookie Preferences</a>
                    </div>
                </div>

                <!-- Navigation Quick Links -->
                <div>
                    <h4>Quick Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="<?= url('index.php'); ?>">Home</a></li>
                        <li><a href="<?= url('about.php'); ?>">About Initiative</a></li>
                        <li><a href="<?= url('vaccines.php'); ?>">Vaccine Directory</a></li>
                        <li><a href="<?= url('hospitals.php'); ?>">Participating Centers</a></li>
                        <li><a href="<?= url('contact.php'); ?>">Inquiries &amp; Support</a></li>
                        <li><a href="<?= url('terms.php'); ?>">Terms &amp; Conditions</a></li>
                        <li><a href="<?= url('privacy.php'); ?>">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Emergency Support & Admin Link -->
                <div>
                    <h4>Emergency &amp; Assistance</h4>
                    <p style="font-size: 0.88rem; color: #94a3b8; margin-bottom: 0.5rem;">
                        <strong>National Immunization Helpline:</strong><br>
                        Toll-Free: 0800-867-00 (Mon - Sat, 8am - 6pm PKT)<br>
                        Helpline: +92 (21) 111-867-725
                    </p>
                    <p style="font-size: 0.88rem; color: #94a3b8;">
                        <strong>Support Email:</strong><br>
                        support@vms.health.local
                    </p>
                    <div style="margin-top: 1rem;">
                        <a href="<?= url('admin/login.php'); ?>" style="font-size: 0.82rem; color: #64748b; text-decoration: underline;">
                            Staff / Admin Access Portal &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright and Aptech eProject Notice -->
            <div class="footer-bottom">
                <p style="margin: 0; color: #64748b; font-size: 0.85rem;">
                    &copy; <?= date('Y'); ?> Vaccination Management System (VMS) Pakistan. Aptech Computer Education eProject. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Master JavaScript Engine (Linked Once Globally) -->
    <script src="<?= url('assets/js/main.js'); ?>"></script>
    <script src="<?= url('assets/js/animations.js'); ?>"></script>
</body>
</html>
