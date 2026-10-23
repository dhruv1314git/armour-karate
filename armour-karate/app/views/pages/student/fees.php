<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Fee History & Clearance Records
 * app/views/pages/student/fees.php
 *
 * Faithfully recreates the original Next.js student fees page:
 *  - 3 Summary metrics cards: Fees Paid Up To, Total Contributed, Latest Receipt
 *  - Reliable fee status calculation (Paid & Active vs Due vs Contact Academy)
 *  - Official ledger table of payments with receipt IDs, methods, amounts & paid-up-to dates
 *  - Indian Rupee (₹) currency formatting
 *  - Safe empty state when no payments are recorded
 *  - Strict IDOR isolation: Only authenticated student's payment records are queried
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'My Fee Records & Receipts';
$portalType = 'student';

// Fetch student profile ID
$stmtStudent = $pdo->prepare(
    "SELECT id FROM student_profiles WHERE id = ? AND user_id = ? LIMIT 1"
);
$stmtStudent->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtStudent->fetch();

if (!$student) {
    destroySession();
    safeRedirect(BASE_PATH . '/login');
}

// Fetch all payment records for this student (IDOR-scoped)
$stmtPayments = $pdo->prepare(
    "SELECT * FROM payments 
     WHERE student_profile_id = ? 
     ORDER BY payment_date DESC, id DESC"
);
$stmtPayments->execute([$student['id']]);
$payments = $stmtPayments->fetchAll();

// Calculations
$latestPayment = $payments[0] ?? null;
$feesPaidUpTo  = $latestPayment['fees_paid_up_to'] ?? null;
$hasPayments   = !empty($payments);

// Fee status evaluation
$isDue = false;
$feeStatusMessage = 'Please contact the academy for your current fee status.';
if ($feesPaidUpTo) {
    $paidUpTime = strtotime($feesPaidUpTo);
    $todayTime  = strtotime('today');
    if ($paidUpTime < $todayTime) {
        $isDue = true;
        $feeStatusMessage = 'Payment is currently due';
    } else {
        $isDue = false;
        $feeStatusMessage = 'Dues cleared and active';
    }
}

// Total amount paid
$totalPaid = 0.0;
foreach ($payments as $p) {
    $totalPaid += (float) $p['amount'];
}

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            My Fee Records &amp; Receipts
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Official ledger of tuition dues and payment clearance receipts
        </p>
    </div>

    <!-- Summary Metrics Cards Grid -->
    <div class="row g-3">
        <!-- 1. Fees Paid Up To Card -->
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                        Fees Paid Up To
                    </span>
                    <div class="p-2 rounded-3 text-secondary" style="background: rgba(0, 0, 0, 0.04);">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="fs-4 fw-black <?= $feesPaidUpTo ? ($isDue ? 'text-danger' : 'text-success') : 'text-dark' ?>">
                        <?= $feesPaidUpTo ? formatDate($feesPaidUpTo) : 'No Record' ?>
                    </span>
                </div>
                <div class="mt-auto">
                    <?php if ($feesPaidUpTo): ?>
                        <?php if ($isDue): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.72rem;">
                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
                                <span><?= esc($feeStatusMessage) ?></span>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.72rem;">
                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                <span><?= esc($feeStatusMessage) ?></span>
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-secondary small fst-italic" style="font-size: 0.72rem;">
                            <?= esc($feeStatusMessage) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Total Fees Contributed Card -->
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                        Total Fees Contributed
                    </span>
                    <div class="p-2 rounded-3 text-secondary" style="background: rgba(0, 0, 0, 0.04);">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2Zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7Z"/></svg>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="fs-4 fw-black text-dark">
                        <?= formatCurrency($totalPaid) ?>
                    </span>
                </div>
                <div class="mt-auto text-secondary small" style="font-size: 0.75rem;">
                    Across <?= count($payments) ?> recorded <?= count($payments) === 1 ? 'payment' : 'payments' ?>
                </div>
            </div>
        </div>

        <!-- 3. Latest Receipt Card -->
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                        Latest Receipt
                    </span>
                    <div class="p-2 rounded-3 text-secondary" style="background: rgba(0, 0, 0, 0.04);">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647A.5.5 0 0 1 14 1.05v13.9a.5.5 0 0 1-.854.353l-.646-.646-.646.646a.5.5 0 0 1-.708 0L10.5 14.65l-.646.646a.5.5 0 0 1-.708 0L8.5 14.65l-.646.646a.5.5 0 0 1-.708 0L6.5 14.65l-.646.646a.5.5 0 0 1-.708 0L4.5 14.65l-.646.646a.5.5 0 0 1-.708 0l-.646-.646-.646.646A.5.5 0 0 1 1 14.95V1.05a.5.5 0 0 1 .92-.544zM2 2.5v11l.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0L14 13.5v-11H2z"/></svg>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="fs-4 fw-black text-dark font-monospace">
                        <?= $latestPayment ? esc($latestPayment['payment_ref']) : '—' ?>
                    </span>
                </div>
                <div class="mt-auto text-secondary small" style="font-size: 0.75rem;">
                    <?= $latestPayment ? formatDate($latestPayment['payment_date']) : 'No payments recorded yet' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Ledger Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <div class="mb-4">
            <h2 class="fs-6 fw-bold text-dark mb-1">Payment History Ledger</h2>
            <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                All payments issued by the dojo office for training, admissions, or tournament kits
            </p>
        </div>

        <?php if (!empty($payments)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light text-uppercase text-secondary" style="font-size: 0.7rem;">
                        <tr>
                            <th class="py-3 font-bold">Receipt ID</th>
                            <th class="py-3 font-bold">Billing Period</th>
                            <th class="py-3 font-bold">Payment Date</th>
                            <th class="py-3 font-bold">Method</th>
                            <th class="py-3 font-bold">Amount</th>
                            <th class="py-3 font-bold">Paid Up To</th>
                            <th class="py-3 font-bold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-danger py-3">
                                    <?= esc($p['payment_ref']) ?>
                                </td>
                                <td class="text-dark fw-medium py-3">
                                    <?= esc($p['payment_period']) ?>
                                </td>
                                <td class="text-secondary py-3">
                                    <?= formatDate($p['payment_date']) ?>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-light text-dark border px-2 py-1 text-uppercase fw-bold" style="font-size: 0.68rem;">
                                        <?= esc($p['payment_method']) ?>
                                    </span>
                                </td>
                                <td class="fw-black text-dark py-3">
                                    <?= formatCurrency($p['amount']) ?>
                                </td>
                                <td class="text-success fw-bold py-3">
                                    <?= $p['fees_paid_up_to'] ? formatDate($p['fees_paid_up_to']) : '—' ?>
                                </td>
                                <td class="text-secondary py-3 text-truncate" style="max-width: 200px;">
                                    <?= !empty($p['remarks']) ? esc($p['remarks']) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-5 text-center text-secondary small">
                No fee payment records have been added to your ledger yet.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
