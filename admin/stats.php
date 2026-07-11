<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();
$clicksByLink = $db->query("SELECT l.label, COUNT(c.id) as total FROM links l LEFT JOIN clicks c ON c.link_id = l.id GROUP BY l.id ORDER BY total DESC")->fetchAll();
$recentClicks = $db->query("SELECT c.ip_address, c.user_agent, c.created_at, l.label FROM clicks c JOIN links l ON l.id = c.link_id ORDER BY c.created_at DESC LIMIT 50")->fetchAll();
$totalClicks = $db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();
$totalLinks  = $db->query("SELECT COUNT(*) FROM links")->fetchColumn();

$flash = flash_get();

if (isset($_GET['reset']) && $_GET['reset'] === 'confirm') {
    $db->exec("DELETE FROM clicks");
    flash_set('Todos los clics han sido eliminados.');
    header('Location: stats.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if ($flash): ?><div class="message"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">
            <h2 style="font-size:1rem;color:#3f3a36">Resumen</h2>
            <div style="display:flex;gap:8px">
                <a href="export-csv.php" class="btn btn-sm" style="background:#8a8580">Exportar CSV</a>
                <a href="?reset=confirm" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar TODOS los clics registrados? Esta acción no se puede deshacer.')">Resetear clics</a>
            </div>
        </div>

        <div class="grid">
            <div class="card"><div class="card-value"><?= $totalClicks ?></div><div class="card-label">Clics totales</div></div>
            <div class="card"><div class="card-value"><?= $totalLinks ?></div><div class="card-label">Enlaces</div></div>
            <div class="card"><div class="card-value"><?= $totalLinks > 0 ? round($totalClicks / $totalLinks, 1) : 0 ?></div><div class="card-label">Promedio clics/enlace</div></div>
        </div>

        <div class="section">
            <h2>Clics por enlace</h2>
            <?php if (empty($clicksByLink)): ?>
                <p class="empty">Sin datos todavía.</p>
            <?php else: $maxClicks = max(array_column($clicksByLink, 'total')) ?: 1; ?>
            <div class="bar-chart">
                <?php foreach ($clicksByLink as $row): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= htmlspecialchars($row['label']) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill<?= $row['total'] < $maxClicks * 0.2 ? ' small' : '' ?>" style="width:<?= max(2, ($row['total'] / $maxClicks) * 100) ?>%"><?= $row['total'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Últimos clics</h2>
            <?php if (empty($recentClicks)): ?>
                <p class="empty" id="clicksEmpty">No hay clics registrados.</p>
            <?php else: ?>
            <div class="table-scroll">
            <table id="clicksTable">
                <thead><tr><th>Enlace</th><th>IP</th><th>Fecha</th><th>Navegador</th></tr></thead>
                <tbody id="clicksBody">
                    <?php foreach ($recentClicks as $c): ?>
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
            <div id="clicksFooter" style="text-align:center;padding:16px 0">
                <button id="loadMoreBtn" class="btn btn-sm" onclick="loadMoreClicks()" style="background:#8a8580">Cargar más</button>
            </div>
            <script>
            let clickPage = 1;
            async function loadMoreClicks() {
                clickPage++;
                const btn = document.getElementById('loadMoreBtn');
                btn.textContent = 'Cargando...';
                btn.disabled = true;
                try {
                    const r = await fetch('../api/get-clicks.php?page=' + clickPage);
                    const json = await r.json();
                    const tbody = document.getElementById('clicksBody');
                    for (const c of json.data) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td>' + escapeHtml(c.label) + '</td><td class="ip">' + escapeHtml(c.ip_address) + '</td><td>' + new Date(c.created_at).toLocaleDateString('es-ES') + ' ' + new Date(c.created_at).toLocaleTimeString('es-ES', {hour:'2-digit',minute:'2-digit'}) + '</td><td class="ua" title="' + escapeHtml(c.user_agent) + '">' + escapeHtml(c.user_agent) + '</td>';
                        tbody.appendChild(tr);
                    }
                    if (json.hasMore) {
                        btn.textContent = 'Cargar más';
                        btn.disabled = false;
                    } else {
                        btn.textContent = 'No hay más clics';
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                    }
                } catch (e) {
                    btn.textContent = 'Error al cargar';
                    btn.disabled = false;
                }
            }
            function escapeHtml(s) {
                const d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            }
            </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
