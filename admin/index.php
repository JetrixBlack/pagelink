<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();

// ═══ STATS ═══
$linkCount = $db->query("SELECT COUNT(*) FROM links")->fetchColumn();
$clickCount = $db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();
$testimonialCount = $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
$testimonialPending = $db->query("SELECT COUNT(*) FROM testimonials WHERE is_approved = 0")->fetchColumn();
$lastClick = $db->query("SELECT c.created_at, l.label FROM clicks c JOIN links l ON l.id = c.link_id ORDER BY c.created_at DESC LIMIT 1")->fetch();
$avgClicks = $linkCount > 0 ? round($clickCount / $linkCount, 1) : 0;

// Top links by clicks
$topLinks = $db->query("SELECT l.label, COUNT(c.id) as total FROM links l LEFT JOIN clicks c ON c.link_id = l.id GROUP BY l.id ORDER BY total DESC LIMIT 5")->fetchAll();
$maxClicks = $topLinks ? max(array_column($topLinks, 'total')) ?: 1 : 1;

// ═══ HISTORIAL CON FILTROS Y PAGINACION ═══
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

$filterLink = $_GET['filter_link'] ?? '';
$filterDate = $_GET['filter_date'] ?? '';

$where = "1=1";
$params = [];

if ($filterLink !== '') {
    $where .= " AND l.label = ?";
    $params[] = $filterLink;
}
if ($filterDate !== '') {
    $where .= " AND DATE(c.created_at) = ?";
    $params[] = $filterDate;
}

$countQuery = "SELECT COUNT(*) FROM clicks c JOIN links l ON l.id = c.link_id WHERE $where";
$totalRecords = $db->prepare($countQuery);
$totalRecords->execute($params);
$totalRecords = $totalRecords->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$query = "SELECT c.ip_address, c.user_agent, c.created_at, l.label
          FROM clicks c JOIN links l ON l.id = c.link_id
          WHERE $where
          ORDER BY c.created_at DESC
          LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();

