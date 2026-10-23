<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Registration Pending Status Notice
 * app/views/pages/auth/registration-pending.php
 *
 * Faithfully recreates the original Next.js registration-pending page:
 *  - Academy logo with amber clock status badge
 *  - Clear status message explaining pending Sensei review
 *  - Explanation of official Student ID assignment upon verification
 *  - Verified contact information from database
 *  - Safe: Does not reveal any other student's personal details
 */

declare(strict_types=1);

$pageTitle       = 'Registration Under Review | Armour Karate Academy';
$metaDescription = 'Your student admission application is currently under review by the Sensei.';

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

    <!-- Ambient Red Glow -->
    <div class="position-absolute top-50 start-50 translate-middle rounded-circle pointer-events-none"
         style="width:500px; height:500px; background:rgba(220,38,38,0.1); filter:blur(150px); pointer-events:none;"></div>

    <div class="rounded-4 p-4 p-sm-5 text-center position-relative z-1 shadow-2xl"
         style="max-width:520px; width:100%; background:rgba(20,20,22,0.92); border:1px solid #2a2a2a; backdrop-filter:blur(16px);">

        <!-- Logo with Status Badge -->
        <div class="position-relative d-inline-block mx-auto mb-4" style="width:80px; height:80px;">
            <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="80" height="80" class="rounded-circle"
                 style="border:2px solid #DC2626; box-shadow:0 8px 24px rgba(220,38,38,0.3);">
            <span class="position-absolute bottom-0 end-0 p-1 rounded-circle d-flex align-items-center justify-content-center"
                  style="background:#F59E0B; color:#000; width:26px; height:26px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                </svg>
            </span>
        </div>

        <!-- Heading -->
        <div class="mb-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2"
                 style="background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); color:#fbbf24; font-size:0.7rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase;">
                Awaiting Sensei Approval
            </div>
            <h1 class="fw-black text-uppercase text-white mb-2" style="font-size:1.6rem; letter-spacing:-0.02em;">
                Registration Under Review
            </h1>
            <p style="font-size:0.85rem; color:#d4d4d4; line-height:1.7;">
                Thank you for enrolling in <span class="text-white fw-bold">Armour Martial Arts Gujju Karate Association</span>. Your student admission request is currently pending administrative review.
            </p>
        </div>

        <!-- Explanation Card -->
        <div class="p-4 rounded-3 text-start mb-4 d-flex flex-column gap-3"
             style="background:#0a0a0a; border:1px solid #222; font-size:0.8rem; color:#d4d4d4;">
            <div class="d-flex align-items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" class="mt-1" aria-hidden="true">
                    <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                </svg>
                <div>
                    <span class="fw-bold text-white d-block text-uppercase" style="font-size:0.75rem; letter-spacing:0.06em;">Official Student ID Assignment</span>
                    <span style="font-size:0.75rem; color:#a3a3a3;">Upon dojo verification, your unique permanent Student ID (e.g. KAR-YYYY-NNNN) will be assigned by the Chief Instructor.</span>
                </div>
            </div>

            <div class="d-flex align-items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" class="mt-1" aria-hidden="true">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                <div>
                    <span class="fw-bold text-white d-block text-uppercase" style="font-size:0.75rem; letter-spacing:0.06em;">Account Activation</span>
                    <span style="font-size:0.75rem; color:#a3a3a3;">Once approved, your credentials will become active and grant full access to your personalized belt journey and student dashboard.</span>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="pt-3 mb-4" style="border-top:1px solid #222; font-size:0.75rem; color:#a3a3a3;">
            <p class="mb-2">Need immediate assistance or have questions regarding training batches?</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php if ($helpline): ?>
                    <a href="tel:<?= escAttr(preg_replace('/[^0-9+]/', '', $helpline)) ?>" class="text-danger fw-bold text-decoration-none">
                        <?= esc($helpline) ?>
                    </a>
                <?php endif; ?>
                <?php if ($email): ?>
                    <a href="mailto:<?= escAttr($email) ?>" class="text-danger fw-bold text-decoration-none">
                        <?= esc($email) ?>
                    </a>
                <?php endif; ?>
                <?php if (!$helpline && !$email): ?>
                    <a href="<?= BASE_PATH ?>/contact" class="text-danger fw-bold text-decoration-none">
                        Contact Academy Administration
                    </a>
                <?php endif; ?>
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
