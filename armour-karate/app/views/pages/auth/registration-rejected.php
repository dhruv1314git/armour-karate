<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Registration Rejected Notice
 * app/views/pages/auth/registration-rejected.php
 *
 * Faithfully recreates the original Next.js registration-rejected page:
 *  - Academy logo & red XCircle icon
 *  - Dignified status notice for unapproved applications
 *  - Dojo helpdesk inquiry directions
 *  - Safe: No personal data disclosure
 */

declare(strict_types=1);

$pageTitle       = 'Application Status | Armour Karate Academy';
$metaDescription = 'Student admission application status notification.';

$pdo = db();
$headOffice = null;
try {
    $stmtHo = $pdo->query("SELECT phone, email FROM head_office WHERE id = 1");
    $headOffice = $stmtHo->fetch() ?: null;
} catch (PDOException) {
    $headOffice = null;
}

$helpline = ($headOffice && !empty($headOffice['phone'])) ? $headOffice['phone'] : null;
$email    = ($headOffice && !empty($headOffice['email'])) ? $headOffice['email'] : null;

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center p-4 position-relative overflow-hidden"
     style="background:#0A0A0A; color:#fff;">

    <div class="rounded-4 p-4 p-sm-5 text-center position-relative z-1 shadow-2xl"
         style="max-width:480px; width:100%; background:rgba(20,20,22,0.92); border:1px solid #2a2a2a; backdrop-filter:blur(16px);">

        <!-- Academy Logo -->
        <div class="mb-4">
            <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="72" height="72" class="rounded-circle"
                 style="border:2px solid rgba(220,38,38,0.5);">
        </div>

        <!-- Status Icon & Message -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3"
                 style="width:52px; height:52px; background:rgba(127,29,29,0.35); border:1px solid #dc2626; color:#f87171;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                </svg>
            </div>
            <h1 class="fw-black text-uppercase text-white mb-2" style="font-size:1.5rem; letter-spacing:-0.02em;">
                Application Status
            </h1>
            <p style="font-size:0.85rem; color:#a3a3a3; line-height:1.7;">
                Your admission application could not be approved at this time. Please contact the dojo administration directly for further clarification.
            </p>
        </div>

        <!-- Contact Box -->
        <div class="p-3.5 rounded-3 text-start mb-4 d-flex flex-column gap-2"
             style="background:#0a0a0a; border:1px solid #222; font-size:0.8rem; color:#d4d4d4;">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                </svg>
                <span>Dojo Helpdesk: <?= esc($helpline ?: 'Contact Dojo Office') ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                <span><?= esc($email ?: 'Admissions Committee') ?></span>
            </div>
        </div>

        <!-- Home CTA -->
        <div>
            <a href="<?= BASE_PATH ?>/" class="btn px-4 py-2 rounded-3 text-white fw-bold text-uppercase"
               style="font-size:0.75rem; letter-spacing:0.08em; background:#222; border:1px solid #333;">
                &larr; Return to Homepage
            </a>
        </div>

    </div>
</div>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
