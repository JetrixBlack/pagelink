<?php
/*
 * PageLink - Vista: Gestion de testimonios.
 */
declare(strict_types=1);

require_admin();
require_once __DIR__ . '/_nav.php';

$base = base_path();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        if ($_POST['action'] === 'approve' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE testimonials SET is_approved = 1 WHERE id = ?")->execute([$id]);
            $message = 'Testimonio aprobado y publicado.';
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $db->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$id]);
            $message = 'Testimonio eliminado.';
        }
    }
}

$testimonials = $db->query("SELECT * FROM testimonials ORDER BY is_approved ASC, id DESC")->fetchAll();
$adminCss = $base . 'assets/css/admin.css?v=' . asset_version('assets/css/admin.css');
$formAction = $base . 'admin/testimonials';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonios — PageLink Admin</title>
    <link rel="stylesheet" href="<?= $adminCss ?>">
</head>
<body>
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
        <?php admin_nav('testimonials'); ?>
        <div class="toast-container" id="toastContainer"></div>

        <div class="card">
            <h2>Gestión de Testimonios</h2>
            <p style="font-size: 0.85rem; color: #8a8580; margin-bottom: 16px;">
                Los testimonios enviados por el público aparecen aquí como "Pendientes". Debes aprobarlos para que se muestren en tu perfil.
            </p>

            <?php if (empty($testimonials)): ?>
                <p class="empty">No hay testimonios todavía.</p>
            <?php else: ?>
            <div class="table-scroll">
            <table>
                <thead><tr><th>Estado</th><th>Autor</th><th>Testimonio</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($testimonials as $t): ?>
                    <tr style="<?= $t['is_approved'] == 0 ? 'background: rgba(196,122,138,0.04);' : '' ?>">
                        <td>
                            <?php if ($t['is_approved'] == 1): ?>
                                <span class="status-badge status-approved">Aprobado</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($t['author']) ?></td>
                        <td style="max-width:300px; font-size: 0.85rem;">
                            <?= htmlspecialchars(mb_substr($t['text'], 0, 80)) ?><?= mb_strlen($t['text']) > 80 ? '...' : '' ?>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($t['is_approved'] == 0): ?>
                                    <form method="POST" style="display:inline;" action="<?= $formAction ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-approve">Aprobar</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['author'], ENT_QUOTES) ?>')">Eliminar</button>
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

    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <form method="POST" id="deleteForm" action="<?= $formAction ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-body">
                    <div class="delete-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </div>
                    <div class="delete-title">¿Eliminar testimonio?</div>
                    <div class="delete-text">Se eliminará el testimonio de <span class="delete-author-name" id="deleteAuthorName"></span>. Esta acción no se puede deshacer.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('deleteModal')">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openDeleteModal(id, author) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteAuthorName').textContent = '"' + author + '"';
        document.getElementById('deleteModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        document.body.style.overflow = '';
    }
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m.id)); }
    });
    </script>
    <script>
    <?php if ($message): ?>
    (function() {
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast toast-success';
        toast.innerHTML = '<span class="toast-icon">&#9989;</span><?= addslashes(htmlspecialchars($message)) ?>';
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
