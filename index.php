<?php

/**
 * Dragon Ball Z - Gestión de Guerreros
 * Página principal con listado, búsqueda, filtros y paginación
 */

require_once 'database.php';

// Función auxiliar para escapar HTML y prevenir XSS
function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Obtener parámetros de búsqueda y filtros
$buscar = trim($_GET['buscar'] ?? '');
$filtro_raza = $_GET['raza'] ?? '';
$tema = $_GET['tema'] ?? 'claro';
$clase_tema = 'tema-' . ($tema === 'oscuro' ? 'oscuro' : 'claro');

// Configuración de paginación
$gxpag = 8;
$pagina = intval($_GET['pagina'] ?? 1);
if ($pagina < 1) $pagina = 1;

// Conectar a la base de datos
$pdo = conectar_db();
$params = [];
$where_clauses = "WHERE 1=1";

// Búsqueda por nombre
if ($buscar) {
    $where_clauses .= " AND nombre LIKE :buscar";
    $params['buscar'] = "%{$buscar}%";
}

// Búsqueda por raza
if ($filtro_raza) {
    $where_clauses .= " AND raza = :raza";
    $params['raza'] = $filtro_raza;
}

// Consultar total de registros
$sql_count = "SELECT COUNT(*) as total FROM guerreros {$where_clauses}";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();

// Calcular offset y total de páginas
$total_paginas = ceil($total_registros / $gxpag);
if ($total_paginas == 0) $total_paginas = 1;
if ($pagina > $total_paginas) $pagina = $total_paginas;

$offset = ($pagina - 1) * $gxpag;

// Consulta final para los resultados de la página actual
$sql = "SELECT id, nombre, raza, descripcion, imagen, poder 
        FROM guerreros {$where_clauses} 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Vincular parámetros de búsqueda
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

