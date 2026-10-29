<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/ui.php
 * Description: Shared UI helpers (SVG icons, page header, small utilities)
 *              and a small extra stylesheet that sits on top of the existing CSS.
 * ----------------------------------------------------------------------------
 * USAGE (in any page, after header.php):
 *     require_once __DIR__ . '/includes/ui.php';   // or '/../includes/ui.php'
 *     uiStyles();                                   // prints the <style> block once
 *     uiPageHeader('Title', 'Subtitle', 'Badge');
 *     echo uiIcon('check');
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

/**
 * Inline SVG icon (replaces emoji / HTML entities that render as empty boxes).
 */
function uiIcon(string $name, int $size = 20): string
{
    static $paths = [
        'alert'    => '<path d="M12 9v4m0 4h.01M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'hospital' => '<path d="M3 21V8l9-5 9 5v13M9 21v-6h6v6M12 8v4M10 10h4"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
        'child'    => '<circle cx="12" cy="7" r="3.5"/><path d="M6 21v-5a6 6 0 0 1 12 0v5M9 21v-3m6 3v-3"/>',
        'syringe'  => '<path d="m18 2 4 4M17 7l3-3M19 9 8.7 19.3a1 1 0 0 1-.7.3H4v-4a1 1 0 0 1 .3-.7L15 5M9 11l4 4M5 19l-3 3M14 4l6 6"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'map'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back'     => '<path d="M19 12H5m6-6-6 6 6 6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4zM13.5 6.5l4 4"/>',
        'trash'    => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'print'    => '<path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'scale'    => '<path d="M12 3v18M5 21h14M5 7h14M5 7l-3 7a3.5 3.5 0 0 0 6 0L5 7zm14 0-3 7a3.5 3.5 0 0 0 6 0l-3-7z"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>',
    ];

    $inner = $paths[$name] ?? $paths['info'];
    $size  = max(10, $size);

    return '<svg class="ui-icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
         . 'aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

/**
 * Consistent page header block (badge + h1 + subtitle). Replaces the
 * copy-pasted inline-styled header in every public page.
 */
function uiPageHeader(string $title, string $subtitle = '', string $badge = '', bool $center = true): void
{
    echo '<header class="ui-page-header' . ($center ? ' is-center' : '') . '">';
    if ($badge !== '') {
        echo '<span class="badge badge-info">' . e($badge) . '</span>';
    }
    echo '<h1>' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . e($subtitle) . '</p>';
    }
    echo '</header>';
}

/**
 * Escape % and _ so user search text is matched literally inside LIKE.
 * MySQL's default LIKE escape character is backslash, so no ESCAPE clause is needed.
 */
function likeEscape(string $value): string
{
    return addcslashes($value, '%_\\');
}

/**
 * Validate a Y-m-d date string (strtotime() accepts garbage, this does not).
 */
