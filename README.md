# Vaccination Management System (VMS) - Aptech eProject (Pakistan)

A comprehensive, production-grade Child Vaccination Management System built with **Plain PHP 8+**, **MySQL / MariaDB (PDO Prepared Statements)**, **Apache (XAMPP)**, **HTML5**, **Custom Mobile-First CSS (Zero Frameworks, Zero Filled Buttons)**, and **Vanilla JavaScript**.

---

## 1. Project Overview & Architecture
Manual recording of infant immunizations through paper health cards or school visits leads to skipped booster doses and preventable outbreaks. The **Vaccination Management System (VMS)** resolves this through an integrated tripartite digital architecture:
1. **Parents:** Register child profiles (DOB, gender, blood group), receive automated due date alerts calculated dynamically from DOB and national Pakistan schedule (EPI), discover participating clinics, and book appointment slots.
2. **Pediatric Hospitals:** Review admin-approved appointments, record clinical administration status (`Vaccinated` / `Not Vaccinated`) with observations and timestamps, and manage local vaccine inventory stock.
3. **Administration:** Moderate appointment requests (`Approve` / `Reject`), manage hospital facility enrollments, maintain the vaccine catalog schedule, and generate printable compliance reports.

---

## 2. Default System Credentials (Pakistan Localized)

| Role | Portal URL | Login Email | Password |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin/login.php` | `admin@vms.local` | `Admin@123` |
| **Parent 1 (Ahmed Khan)** | `login.php` | `ahmed.khan@example.com` | `Parent@123` |
| **Parent 2 (Fatima Khan)** | `login.php` | `fatima.khan@example.com` | `Parent@123` |
| **Hospital 1 (Indus Hospital)** | `login.php` | `indus.hospital@hospital.local` | `Hospital@123` |
| **Hospital 2 (Children's Hospital Lahore)** | `login.php` | `children.hospital@hospital.local` | `Hospital@123` |
| **Hospital 3 (Federal Pediatrics Islamabad)** | `login.php` | `federal.pediatrics@hospital.local` | `Hospital@123` |

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
- **Session Protection:** Session cookies configured with `HttpOnly`, `SameSite=Lax`, sliding inactivity timeout (2 hours), and secure flags on HTTPS.
- **Directory Protection:** `.htaccess` blocks direct browser access to `config/`, `includes/`, and `database/`.
- **Anti-Spam & Rate Limiting:** Honeypot verification and rate limiting on login and contact forms.
- **Strictly No Solid Buttons / No Purple Gradients:** 100% outlined, non-filled button styling with high contrast.
