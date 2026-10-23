<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Account Inactive Status Notice
 * app/views/pages/auth/account-inactive.php
 *
 * Faithfully recreates the original Next.js account-inactive page:
 *  - Academy logo & amber warning icon
 *  - Clear status notification regarding inactive membership
 *  - Administrative office contact channels
 *  - Safe: No student personal data disclosed
 */

declare(strict_types=1);

$pageTitle       = 'Account Inactive | Armour Karate Academy';
$metaDescription = 'Student membership account inactive notice.';

$pdo = db();
$headOffice = null;
try {
    $stmtHo = $pdo->query("SELECT phone, email, office_timing FROM head_office WHERE id = 1");
    $headOffice = $stmtHo->fetch() ?: null;
} catch (PDOException) {
    $headOffice = null;
}

$helpline = ($headOffice && !empty($headOffice['phone'])) ? $headOffice['phone'] : null;
$email    = ($headOffice && !empty($headOffice['email'])) ? $headOffice['email'] : null;
$timing   = ($headOffice && !empty($headOffice['office_timing'])) ? $headOffice['office_timing'] : 'Office Hours: 6:00 AM - 8:30 PM';

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center p-4 position-relative overflow-hidden"
     style="background:#0A0A0A; color:#fff;">

    <div class="rounded-4 p-4 p-sm-5 text-center position-relative z-1 shadow-2xl"
         style="max-width:480px; width:100%; background:rgba(20,20,22,0.92); border:1px solid #2a2a2a; backdrop-filter:blur(16px);">

        <!-- Academy Logo -->
        <div class="mb-4">
            <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="72" height="72" class="rounded-circle"
                 style="border:2px solid rgba(245,158,11,0.5);">
        </div>

        <!-- Status Icon & Message -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3"
                 style="width:52px; height:52px; background:rgba(245,158,11,0.2); border:1px solid #f59e0b; color:#fbbf24;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
                </svg>
            </div>
            <h1 class="fw-black text-uppercase text-white mb-2" style="font-size:1.5rem; letter-spacing:-0.02em;">
                Account Inactive
            </h1>
            <p style="font-size:0.85rem; color:#a3a3a3; line-height:1.7;">
                Your student membership account is currently inactive. Please clear any overdue fees or contact the academy office to reactivate your training status.
            </p>
        </div>

        <!-- Contact Box -->
        <div class="p-3.5 rounded-3 text-start mb-4 d-flex flex-column gap-2"
             style="background:#0a0a0a; border:1px solid #222; font-size:0.8rem; color:#d4d4d4;">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#F59E0B" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                </svg>
                <span><?= esc($timing) ?></span>
            </div>
            <?php if ($helpline): ?>
                <div class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#F59E0B" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                    </svg>
                    <span><?= esc($helpline) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($email): ?>
                <div class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#F59E0B" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                    </svg>
                    <span><?= esc($email) ?></span>
                </div>
            <?php endif; ?>
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
