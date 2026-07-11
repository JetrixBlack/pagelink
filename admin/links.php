<?php
/*
 * PageLink - Panel de Administración
 * Archivo: links.php
 * Descripción: Gestión de enlaces del perfil público del administrador.
 *              Permite crear, editar y eliminar enlaces que se muestran
 *              en la página pública de PageLink del usuario.
 */

session_start();

// Verificar que el admin esté autenticado; si no, redirigir al login
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../config/database.php';

// Verificar si la sesión ha expirado por inactividad
check_session_timeout();

$db = getDB();
$message = '';

// ─── Manejo de acciones CRUD (Crear, Actualizar, Eliminar) ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verificar token CSRF antes de procesar cualquier acción
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        // Obtener y limpiar los datos del formulario
        $label    = trim($_POST['label'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $url      = trim($_POST['url'] ?? '');
        $sort     = (int)($_POST['sort_order'] ?? 0);

        // Validar que label y URL no estén vacíos
        if ($label === '' || $url === '') {
            $message = 'Label y URL son obligatorios.';
        } elseif ($_POST['action'] === 'create') {
            // Acción: Crear un nuevo enlace en la base de datos
            $db->prepare("INSERT INTO links (label, subtitle, url, sort_order) VALUES (?, ?, ?, ?)")->execute([$label, $subtitle, $url, $sort]);
            $message = 'Enlace creado.';
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            // Acción: Actualizar un enlace existente por su ID
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE links SET label=?, subtitle=?, url=?, sort_order=? WHERE id=?")->execute([$label, $subtitle, $url, $sort, $id]);
            $message = 'Enlace actualizado.';
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Acción: Eliminar un enlace y todos sus registros de clics asociados
            $id = (int)$_POST['id'];
            $db->prepare("DELETE FROM clicks WHERE link_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM links WHERE id = ?")->execute([$id]);
            $message = 'Enlace eliminado.';
        }
    }
}

// Obtener todos los enlaces ordenados por su posición de sort_order
$links = $db->query("SELECT * FROM links ORDER BY sort_order ASC")->fetchAll();

// Calcular el siguiente valor de sort_order para nuevos enlaces
$nextSort = $links ? max(array_column($links, 'sort_order')) + 1 : 0;

