<?php
require_once 'Guerrero.php';

// instancia del objeto Guerrero
$g = new Guerrero();

$buscar = $_GET['buscar'] ?? '';
$raza = $_GET['raza'] ?? '';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));

$guerreros = $g->todos($buscar, $raza, $pagina);
$total = $g->total($buscar, $raza);
$paginas = ceil($total / 8);
$razas = $g->razas();

// funcion para mantener los parametros en la paginacion
function params()
{
    $p = $_GET;
    unset($p['pagina']);
    return http_build_query($p);
}
?>

<!-- Filtros -->
<form class="panel" method="get">
    <div><label>Buscar</label><input type="text" name="buscar" value="<?= h($buscar) ?>" placeholder="Ej: Goku"></div>
    <div><label>Raza</label>
        <select name="raza">
            <option value="">Todas</option>
            <?php foreach ($razas as $r): ?>
                <option value="<?= h($r) ?>" <?= $raza == $r ? 'selected' : '' ?>><?= h($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><label>Tema</label>
        <select name="tema">
            <option value="claro" <?= ($_GET['tema'] ?? 'claro') == 'claro' ? 'selected' : '' ?>>Claro</option>
            <option value="oscuro" <?= ($_GET['tema'] ?? '') == 'oscuro' ? 'selected' : '' ?>>Oscuro</option>
        </select>
    </div>
    <div><label>&nbsp;</label><input type="submit" value="Aplicar"></div>
</form>

<!-- Grid -->
<section class="grid" id="grid">
    <?php if (empty($guerreros)): ?>
        <p class="muted">No se encontraron guerreros.</p>
        <?php else: foreach ($guerreros as $p): ?>
            <article class="card" id="card-<?= $p['id'] ?>">
                <div class="media">
                    <img src="<?= h($p['imagen'] ?: 'https://via.placeholder.com/300x200?text=Sin+Imagen') ?>"
                        alt="<?= h($p['nombre']) ?>"
                        onerror="this.src='https://via.placeholder.com/300x200?text=Sin+Imagen'">
                </div>
                <div class="box">
                    <h3><?= h($p['nombre']) ?></h3>
                    <p class="muted"><?= h($p['descripcion']) ?></p>
                    <p><span class="tag"><?= h($p['raza']) ?></span></p>
                    <p class="poder"><strong>Poder:</strong> <?= h($p['poder']) ?></p>
                    <div style="margin-top:.5rem; display:flex; gap:.5rem;">
                        <button class="btn editar" data-id="<?= $p['id'] ?>">Editar</button>
                        <button class="btn eliminar" data-id="<?= $p['id'] ?>">Eliminar</button>
                    </div>
                </div>
            </article>
    <?php endforeach;
    endif; ?>
</section>

<!-- Paginación -->
<?php if ($paginas > 1): ?>
    <div class="paginacion">
        <?= $pagina > 1 ? '<a href="?' . params() . '&pagina=' . ($pagina - 1) . '" class="btn-paginacion">Anterior</a>' : '' ?>
        <?php for ($i = 1; $i <= $paginas; $i++): ?>
            <?php if ($i == $pagina): ?><span class="pagina-actual"><?= $i ?></span>
            <?php else: ?><a href="?<?= params() ?>&pagina=<?= $i ?>" class="btn-paginacion"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <?= $pagina < $paginas ? '<a href="?' . params() . '&pagina=' . ($pagina + 1) . '" class="btn-paginacion">Siguiente</a>' : '' ?>
    </div>
<?php endif; ?>

<!-- Formulario Agregar -->
<section class="panel" style="margin-top:2rem; display: block;">
    <h2>Agregar Nuevo Guerrero</h2>
    <form id="form-agregar">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">

            <div><label>Nombre *</label><input type="text" name="nombre" required></div>
            <div><label>Raza *</label><input type="text" name="raza" required></div>
            <div><label>Descripción *</label><textarea name="descripcion" required rows="2"></textarea></div>
            <div><label>URL Imagen</label><input type="url" name="imagen"></div>
            <div><label>Poder</label><input type="text" name="poder"></div>
            <div style="align-self:end;"><input type="submit" value="Agregar"></div>
        </div>
    </form>
</section>

<!-- Modal Editar -->
<div id="modal-editar" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">×</span>
        <h2>Editar Guerrero</h2>
        <form id="form-editar">
            <input type="hidden" id="edit-id">
            <div><label>Nombre *</label><input type="text" id="edit-nombre" required></div>
            <div><label>Raza *</label><input type="text" id="edit-raza" required></div>
            <div><label>Descripción *</label><textarea id="edit-descripcion" required rows="3"></textarea></div>
            <div><label>URL Imagen</label><input type="url" id="edit-imagen"></div>
            <div><label>Poder</label><input type="text" id="edit-poder"></div>
            <div style="margin-top:1rem;">
                <button type="submit">Guardar</button>
                <button type="button" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Mensaje dinamico  -->
 <div id="msg" class="msg" style="display:none;"></div>