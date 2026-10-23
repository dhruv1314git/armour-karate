<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Contact Enquiries Management
 * app/views/pages/admin/contact.php
 *
 * Uses the actual existing table: contact_messages
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $action    = trim($_POST['action'] ?? '');
    $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

    if ($messageId && $messageId > 0) {
        if ($action === 'update_status') {
            $newStatus = trim($_POST['status'] ?? '');
            if (in_array($newStatus, ['UNREAD', 'READ', 'ARCHIVED'], true)) {
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $messageId]);

                writeAuditLog($pdo, 'CONTACT_STATUS_UPDATED', 'ContactMessage', $messageId, [
                    'new_status' => $newStatus,
                ], $adminId);

                flashSet('success', "Enquiry status updated to {$newStatus}.");
            }
        } elseif ($action === 'delete') {
            // Fetch message details for audit trail before deleting
            $msgStmt = $pdo->prepare("SELECT name, email, subject FROM contact_messages WHERE id = ?");
            $msgStmt->execute([$messageId]);
            $msgData = $msgStmt->fetch(PDO::FETCH_ASSOC);

            if ($msgData) {
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$messageId]);

                writeAuditLog($pdo, 'CONTACT_MESSAGE_DELETED', 'ContactMessage', $messageId, [
                    'name'    => $msgData['name'],
                    'email'   => $msgData['email'],
                    'subject' => $msgData['subject'],
                ], $adminId);

                flashSet('success', 'Contact enquiry deleted successfully.');
            }
        }
    }

    safeRedirect(BASE_PATH . '/admin/contact' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

// ─── Filters & Data Fetching ──────────────────────────────────────────────────

$filterStatus = strtoupper(trim($_GET['status'] ?? 'ALL'));
if (!in_array($filterStatus, ['ALL', 'UNREAD', 'READ', 'ARCHIVED'], true)) {
    $filterStatus = 'ALL';
}

$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT id, name, phone, email, subject, message, status, created_at FROM contact_messages WHERE 1=1";
$params = [];

if ($filterStatus !== 'ALL') {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

if ($searchQuery !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts per status
$countAll      = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$countUnread   = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'UNREAD'")->fetchColumn();
$countRead     = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'READ'")->fetchColumn();
$countArchived = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'ARCHIVED'")->fetchColumn();

$pageTitle  = 'Contact Enquiries';
$portalType = 'admin';

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="space-y-4">
    <!-- Top Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark">Contact Enquiries</h1>
            <p class="text-muted small mb-0">Manage messages and inquiries submitted via the public contact form.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger rounded-pill px-3 py-2 fw-semibold">
                <?= $countUnread ?> Unread
            </span>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-1">
                    <a href="<?= BASE_PATH ?>/admin/contact"
                       class="btn btn-sm <?= $filterStatus === 'ALL' ? 'btn-danger fw-bold' : 'btn-light text-muted' ?> rounded-pill px-3">
                        All (<?= $countAll ?>)
                    </a>
                    <a href="<?= BASE_PATH ?>/admin/contact?status=UNREAD"
                       class="btn btn-sm <?= $filterStatus === 'UNREAD' ? 'btn-danger fw-bold' : 'btn-light text-muted' ?> rounded-pill px-3">
                        Unread (<?= $countUnread ?>)
                    </a>
                    <a href="<?= BASE_PATH ?>/admin/contact?status=READ"
                       class="btn btn-sm <?= $filterStatus === 'READ' ? 'btn-danger fw-bold' : 'btn-light text-muted' ?> rounded-pill px-3">
                        Read (<?= $countRead ?>)
                    </a>
                    <a href="<?= BASE_PATH ?>/admin/contact?status=ARCHIVED"
                       class="btn btn-sm <?= $filterStatus === 'ARCHIVED' ? 'btn-danger fw-bold' : 'btn-light text-muted' ?> rounded-pill px-3">
                        Archived (<?= $countArchived ?>)
                    </a>
                </div>

                <form method="GET" action="<?= BASE_PATH ?>/admin/contact" class="d-flex gap-2" style="max-width: 320px;">
                    <?php if ($filterStatus !== 'ALL'): ?>
                        <input type="hidden" name="status" value="<?= escAttr($filterStatus) ?>">
                    <?php endif; ?>
                    <input type="search" name="q" class="form-control form-control-sm rounded-pill"
                           placeholder="Search sender, email, subject..." value="<?= escAttr($searchQuery) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Search</button>
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= BASE_PATH ?>/admin/contact<?= $filterStatus !== 'ALL' ? '?status=' . escAttr($filterStatus) : '' ?>"
                           class="btn btn-sm btn-link text-muted p-0 align-self-center">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-2">
                        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="h6 fw-bold text-dark mb-1">No contact enquiries found</h2>
                    <p class="text-muted small mb-0">Inquiries submitted from the public contact page will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="font-size:0.75rem; text-transform:uppercase;">Sender</th>
                                <th style="font-size:0.75rem; text-transform:uppercase;">Subject & Preview</th>
                                <th style="font-size:0.75rem; text-transform:uppercase;">Date</th>
                                <th style="font-size:0.75rem; text-transform:uppercase;">Status</th>
                                <th class="text-end pe-4" style="font-size:0.75rem; text-transform:uppercase;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="<?= $msg['status'] === 'UNREAD' ? 'table-warning-subtle fw-semibold' : '' ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= esc($msg['name']) ?></div>
                                        <div class="text-muted small">
                                            <?= esc($msg['email'] ?? '—') ?>
                                            <?php if (!empty($msg['phone'])): ?>
                                                &bull; <?= esc($msg['phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold"><?= esc($msg['subject'] ?: '(No Subject)') ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 380px;">
                                            <?= esc(mb_substr($msg['message'], 0, 100)) . (mb_strlen($msg['message']) > 100 ? '...' : '') ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap small text-muted">
                                        <?= esc(formatDate($msg['created_at'], 'd M Y, h:i A')) ?>
                                    </td>
                                    <td>
                                        <?php if ($msg['status'] === 'UNREAD'): ?>
                                            <span class="badge bg-danger rounded-pill px-2 py-1">UNREAD</span>
                                        <?php elseif ($msg['status'] === 'READ'): ?>
                                            <span class="badge bg-success rounded-pill px-2 py-1">READ</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-2 py-1">ARCHIVED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1">
                                            <!-- View Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal<?= $msg['id'] ?>" title="View Message">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>

                                            <!-- Toggle Status Form -->
                                            <?php if ($msg['status'] !== 'READ'): ?>
                                                <form method="POST" action="<?= BASE_PATH ?>/admin/contact" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                    <input type="hidden" name="status" value="READ">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as Read">
                                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?= BASE_PATH ?>/admin/contact" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                    <input type="hidden" name="status" value="UNREAD">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mark as Unread">
                                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($msg['status'] !== 'ARCHIVED'): ?>
                                                <form method="POST" action="<?= BASE_PATH ?>/admin/contact" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                    <input type="hidden" name="status" value="ARCHIVED">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Archive">
                                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Delete Form -->
                                            <form method="POST" action="<?= BASE_PATH ?>/admin/contact" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to permanently delete this enquiry?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Message Detail Modal -->
                                        <div class="modal fade text-start" id="viewModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-bottom px-4 pt-4 pb-3">
                                                        <div>
                                                            <h2 class="h5 fw-bold text-dark mb-0"><?= esc($msg['subject'] ?: 'Contact Enquiry') ?></h2>
                                                            <p class="text-muted small mb-0">From <?= esc($msg['name']) ?> on <?= esc(formatDate($msg['created_at'], 'd M Y, h:i A')) ?></p>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body px-4 py-3">
                                                        <div class="bg-light p-3 rounded-3 mb-3">
                                                            <div class="row g-2 small">
                                                                <div class="col-6"><strong>Email:</strong> <a href="mailto:<?= escAttr($msg['email'] ?? '') ?>"><?= esc($msg['email'] ?? '—') ?></a></div>
                                                                <div class="col-6"><strong>Phone:</strong> <a href="tel:<?= escAttr($msg['phone'] ?? '') ?>"><?= esc($msg['phone'] ?? '—') ?></a></div>
                                                                <div class="col-12"><strong>Status:</strong> <?= esc($msg['status']) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="p-3 bg-white border rounded-3" style="min-height: 120px; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6; color: #374151;">
                                                            <?= esc($msg['message']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top px-4 pb-4 pt-3">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <?php if (!empty($msg['email'])): ?>
                                                            <a href="mailto:<?= escAttr($msg['email']) ?>?subject=Re:%20<?= urlencode($msg['subject'] ?? 'Armour Karate Enquiry') ?>"
                                                               class="btn btn-sm btn-danger fw-semibold">
                                                                Reply via Email
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