// ─── Colores de marca conocidos ──────────────────────────────────
// Array asociativo que mapea nombres de plataformas sociales a sus
// colores oficiales. Se usa para mostrar un ícono con el color
// correcto junto al label de cada enlace en la tabla.
$brandColors = [
    'telegram' => '#26A5E4', 'github' => '#181717', 'instagram' => '#E4405F',
    'facebook' => '#1877F2', 'twitter' => '#000000', 'x' => '#000000',
    'onlyfans' => '#00AFF0', 'tiktok' => '#000000', 'threads' => '#000000',
    'youtube' => '#FF0000', 'whatsapp' => '#25D366',     'link' => '#c47a8a',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlaces — PageLink Admin</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
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
        <?php include __DIR__ . '/_nav.php'; ?>

        <div class="toast-container" id="toastContainer"></div>

        <!-- ═══════════════════════════════════════
             FORMULARIO PARA CREAR UN NUEVO ENLACE
             Contiene campos para label, subtítulo, URL y orden.
        ═══════════════════════════════════════ -->
        <div class="card">
            <h2>Nuevo enlace</h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <input type="text" name="label" placeholder="Label (ej: Instagram)" required>
                    <input type="text" name="subtitle" placeholder="Subtítulo (ej: Sígueme)">
                </div>
                <div class="form-row">
                    <input type="text" name="url" placeholder="URL (ej: https://instagram.com/tuuser)" required>
                    <input type="number" name="sort_order" placeholder="Orden" style="min-width:80px" value="<?= $nextSort ?>">
                </div>
                <button type="submit">Crear enlace</button>
            </form>
        </div>

        <!-- ═══════════════════════════════════════
             TABLA DE ENLACES EXISTENTES
             Muestra todos los enlaces con su color de marca,
             label, subtítulo, URL y botones de acción.
        ═══════════════════════════════════════ -->
        <div class="card">
            <h2>Enlaces actuales</h2>
            <?php if (empty($links)): ?>
                <p class="empty">No hay enlaces todavía.</p>
            <?php else: ?>
            <div class="table-scroll">
            <table>
                <thead><tr><th>#</th><th>Icono</th><th>Label</th><th>Subtítulo</th><th>URL</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($links as $l):
                        // Buscar el color de marca que coincida con el label del enlace
                        $labelLower = mb_strtolower($l['label']);
                        $matchedColor = '#c47a8a';
                        foreach ($brandColors as $key => $color) {
                            if (str_contains($labelLower, $key)) { $matchedColor = $color; break; }
                        }
                    ?>
                    <tr>
                        <td><?= $l['sort_order'] + 1 ?></td>
                        <!-- Ícono cuadrado con la primera letra del label y el color de marca -->
                        <td><span style="display:inline-block;width:28px;height:28px;border-radius:8px;background:<?= $matchedColor ?>;color:#fff;text-align:center;line-height:28px;font-size:0.75rem;font-weight:700"><?= mb_strtoupper(mb_substr($l['label'], 0, 1)) ?></span></td>
                        <td><?= htmlspecialchars($l['label']) ?></td>
                        <td><?= htmlspecialchars($l['subtitle']) ?></td>
                        <td class="url-cell"><a href="<?= htmlspecialchars($l['url']) ?>" target="_blank"><?= htmlspecialchars($l['url']) ?></a></td>
                        <td>
                            <div class="actions">
                                <!-- Botón Editar: abre el modal con los datos del enlace -->
                                <button type="button" class="btn btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($l, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)">Editar</button>
                                <!-- Botón Eliminar: abre el modal de confirmación -->
                                <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?= $l['id'] ?>, '<?= htmlspecialchars($l['label'], ENT_QUOTES) ?>')">Eliminar</button>
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
         MODAL DE EDICIÓN DE ENLACE
         Se abre al hacer clic en "Editar" y carga los datos
         actuales del enlace en los campos del formulario.
    ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <form method="POST" id="editForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header">
                    <h3>Editar enlace</h3>
                </div>
                <div class="modal-body">
                    <label>Label</label>
                    <div class="form-row">
                        <input type="text" name="label" id="editLabel" placeholder="Label (ej: Instagram)" required>
                    </div>
                    <label>Subtítulo</label>
                    <div class="form-row">
                        <input type="text" name="subtitle" id="editSubtitle" placeholder="Subtítulo (ej: Sígueme)">
                    </div>
                    <label>URL</label>
                    <div class="form-row">
                        <input type="text" name="url" id="editUrl" placeholder="URL" required>
                    </div>
                    <label>Orden</label>
                    <div class="form-row">
                        <input type="number" name="sort_order" id="editSort" placeholder="Orden" style="max-width: 120px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('editModal')">Cancelar</button>
                    <button type="submit" class="btn">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         MODAL DE CONFIRMACIÓN DE ELIMINACIÓN
         Se abre al hacer clic en "Eliminar" y pide confirmación
         antes de borrar el enlace y sus registros de clics.
    ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal modal-delete">
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
                    <div class="delete-title">¿Eliminar enlace?</div>
                    <div class="delete-text">Se eliminará <span class="delete-link-name" id="deleteLinkName"></span> y todos sus registros de clics. Esta acción no se puede deshacer.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('deleteModal')">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ─── LÓGICA DE MODALES ───────────────────────────────────
        // Funciones JavaScript para abrir/cerrar los modales
        // de edición y eliminación de enlaces.

        // Abrir modal de edición: carga los datos del enlace en los campos del formulario
        function openEditModal(link) {
            document.getElementById('editId').value = link.id;
            document.getElementById('editLabel').value = link.label;
            document.getElementById('editSubtitle').value = link.subtitle || '';
            document.getElementById('editUrl').value = link.url;
            document.getElementById('editSort').value = link.sort_order;
            openModal('editModal');
            // Colocar foco en el primer campo después de la animación de apertura
            setTimeout(() => document.getElementById('editLabel').focus(), 250);
        }

        // Abrir modal de eliminación: muestra el nombre del enlace a eliminar
        function openDeleteModal(id, label) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteLinkName').textContent = '"' + label + '"';
            openModal('deleteModal');
        }

        // Mostrar un modal por su ID y bloquear el scroll del fondo
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Ocultar un modal por su ID y restaurar el scroll del fondo
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Cerrar modal al hacer clic fuera del contenido (sobre la capa de fondo)
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Cerrar cualquier modal activo al presionar la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => {
                    m.classList.remove('active');
                });
                document.body.style.overflow = '';
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