// All links for filter dropdown
$allLinks = $db->query("SELECT DISTINCT label FROM links ORDER BY label")->fetchAll(PDO::FETCH_COLUMN);

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PageLink Admin</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
    <meta http-equiv="refresh" content="30">
    <style>
        .dash-toolbar { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; align-items:center; }
        .dash-toolbar-right { margin-left:auto; display:flex; gap:8px; align-items:center; }
        .auto-refresh-badge {
            font-size:0.7rem; color:var(--fg-soft); background:var(--surface-hover);
            padding:4px 10px; border-radius:99px; border:1px solid var(--border);
        }
        .filter-bar {
            display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px;
        }
        .filter-bar select, .filter-bar input[type="date"] {
            padding:8px 10px; border:1px solid var(--border); border-radius:var(--radius-sm);
            background:var(--bg); color:var(--fg); font-size:0.85rem;
        }
        .filter-bar select:focus, .filter-bar input[type="date"]:focus {
            outline:none; border-color:var(--accent);
        }
        .pagination {
            display:flex; gap:4px; justify-content:center; align-items:center; margin-top:16px;
        }
        .pagination a, .pagination span {
            padding:6px 12px; border-radius:var(--radius-sm); font-size:0.8rem;
            text-decoration:none; color:var(--fg-soft); border:1px solid var(--border);
            transition: all 0.15s;
        }
        .pagination a:hover { background:var(--surface-hover); color:var(--fg); }
        .pagination .active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .pagination .disabled { opacity:0.4; pointer-events:none; }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="preloader-spinner"></div>
        <div class="preloader-text">PageLink</div>
    </div>
    <style>
    .preloader { position:fixed; inset:0; z-index:10000; background:#0f0f0f; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; transition:opacity 0.4s; }
    .preloader.fade-out { opacity:0; pointer-events:none; }
    .preloader-spinner { width:36px; height:36px; border:3px solid #2a2a2a; border-top-color:#c47a8a; border-radius:50%; animation:spin 0.8s linear infinite; }
    .preloader-text { color:#8a8080; font-size:0.9rem; letter-spacing:0.05em; }
    @keyframes spin { to { transform:rotate(360deg); } }
    </style>

    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="toast-container" id="toastContainer"></div>

        <!-- ═══ CARDS RESUMEN ═══ -->
        <div class="grid">
            <div class="card">
                <div class="card-value"><?= $linkCount ?></div>
                <div class="card-label">Enlaces</div>
            </div>
            <div class="card">
                <div class="card-value"><?= $clickCount ?></div>
                <div class="card-label">Clics totales</div>
            </div>
            <div class="card">
                <div class="card-value"><?= $avgClicks ?></div>
                <div class="card-label">Promedio clics/enlace</div>
            </div>
            <div class="card">
                <div class="card-value" style="<?= $testimonialPending > 0 ? 'color:#caa06a' : '' ?>"><?= $testimonialCount ?><?= $testimonialPending > 0 ? " <span style='font-size:0.75rem;font-weight:400'>($testimonialPending pen.)</span>" : '' ?></div>
                <div class="card-label">Testimonios</div>
                <div class="card-sub"><?= $lastClick ? 'Ultimo clic: ' . date('d/m H:i', strtotime($lastClick['created_at'])) : 'Sin actividad' ?></div>
            </div>
        </div>

        <!-- ═══ TOOLBAR: ACCIONES + REFRESH ═══ -->
        <div class="dash-toolbar">
            <a href="links.php" class="btn btn-sm">+ Nuevo enlace</a>
            <a href="export-csv.php" class="btn btn-sm btn-ghost">Exportar CSV</a>
            <?php if ($testimonialPending > 0): ?>
            <a href="testimonials.php" class="btn btn-sm btn-success"><?= $testimonialPending ?> pendiente<?= $testimonialPending > 1 ? 's' : '' ?></a>
            <?php endif; ?>
            <div class="dash-toolbar-right">
                <span class="auto-refresh-badge" id="refreshBadge">Auto-refresh: 30s</span>
                <button type="button" class="btn btn-sm btn-ghost" onclick="location.reload()" title="Actualizar ahora">Actualizar</button>
            </div>
        </div>

        <!-- ═══ TOP ENLACES (barras) ═══ -->
        <?php if (!empty($topLinks)): ?>
        <div class="section">
            <h2>Top enlaces por clics</h2>
            <div class="bar-chart">
                <?php foreach ($topLinks as $row): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= htmlspecialchars($row['label']) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill<?= $row['total'] < $maxClicks * 0.2 ? ' small' : '' ?>" style="width:<?= max(2, ($row['total'] / $maxClicks) * 100) ?>%"><?= $row['total'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═══ HISTORIAL DE ACTIVIDAD ═══ -->
        <div class="section">
            <h2>Historial de actividad</h2>

            <!-- Filtros -->
            <form method="GET" class="filter-bar">
                <div>
                    <label style="margin-top:0;margin-bottom:4px;font-size:0.8rem">Enlace</label>
                    <select name="filter_link">
                        <option value="">Todos</option>
                        <?php foreach ($allLinks as $label): ?>
                        <option value="<?= htmlspecialchars($label) ?>"<?= $filterLink === $label ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="margin-top:0;margin-bottom:4px;font-size:0.8rem">Fecha</label>
                    <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <button type="submit" class="btn btn-sm">Filtrar</button>
                <?php if ($filterLink !== '' || $filterDate !== ''): ?>
                <a href="index.php" class="btn btn-sm btn-ghost">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (empty($history)): ?>
                <p class="empty">Sin registros <?= ($filterLink !== '' || $filterDate !== '') ? 'para estos filtros' : 'aun' ?>.</p>
            <?php else: ?>
                <div class="table-scroll">
                <table>
                    <thead><tr><th>Enlace</th><th>IP</th><th>Fecha</th><th>Navegador</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['label']) ?></td>
                            <td class="ip"><?= htmlspecialchars($c['ip_address']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                            <td class="ua" title="<?= htmlspecialchars($c['user_agent']) ?>"><?= htmlspecialchars($c['user_agent']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <!-- Paginacion -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?= max(1, $page - 1) ?>&filter_link=<?= urlencode($filterLink) ?>&filter_date=<?= urlencode($filterDate) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&filter_link=<?= urlencode($filterLink) ?>&filter_date=<?= urlencode($filterDate) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page + 1) ?>&filter_link=<?= urlencode($filterLink) ?>&filter_date=<?= urlencode($filterDate) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
                <p style="text-align:center;font-size:0.75rem;color:var(--fg-soft);margin-top:8px">
                    Mostrando <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRecords) ?> de <?= $totalRecords ?> registros
                </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ═══ TUS ENLACES ═══ -->
        <div class="section">
            <h2>Tus enlaces</h2>
            <?php
            $linksList = $db->query("SELECT label, sort_order FROM links ORDER BY sort_order ASC")->fetchAll();
            if (empty($linksList)):
            ?>
                <p class="empty">No hay enlaces. <a href="links.php" style="color:var(--accent)">Crea uno</a>.</p>
            <?php else: ?>
                <ul style="list-style:none">
                    <?php foreach ($linksList as $l): ?>
                    <li class="dash-link-item">
                        <span><?= htmlspecialchars($l['label']) ?></span>
                        <span class="dash-link-badge">#<?= $l['sort_order'] + 1 ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Countdown auto-refresh
    let countdown = 30;
    const badge = document.getElementById('refreshBadge');
    setInterval(() => {
        countdown--;
        if (countdown <= 0) countdown = 30;
        badge.textContent = 'Auto-refresh: ' + countdown + 's';
    }, 1000);
    </script>
    <script>
    <?php if ($flash): ?>
    (function() {
        var isError = <?= $flash['type'] === 'error' ? 'true' : 'false' ?>;
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success');
        toast.innerHTML = '<span class="toast-icon">' + (isError ? '&#10060;' : '&#9989;') + '<?= addslashes(htmlspecialchars($flash['msg'])) ?></span>';
        container.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    })();
    <?php endif; ?>
    </script>
    <script>
    window.addEventListener('load', function() {
        var p = document.getElementById('preloader');
        if (p) { p.classList.add('fade-out'); setTimeout(function() { p.remove(); }, 400); }
    });
    </script>
</body>
</html>
