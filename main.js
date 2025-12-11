// funcion 
function msg(text, ok = true) {
    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? '¡Éxito!' : 'Error', text, timer: 2500, showConfirmButton: false });
}

// funcion para agregar un nuevo guerrero dinamico sin recarcar la pagina
document.getElementById('form-agregar').onsubmit = async function (e) {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(this));
    const res = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
    const json = await res.json();
    if (json.success) {
        msg('Guerrero agregado');
        location.Reload();
    } else msg(json.error || 'Error', false);
};

// funcion para eliminar un guerrero dinamico sin recarcar la pagina
document.getElementById('grid').onclick = async function (e) {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.dataset.id;

    if (btn.classList.contains('eliminar')) {
        if (!confirm('¿Eliminar guerrero?')) return;
        await fetch(`api.php?id=${id}`, { method: 'DELETE' });
        document.getElementById('card-' + id).remove();
        msg('Eliminado');
    }

    // funcion para editar un guerrero dinamico sin recarcar la pagina
    if (btn.classList.contains('editar')) {
        const card = btn.closest('.card');
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nombre').value = card.querySelector('h3').textContent;
        document.getElementById('edit-raza').value = card.querySelector('.tag').textContent;
        document.getElementById('edit-descripcion').value = card.querySelector('.muted').textContent;
        document.getElementById('edit-imagen').value = card.querySelector('img').src;
        document.getElementById('edit-poder').value = card.querySelector('.poder').textContent.replace('Poder: ', '').trim();
        document.getElementById('modal-editar').style.display = 'flex';
    }
};

// funcion para cerrar el modal de editar
function cerrarModal() { document.getElementById('modal-editar').style.display = 'none'; }


// funcion para actualizar un guerrero dinamico sin recarcar la pagina
document.getElementById('form-editar').onsubmit = async function (e) {
    e.preventDefault();
    const id = document.getElementById('edit-id').value;
    const datos = {
        nombre: document.getElementById('edit-nombre').value,
        raza: document.getElementById('edit-raza').value,
        descripcion: document.getElementById('edit-descripcion').value,
        imagen: document.getElementById('edit-imagen').value,
        poder: document.getElementById('edit-poder').value
    };

    // enviar datos al servidor
    await fetch(`api.php?id=${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
    msg('Actualizado');
    location.reload();
};