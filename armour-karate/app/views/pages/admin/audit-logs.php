<?php
declare(strict_types=1);
requireAdmin();

$pageTitle  = 'Audit Logs';
$portalType = 'admin';
$adminId    = (int) ($_SESSION['user_id'] ?? 0);

$pdo = db();

// ── Pagination ────────────────────────────────────────────────────────────────
$perPage     = 50;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// Total count
$totalRows  = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$totalPages = (int) ceil($totalRows / $perPage);
$currentPage = min($currentPage, max(1, $totalPages));  // clamp after knowing total

// Fetch page of records (newest first)
$stmt = $pdo->prepare(
    "SELECT id, action, entity_type, entity_id, admin_id, created_at
       FROM audit_logs
      ORDER BY id DESC
      LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: build pagination URL
$paginateUrl = static function (int $page): string {
    $params = $_GET;
    $params['page'] = $page;
    return BASE_PATH . '/admin/audit-logs?' . http_build_query($params);
};

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="portal-content">

    <!-- Page heading -->
    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="20" height="20" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Audit Logs</h5>
                <p class="text-muted mb-0" style="font-size:0.8rem;">
                    <?= esc(number_format($totalRows)) ?> total entr<?= $totalRows === 1 ? 'y' : 'ies' ?> &mdash; read-only view
                </p>
            </div>
        </div>
        <span class="badge text-bg-secondary px-3 py-2" style="font-size:0.75rem;">
            Page <?= esc((string)$currentPage) ?> of <?= esc((string)max(1, $totalPages)) ?>
        </span>
    </div>

    <!-- Audit log table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center text-muted py-5">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 d-block mx-auto opacity-40">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mb-0" style="font-size:0.875rem;">No audit log entries found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.825rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3" style="width:80px;">ID</th>
                                <th class="py-3">Timestamp</th>
                                <th class="py-3">Action</th>
                                <th class="py-3">Entity Type</th>
                                <th class="py-3" style="width:100px;">Entity ID</th>
                                <th class="py-3 pe-4" style="width:100px;">Admin ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="px-4 py-3 text-muted"><?= esc((string)$log['id']) ?></td>
                                    <td class="py-3">
                                        <span class="text-nowrap"><?= esc(formatDate($log['created_at'], 'd M Y, H:i')) ?></span>
                                    </td>
                                    <td class="py-3">
                                        <code class="text-dark" style="font-size:0.8rem;background:#F3F4F6;padding:2px 6px;border-radius:4px;">
                                            <?= esc($log['action'] ?? '—') ?>
                                        </code>
                                    </td>
                                    <td class="py-3">
                                        <?php if (!empty($log['entity_type'])): ?>
                                            <span class="badge text-bg-light text-dark border" style="font-size:0.75rem;font-weight:500;">
                                                <?= esc($log['entity_type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <?= $log['entity_id'] !== null ? esc((string)$log['entity_id']) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="py-3 pe-4">
                                        <?= $log['admin_id'] !== null ? esc((string)$log['admin_id']) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="d-flex justify-content-center mt-4" aria-label="Audit log pages">
            <ul class="pagination pagination-sm mb-0">

                <!-- Previous -->
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= escAttr($paginateUrl($currentPage - 1)) ?>" aria-label="Previous">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </li>

                <!-- Page numbers (show window of 5 around current) -->
                <?php
                $windowStart = max(1, $currentPage - 2);
                $windowEnd   = min($totalPages, $currentPage + 2);
                if ($windowStart > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= escAttr($paginateUrl(1)) ?>">1</a></li>
                    <?php if ($windowStart > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= escAttr($paginateUrl($p)) ?>"><?= esc((string)$p) ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($windowEnd < $totalPages): ?>
                    <?php if ($windowEnd < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= escAttr($paginateUrl($totalPages)) ?>"><?= esc((string)$totalPages) ?></a></li>
                <?php endif; ?>

                <!-- Next -->
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= escAttr($paginateUrl($currentPage + 1)) ?>" aria-label="Next">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </li>

            </ul>
        </nav>
    <?php endif; ?>

</div><!-- /.portal-content -->

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
