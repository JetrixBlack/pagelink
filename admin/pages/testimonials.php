<?php
/*
 * PageLink - Panel de Administración
 * Archivo: testimonials.php
 * Descripción: Gestión de testimonios enviados por el público.
 *              Permite aprobar testimonios para que se muestren
 *              en el perfil público o eliminarlos definitivamente.
 */

session_start();

// Verificar que el admin esté autenticado; si no, redirigir al login
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../../config/database.php';

// Verificar si la sesión ha expirado por inactividad
check_session_timeout();

$db = getDB();
$message = '';

// ─── Manejo de acciones: aprobar y eliminar testimonios ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verificar token CSRF antes de procesar cualquier acción
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        if ($_POST['action'] === 'approve' && isset($_POST['id'])) {
            // Acción: Aprobar un testimonio para que sea visible en el perfil público
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE testimonials SET is_approved = 1 WHERE id = ?")->execute([$id]);
            $message = 'Testimonio aprobado y publicado.';
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Acción: Eliminar un testimonio definitivamente de la base de datos
            $id = (int)$_POST['id'];
            $db->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$id]);
            $message = 'Testimonio eliminado.';
        }
    }
}

// ─── Consulta de testimonios ─────────────────────────────────────
// Obtener todos los testimonios ordenados por estado (pendientes primero
// usando is_approved ASC) y luego por fecha descendente (id DESC como proxy).
$testimonials = $db->query("SELECT * FROM testimonials ORDER BY is_approved ASC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonios — PageLink Admin</title>
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <style>/* Modal styles loaded from style.css */</style>
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
        <!-- Barra de navegación del panel de administración -->
        <?php include __DIR__ . '/../partials/_nav.php'; ?>

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
                    <!-- Fila con fondo sutil para testimonios pendientes -->
                    <tr style="<?= $t['is_approved'] == 0 ? 'background: rgba(196,122,138,0.04);' : '' ?>">
                        <td>
                            <!-- Mostrar badge de estado: aprobado o pendiente -->
                            <?php if ($t['is_approved'] == 1): ?>
                                <span class="status-badge status-approved">Aprobado</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($t['author']) ?></td>
                        <td style="max-width:300px; font-size: 0.85rem;">
                            <!-- Mostrar una vista previa del texto (máx. 80 caracteres) -->
                            <?= htmlspecialchars(mb_substr($t['text'], 0, 80)) ?><?= mb_strlen($t['text']) > 80 ? '...' : '' ?>
                        </td>
                        <td>
                            <div class="actions">
                                <!-- Botón de aprobar: solo se muestra si el testimonio está pendiente -->
                                <?php if ($t['is_approved'] == 0): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-approve">Aprobar</button>
                                    </form>
                                <?php endif; ?>
                                <!-- Botón de eliminar: abre el modal de confirmación -->
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

    <!-- ═══════════════════════════════════════
         MODAL DE CONFIRMACIÓN DE ELIMINACIÓN
         Muestra una advertencia antes de borrar el testimonio.
    ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <form method="POST" id="deleteForm">
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
        // Abrir modal de eliminación mostrando el nombre del autor del testimonio
        function openDeleteModal(id, author) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteAuthorName').textContent = '"' + author + '"';
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Ocultar modal y restaurar el scroll de la página
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }

        // Cerrar modal al hacer clic fuera del contenido (sobre la capa de fondo)
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Cerrar cualquier modal activo al presionar la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => {
                    closeModal(m.id);
                });
            }
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
