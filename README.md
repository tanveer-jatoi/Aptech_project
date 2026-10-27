# Vaccination Management System (VMS) - Aptech eProject

A comprehensive, production-grade Child Vaccination Management System built with **Plain PHP 8+**, **MySQL / MariaDB (PDO Prepared Statements)**, **Apache (XAMPP)**, **HTML5**, **Custom Mobile-First CSS (Zero Frameworks)**, and **Vanilla JavaScript**.

---

## 1. Project Overview & Architecture
Manual recording of infant immunizations through paper health cards or school visits leads to skipped booster doses and preventable outbreaks. The **Vaccination Management System (VMS)** resolves this through an integrated tripartite digital architecture:
1. **Parents:** Register child profiles (DOB, gender, blood group), receive automated due date alerts calculated dynamically from DOB and national schedule, discover participating clinics, and book appointment slots.
2. **Pediatric Hospitals:** Review admin-approved appointments, record clinical administration status (`Vaccinated` / `Not Vaccinated`) with observations and timestamps, and manage local vaccine inventory stock.
3. **Administration:** Moderate appointment requests (`Approve` / `Reject`), manage hospital facility enrollments, maintain the vaccine catalog schedule, and generate printable compliance reports.

---

## 2. Default System Credentials

| Role | Portal URL | Login Email | Password |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin/login.php` | `admin@vms.local` | `Admin@123` |
| **Parent 1** | `login.php` | `john.doe@example.com` | `Parent@123` |
| **Parent 2** | `login.php` | `sarah.smith@example.com` | `Parent@123` |
| **Hospital 1** | `login.php` | `city.general@hospital.local` | `Hospital@123` |
| **Hospital 2** | `login.php` | `stjude.care@hospital.local` | `Hospital@123` |
| **Hospital 3** | `login.php` | `metro.pediatrics@hospital.local` | `Hospital@123` |

*Database Credentials (Default XAMPP):*
- **Host:** `127.0.0.1` | **Port:** `3306` | **User:** `root` | **Password:** `""` (blank) | **Database:** `vms_db`

---

## 3. Quick Setup Guide for XAMPP

1. **Start Services:** Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. **Import Database:**
   - Open browser at `http://localhost/phpmyadmin/`.
   - Click the **Import** tab.
   - Choose `vms/database/vms.sql` and click **Import**.
   *(Or via command line: `C:\xampp\mysql\bin\mysql.exe -u root < "c:\xampp\htdocs\Aptech_Project\vms\database\vms.sql"`)*
3. **Run Application:**
   - Open your browser to: `http://localhost/Aptech_Project/` (automatically forwards to `vms/index.php`).

---

## 4. Security Architecture & Standards
- **SQL Injection:** 100% PDO prepared statements with `$pdo->prepare()` and `$pdo->execute()`. Driver emulation disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Cross-Site Scripting (XSS):** All dynamic browser output sanitized with `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` via `e()`.
- **Cross-Site Request Forgery (CSRF):** Every POST form includes a 64-hex cryptographic token validated using `hash_equals()`.
- **Session Protection:** Session cookies configured with `HttpOnly`, `SameSite=Lax`. Session IDs regenerated on login (`session_regenerate_id(true)`).
- **Directory Protection:** `.htaccess` blocks direct browser access to `config/` and `includes/`.

---

## 5. Animation Engine (Zero Layout Shift)
- Respects `@media (prefers-reduced-motion: reduce)` across all keyframes and transitions.
- Animates only `transform` and `opacity`.
- Limited to 2–3 subtle animations on public pages:
  1. Hero section reveal keyframes.
  2. Live database count-up statistics via `IntersectionObserver`.
  3. Parent review shuffle carousel with pause-on-hover/focus.
  4. Success toast with animated checkmark after form submissions.