// Vincular parámetros de paginación
$stmt->bindValue(':limit', $gxpag, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$personajes = $stmt->fetchAll();

// Obtener razas únicas para el select
$razas = $pdo->query("SELECT DISTINCT raza FROM guerreros ORDER BY raza ASC")->fetchAll(PDO::FETCH_COLUMN);

// Función auxiliar para mantener parámetros GET sin la página
function get_url_params($exclude = ['pagina'])
{
    $params = $_GET;
    foreach ($exclude as $key) {
        unset($params[$key]);
    }
    return http_build_query($params);
}

$url_base = '?' . get_url_params();
if ($url_base === '?') $url_base = '?';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>DBZ – Listado de Guerreros</title>
    <link rel="stylesheet" href="Style.css">
    <!-- SweetAlert2 para notificaciones elegantes -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="<?php echo h($clase_tema); ?>">

    <header>
        <h1>🐉 Dragon Ball Z – Guerreros</h1>
        <span class="muted">Tema actual: <strong><?php echo h(ucfirst($tema)); ?></strong></span>
    </header>

    <!-- FORMULARIO GET: buscar / filtrar / tema -->
    <form class="panel" method="get" action="">
        <div>
            <label for="buscar">Buscar por nombre</label>
            <input type="text" id="buscar" name="buscar" value="<?php echo h($buscar); ?>" placeholder="Ej: Goku" />
        </div>

        <div>
            <label for="raza">Filtrar por raza</label>
            <select id="raza" name="raza">
                <option value="">Todas</option>
                <?php foreach ($razas as $r): ?>
                    <option value="<?php echo h($r); ?>" <?php echo $filtro_raza === $r ? 'selected' : ''; ?>>
                        <?php echo h($r); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="tema">Tema</label>
            <select id="tema" name="tema">
                <option value="claro" <?php echo $tema === 'claro' ? 'selected' : ''; ?>>Claro</option>
                <option value="oscuro" <?php echo $tema === 'oscuro' ? 'selected' : ''; ?>>Oscuro</option>
            </select>
        </div>

        <div>
            <label>&nbsp;</label>
            <input type="submit" value="Aplicar" />
        </div>
    </form>

    <!-- GRID DE TARJETAS -->
    <section class="grid" id="grid">
        <?php if (empty($personajes)): ?>
            <p class="muted">No se encontraron personajes con esos criterios.</p>
        <?php else: ?>
            <?php foreach ($personajes as $p): ?>
                <article class="card" id="card-<?php echo h($p['id']); ?>" style="--i:0">
                    <div class="media">
                        <img src="<?php echo h($p['imagen']); ?>"
                            alt="<?php echo h($p['nombre']); ?>"
                            onerror="this.src='https://via.placeholder.com/300x200?text=Sin+Imagen'" />
                    </div>
                    <div class="box">
                        <h3><?php echo h($p['nombre']); ?></h3>
                        <p class="muted"><?php echo h($p['descripcion']); ?></p>
                        <p><span class="tag"><?php echo h($p['raza']); ?></span></p>
                        <p class="poder"><strong>Poder:</strong> <?php echo h((string)($p['poder'] ?? 'Desconocido')); ?></p>

                        <div style="margin-top:.5rem; display:flex; gap:.5rem;">
                            <button class="btn editar" data-id="<?php echo h($p['id']); ?>">Editar</button>
                            <button class="btn eliminar" data-id="<?php echo h($p['id']); ?>">Eliminar</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>
        <div class="paginacion">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo $url_base; ?>&pagina=<?php echo $pagina - 1; ?>" class="btn-paginacion">
                    ← Anterior
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="pagina-actual"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo $url_base; ?>&pagina=<?php echo $i; ?>" class="btn-paginacion">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina < $total_paginas): ?>
                <a href="<?php echo $url_base; ?>&pagina=<?php echo $pagina + 1; ?>" class="btn-paginacion">
                    Siguiente →
                </a>
            <?php endif; ?>
        </div>
        <p class="muted" style="text-align:center; margin-top:.5rem;">
            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
            (<?php echo $total_registros; ?> guerreros)
        </p>
    <?php endif; ?>

    <!-- FORMULARIO POST para agregar -->
    <section class="panel" style="margin-top:1.25rem;">
        <div style="width:100%">
            <h2 style="margin:.25rem 0;">⚡ Agregar nuevo guerrero</h2>
        </div>
        <form id="form-agregar" style="display:flex; gap:1rem; flex-wrap:wrap; width:100%;">
            <div>
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100" />
            </div>
            <div>
                <label for="raza_input">Raza *</label>
                <input type="text" id="raza_input" name="raza" required maxlength="50" />
            </div>
            <div style="flex:1; min-width:280px;">
                <label for="descripcion">Descripción *</label>
                <textarea id="descripcion" name="descripcion" required rows="2"></textarea>
            </div>
            <div style="flex:1; min-width:280px;">
                <label for="imagen">URL Imagen</label>
                <input type="url" id="imagen" name="imagen" placeholder="https://..." />
            </div>
            <div>
                <label for="poder">Poder</label>
                <input type="text" id="poder" name="poder" placeholder="Ej: 9000" maxlength="50" />
            </div>
            <div>
                <label>&nbsp;</label>
                <input type="submit" value="Agregar" />
            </div>
        </form>
    </section>

    <!-- Mensajes simples (fallback si no hay SweetAlert2) -->
    <div id="msg" class="msg" style="display:none;"></div>

    <!-- Modal de Edición -->
    <div id="modal-editar" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>✏️ Editar Guerrero</h2>
            <form id="form-editar">
                <input type="hidden" id="edit-id" />
                <div>
                    <label for="edit-nombre">Nombre *</label>
                    <input type="text" id="edit-nombre" required maxlength="100" />
                </div>
                <div>
                    <label for="edit-raza">Raza *</label>
                    <input type="text" id="edit-raza" required maxlength="50" />
                </div>
                <div>
                    <label for="edit-descripcion">Descripción *</label>
                    <textarea id="edit-descripcion" required rows="3"></textarea>
                </div>
                <div>
                    <label for="edit-imagen">URL Imagen</label>
                    <input type="url" id="edit-imagen" placeholder="https://..." />
                </div>
                <div>
                    <label for="edit-poder">Poder</label>
                    <input type="text" id="edit-poder" maxlength="50" />
                </div>
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit">Guardar cambios</button>
                    <button type="button" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========================================================================
        // FUNCIÓN PARA MOSTRAR MENSAJES
        // ========================================================================
        function mostrarMsg(text, ok = true) {
            // Opción 1: Con SweetAlert2 (más elegante)
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: ok ? 'success' : 'error',
                    title: ok ? '¡Éxito!' : 'Error',
                    text: text,
                    timer: 2800,
                    showConfirmButton: false
                });
            } else {
                // Opción 2: Mensaje simple (fallback)
                const el = document.getElementById('msg');
                el.textContent = text;
                el.className = 'msg ' + (ok ? 'ok' : 'err');
                el.style.display = 'block';
                setTimeout(() => el.style.display = 'none', 2800);
            }
        }

        // ========================================================================
        // AGREGAR NUEVO GUERRERO
        // ========================================================================
        document.getElementById('form-agregar').addEventListener('submit', function(e) {
            e.preventDefault();

            const datos = {
                nombre: document.getElementById('nombre').value.trim(),
                raza: document.getElementById('raza_input').value.trim(),
                descripcion: document.getElementById('descripcion').value.trim(),
                imagen: document.getElementById('imagen').value.trim(),
                poder: document.getElementById('poder').value.trim()
            };

            if (!datos.nombre || !datos.raza || !datos.descripcion) {
                mostrarMsg('Faltan campos obligatorios.', false);
                return;
            }

            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        mostrarMsg('Error: ' + (res.error || 'No se pudo agregar'), false);
                        return;
                    }

                    mostrarMsg('Guerrero agregado correctamente.');

                    const id = res.id;
                    const p = res.data;

                    const card = document.createElement('article');
                    card.className = 'card';
                    card.id = 'card-' + id;
                    card.innerHTML = `
                        <div class="media">
                            <img src="${p.imagen || 'https://via.placeholder.com/300x200?text=Sin+Imagen'}" 
                                 alt="${p.nombre}"
                                 onerror="this.src='https://via.placeholder.com/300x200?text=Sin+Imagen'">
                        </div>
                        <div class="box">
                            <h3>${p.nombre}</h3>
                            <p class="muted">${p.descripcion}</p>
                            <p><span class="tag">${p.raza}</span></p>
                            <p class="poder"><strong>Poder:</strong> ${p.poder}</p>
                            <div style="margin-top:.5rem; display:flex; gap:.5rem;">
                                <button class="btn editar" data-id="${id}">Editar</button>
                                <button class="btn eliminar" data-id="${id}">Eliminar</button>
                            </div>
                        </div>
                    `;

                    // Insertar al principio del grid
                    const grid = document.getElementById('grid');
                    grid.prepend(card);

                    // Resetear formulario
                    e.target.reset();
                })
                .catch(err => {
                    mostrarMsg('Error de red.', false);
                    console.error(err);
                });
        });


        // eliminar guerrero

        document.getElementById('grid').addEventListener('click', async function(e) {
            const btn = e.target.closest('.btn.eliminar');
            if (!btn) return;

            const id = btn.dataset.id;
            if (!id) return;

            // Confirmación con SweetAlert2 o alert nativo
            let confirmar = false;
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });
                confirmar = result.isConfirmed;
            } else {
                confirmar = confirm('¿Eliminar este guerrero?');
            }

            if (!confirmar) return;

            fetch('api.php?id=' + encodeURIComponent(id), {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const art = document.getElementById('card-' + id);
                        if (art) art.remove();
                        mostrarMsg('Guerrero eliminado.');
                    } else {
                        mostrarMsg('No se pudo eliminar.', false);
                    }
                })
                .catch(err => {
                    mostrarMsg('Error de red.', false);
                    console.error(err);
                });
        });


        //  edicion

        document.getElementById('grid').addEventListener('click', function(e) {
            const btnEdit = e.target.closest('.btn.editar');
            if (!btnEdit) return;

            const id = btnEdit.dataset.id;
            const card = document.getElementById('card-' + id);

            // Obtener datos de la tarjeta
            const nombre = card.querySelector('h3').textContent;
            const descripcion = card.querySelector('.muted').textContent;
            const raza = card.querySelector('.tag').textContent;
            const poder = card.querySelector('.poder').textContent.replace('Poder:', '').trim();
            const imagen = card.querySelector('img').src;

            // Llenar formulario de edición
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-raza').value = raza;
            document.getElementById('edit-descripcion').value = descripcion;
            document.getElementById('edit-imagen').value = imagen;
            document.getElementById('edit-poder').value = poder;

            // Mostrar modal
            document.getElementById('modal-editar').style.display = 'flex';
        });


        // modal

        function cerrarModal() {
            document.getElementById('modal-editar').style.display = 'none';
        }


        // guardar edicion

        document.getElementById('form-editar').addEventListener('submit', function(e) {
            e.preventDefault();

            const id = document.getElementById('edit-id').value;
            const datos = {
                nombre: document.getElementById('edit-nombre').value.trim(),
                raza: document.getElementById('edit-raza').value.trim(),
                descripcion: document.getElementById('edit-descripcion').value.trim(),
                imagen: document.getElementById('edit-imagen').value.trim(),
                poder: document.getElementById('edit-poder').value.trim()
            };

            fetch('api.php?id=' + id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        mostrarMsg('Error: ' + (res.error || 'No se pudo actualizar'), false);
                        return;
                    }

                    mostrarMsg('Guerrero actualizado correctamente.');
                    cerrarModal();

                    // Actualizar la tarjeta en el DOM
                    const card = document.getElementById('card-' + id);
                    card.querySelector('h3').textContent = datos.nombre;
                    card.querySelector('.muted').textContent = datos.descripcion;
                    card.querySelector('.tag').textContent = datos.raza;
                    card.querySelector('.poder').innerHTML = '<strong>Poder:</strong> ' + datos.poder;
                    card.querySelector('img').src = datos.imagen || 'https://via.placeholder.com/300x200?text=Sin+Imagen';
                })
                .catch(err => {
                    mostrarMsg('Error de red.', false);
                    console.error(err);
                });
        });


        // CERRAR MODAL AL HACER CLIC FUERA

        window.onclick = function(event) {
            const modal = document.getElementById('modal-editar');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>

    //footer

    <footer class="footer">
        <p>
            <strong>Dragon Ball Z - Guerreros</strong> |
            Programación Web II - Final 2025
        </p>
        <p>
            Cappi Juan Manuel & Jonathan Gennari |
            Comisión: <strong>ACN2BV</strong>
        </p>
    </footer>

</body>

</html>