function isValidYmd(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

/**
 * Human label for a recommended age in days: 0 => At birth, 42 => 6 weeks,
 * 270 => 9 months, 450 => 15 months ...
 */
function ageGroupLabel(int $days): string
{
    if ($days <= 0) {
        return 'At birth';
    }
    if ($days < 180 && $days % 7 === 0) {
        $w = intdiv($days, 7);
        return $w . ($w === 1 ? ' week' : ' weeks');
    }
    if ($days >= 365 && $days % 365 === 0) {
        $y = intdiv($days, 365);
        return $y . ($y === 1 ? ' year' : ' years');
    }
    if ($days % 30 === 0) {
        return intdiv($days, 30) . ' months';
    }
    return $days . ' days';
}

/**
 * True when the app runs locally or APP_ENV=development
 * (used to decide whether demo credentials may be shown).
 */
function isDevelopmentEnv(): bool
{
    $env = getenv('APP_ENV');
    if ($env === false || $env === '') {
        $env = $_ENV['APP_ENV'] ?? ($_SERVER['APP_ENV'] ?? '');
    }
    if (strtolower((string)$env) === 'development') {
        return true;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
}

/**
 * Extra stylesheet. Uses the project's existing CSS variables (with fallbacks)
 * so it blends in with the current theme. Printed only once per request.
 */
function uiStyles(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
<style>
:root{
    --ui-ink: var(--text-main, #0f172a);
    --ui-muted: var(--text-muted, #475569);
    --ui-line: var(--border-color, #e2e8f0);
    --ui-soft: var(--bg-subtle, #f1f5f9);
    --ui-brand: var(--primary, #0284c7);
    --ui-brand-dark: var(--primary-dark, #0369a1);
    --ui-ok: var(--success, #059669);
    --ui-bad: var(--danger, #dc2626);
    --ui-warn: #b45309;
}
.ui-icon{display:inline-block;vertical-align:-0.2em;flex-shrink:0}
.ui-btn-icon{display:inline-flex;align-items:center;gap:.4rem}
.ui-sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.ui-skip{position:absolute;left:-999px;top:0;background:#0f172a;color:#fff;padding:.6rem 1rem;z-index:9999}
.ui-skip:focus{left:.5rem;top:.5rem}
.ui-page-header{max-width:760px;margin:0 0 2rem}
.ui-page-header.is-center{margin-left:auto;margin-right:auto;text-align:center}
.ui-page-header .badge{margin-bottom:.6rem}
.ui-page-header h1{font-size:clamp(1.7rem,4vw,2.3rem);line-height:1.2;margin:0 0 .6rem}
.ui-page-header p{margin:0;color:var(--ui-muted);font-size:1.05rem;line-height:1.6}
.ui-filter{display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:1.75rem;padding:1.1rem}
.ui-filter .grow{flex:2;min-width:230px}
.ui-filter .mid{flex:1;min-width:160px}
.ui-filter .btns{display:flex;gap:.5rem}
.ui-filter .btns .btn{height:42px}
.ui-count{margin:0 0 1rem;color:var(--ui-muted);font-size:.92rem}
.ui-empty{text-align:center;padding:3rem 1rem;color:var(--ui-muted)}
.ui-empty .ui-icon{color:var(--ui-brand);margin-bottom:.5rem}
.ui-empty h3{margin:0 0 .4rem;color:var(--ui-ink)}
.ui-empty p{max-width:440px;margin:0 auto 1.1rem}
.ui-center-card{max-width:660px;margin:0 auto;text-align:center;padding:2.75rem 1.75rem}
.ui-hero-icon{width:68px;height:68px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;border:2px solid currentColor}
.ui-hero-icon.is-bad{color:var(--ui-bad)}
.ui-hero-icon.is-brand{color:var(--ui-brand-dark)}
.ui-hero-icon.is-ok{color:var(--ui-ok)}
.ui-nav-list{list-style:none;margin:.6rem 0 0;padding:0;display:grid;gap:.15rem}
.ui-nav-list a{display:flex;align-items:center;gap:.5rem;padding:.45rem .25rem;border-radius:6px}
.ui-nav-list a:hover{background:rgba(2,132,199,.08)}
.ui-note{background:var(--ui-soft);border-radius:10px;padding:1.1rem 1.25rem;text-align:left}
.ui-split{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.75rem}
.ui-split > .card{border-left:4px solid var(--ui-line)}
.ui-split > .card.is-bad{border-left-color:var(--ui-bad)}
.ui-split > .card.is-ok{border-left-color:var(--ui-ok)}
.ui-split h3{display:flex;align-items:center;gap:.6rem;margin:0 0 .85rem}
.ui-split p{color:var(--ui-muted);line-height:1.65}
.ui-roles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem}
.ui-role{padding:1.75rem 1.4rem;text-align:center}
.ui-role .ui-hero-icon{width:56px;height:56px;margin-bottom:1rem}
.ui-role p{margin:0;font-size:.92rem;color:var(--ui-muted);line-height:1.6}
.ui-faq{max-width:780px;margin:0 auto 2.5rem}
.ui-faq details{border:1px solid var(--ui-line);border-radius:10px;padding:0;margin-bottom:.7rem;background:#fff}
.ui-faq summary{cursor:pointer;list-style:none;padding:1rem 1.2rem;font-weight:600;display:flex;justify-content:space-between;gap:1rem;align-items:center}
.ui-faq summary::-webkit-details-marker{display:none}
.ui-faq summary::after{content:"+";font-size:1.3rem;line-height:1;color:var(--ui-brand-dark)}
.ui-faq details[open] summary::after{content:"\2212"}
.ui-faq details p{margin:0;padding:0 1.2rem 1.1rem;color:var(--ui-muted);line-height:1.65;font-size:.95rem}
.ui-cta{background:var(--ui-soft);border:1px solid var(--ui-line);text-align:center;padding:2.25rem 1.5rem}
.ui-cta-dark{background:#0f172a;color:#fff;padding:4rem 1rem;text-align:center;margin-top:2rem}
.ui-cta-dark h2{color:#fff;font-size:clamp(1.6rem,4vw,2.2rem);margin:0 0 1rem}
.ui-cta-dark p{max-width:650px;margin:0 auto 2rem;color:#cbd5e1;font-size:1.05rem;line-height:1.6}
.ui-btn-row{display:flex;justify-content:center;gap:.75rem;flex-wrap:wrap}
.ui-stats{background:#fff;border-bottom:1px solid var(--ui-line);padding:2.5rem 0}
.ui-section-muted{background:var(--ui-soft);padding:3.5rem 0;border-top:1px solid var(--ui-line);border-bottom:1px solid var(--ui-line)}
.ui-section-head{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap;margin-bottom:2rem}
.ui-section-head h2{font-size:1.75rem;margin:.4rem 0 .25rem}
.ui-section-head p{margin:0;color:var(--ui-muted)}
.ui-center{text-align:center}
/* Hospital cards */
.ui-hosp{display:flex;flex-direction:column;justify-content:space-between;height:100%}
.ui-hosp h3{font-size:1.2rem;margin:0 0 .3rem;color:var(--ui-ink)}
.ui-hosp .where{font-size:.88rem;color:var(--ui-brand-dark);font-weight:600;margin:0 0 .45rem}
.ui-hosp .addr{font-size:.86rem;color:var(--ui-muted);line-height:1.5;margin:0 0 1rem}
.ui-hosp-top{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.75rem}
.ui-hosp-foot{border-top:1px solid var(--ui-line);padding-top:.85rem;display:flex;flex-direction:column;gap:.7rem}
.ui-hosp-meta{display:flex;flex-wrap:wrap;gap:.35rem .9rem;font-size:.85rem}
.ui-hosp-meta a{display:inline-flex;align-items:center;gap:.3rem;font-weight:600}
.ui-hosp-actions{display:flex;gap:.5rem;flex-wrap:wrap}
.ui-pager{display:flex;justify-content:center;align-items:center;gap:.4rem;flex-wrap:wrap;margin-top:2rem}
.ui-pager a,.ui-pager span{min-width:38px;height:38px;padding:0 .7rem;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--ui-line);border-radius:8px;font-weight:600;font-size:.9rem}
.ui-pager .is-current{background:var(--ui-brand);border-color:var(--ui-brand);color:#fff}
.ui-pager .is-off{opacity:.45}
/* Vaccine timeline groups */
.ui-vgroup{margin-bottom:2.25rem}
.ui-vgroup-head{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.ui-vgroup-head h2{font-size:1.25rem;margin:0}
.ui-vgroup-head::before{content:"";width:12px;height:12px;border-radius:50%;background:var(--ui-brand);box-shadow:0 0 0 4px rgba(2,132,199,.18)}
.ui-vgroup-head::after{content:"";flex:1;height:1px;background:var(--ui-line)}
/* Legal pages */
.ui-legal{display:grid;grid-template-columns:230px minmax(0,1fr);gap:2rem;align-items:start;max-width:1040px;margin:0 auto}
.ui-legal nav{position:sticky;top:1rem;font-size:.9rem}
.ui-legal nav strong{display:block;margin-bottom:.5rem}
.ui-legal nav a{display:block;padding:.35rem 0;color:var(--ui-muted)}
.ui-legal nav a:hover{color:var(--ui-brand-dark)}
.ui-legal article{padding:2.25rem 2rem}
.ui-legal h1{font-size:2rem;margin:0 0 .4rem}
.ui-legal .meta{color:var(--ui-muted);font-size:.93rem;margin:0 0 2rem;padding-bottom:1rem;border-bottom:1px solid var(--ui-line)}
.ui-legal section{margin-bottom:2rem;scroll-margin-top:1rem}
.ui-legal h2{font-size:1.2rem;margin:0 0 .6rem}
.ui-legal p,.ui-legal li{line-height:1.7;color:var(--ui-muted)}
.ui-legal ul{margin:0 0 0 1.4rem}
.ui-legal-foot{border-top:1px solid var(--ui-line);padding-top:1.4rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
/* Auth */
.ui-pass{position:relative}
.ui-pass input{padding-right:3rem}
.ui-pass button{position:absolute;right:.4rem;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:var(--ui-muted);padding:.4rem;border-radius:6px}
.ui-pass button:hover,.ui-pass button:focus-visible{color:var(--ui-brand-dark);background:var(--ui-soft)}
.ui-demo{margin-top:1.5rem;background:var(--ui-soft);padding:1rem;border-radius:10px;font-size:.8rem;border:1px dashed var(--ui-line)}
.ui-demo ul{margin:.4rem 0 0 1.2rem;line-height:1.7;color:var(--ui-muted)}
/* Portal pages */
.ui-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ui-bar h1{margin:0 0 .2rem}
.ui-bar p{margin:0}
.ui-alert-row{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
.ui-alert-row > div:first-child{display:flex;align-items:flex-start;gap:.75rem;min-width:0;flex:1}
.ui-alert-row small{display:block;margin-top:.15rem}
.ui-child-card{background:var(--ui-soft);padding:1.25rem;border-radius:10px;border:1px solid var(--ui-line)}
.ui-child-top{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.75rem}
.ui-child-top .who{display:flex;align-items:center;gap:.6rem}
.ui-child-top h4{margin:0;font-size:1.1rem}
.ui-avatar{width:42px;height:42px;border-radius:50%;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center}
.ui-stat-ico{display:flex;align-items:center;justify-content:center}
/* Booking form */
.hospital-radio-card{display:block;border:2px solid var(--ui-line);border-radius:10px;padding:1rem;cursor:pointer;transition:border-color .15s,background-color .15s}
.hospital-radio-card:hover{border-color:var(--ui-brand)}
.hospital-radio-card:has(input:checked){border-color:var(--ui-brand);background:rgba(2,132,199,.07)}
.hospital-radio-card:has(input:focus-visible){outline:3px solid rgba(2,132,199,.35);outline-offset:2px}
.ui-hosp-pick{display:flex;align-items:flex-start;gap:.6rem}
.ui-step{display:flex;align-items:center;gap:.6rem}
.ui-step-n{width:26px;height:26px;border-radius:50%;background:var(--ui-brand);color:#fff;font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
/* Certificate */
.cert-box{border:3px double var(--ui-brand)}
.ui-sign-row{margin-top:3.5rem;display:flex;justify-content:space-between;gap:1rem;padding-top:2rem;border-top:1px dashed #94a3b8}
.ui-sign{text-align:center;width:230px;max-width:46%}
.ui-sign i{display:block;border-bottom:1px solid #334155;height:35px;margin-bottom:.5rem}
.ui-sign small{font-weight:600;color:var(--ui-muted)}
.ui-kv{width:100%;font-size:.9rem;line-height:1.8;border-collapse:collapse}
.ui-kv td:first-child{width:110px;color:var(--ui-muted)}
.ui-progress{background:var(--ui-soft);height:12px;border-radius:999px;overflow:hidden}
.ui-progress > div{background:var(--teal,#0d9488);height:100%;transition:width .4s ease}
.ui-counter{font-size:.8rem;color:var(--ui-muted);text-align:right;margin-top:.25rem}
@media (max-width:860px){
    .ui-legal{grid-template-columns:1fr}
    .ui-legal nav{position:static}
    .ui-split,.ui-roles{grid-template-columns:1fr}
}
@media (max-width:560px){
    .ui-legal article{padding:1.5rem 1.1rem}
    .ui-sign-row{flex-direction:column;align-items:center}
    .ui-sign{max-width:100%}
}
@media (prefers-reduced-motion:reduce){*{transition:none !important;animation:none !important}}
@media print{
    .no-print,.ui-skip{display:none !important}
    .cert-box{border:4px double #0284c7 !important;padding:2rem !important}
}
</style>
    <?php
}
