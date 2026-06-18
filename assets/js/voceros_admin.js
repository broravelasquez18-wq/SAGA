const btnMenu   = document.getElementById("btnMenu");
const modalMenu = document.getElementById("modalMenu");
if(btnMenu && modalMenu) {
    btnMenu.addEventListener("click", () => modalMenu.classList.add("active"));
    modalMenu.addEventListener("click", (e) => { if(e.target === modalMenu) modalMenu.classList.remove("active"); });
}

const mensajeAlert = document.getElementById('mensajeAlert');
if(mensajeAlert) {
    setTimeout(() => { mensajeAlert.style.opacity = '0'; setTimeout(() => mensajeAlert.style.display = 'none', 300); }, 5000);
}

function abrirModal() {
    document.getElementById('modalVocero').style.display = 'flex';
    document.getElementById('modalTitulo').textContent   = 'Nuevo Vocero';
    document.getElementById('btnTexto').textContent      = '💾 Guardar Vocero';
    document.getElementById('accion').value       = 'crear';
    document.getElementById('voceroId').value     = '';
    document.getElementById('voceroNombre').value    = '';
    document.getElementById('voceroApellido').value  = '';
    document.getElementById('voceroCedula').value    = '';
    document.getElementById('voceroEmail').value     = '';
    document.getElementById('voceroTelefono').value  = '';
    document.getElementById('voceroContrasena').value = '';
    document.getElementById('voceroNivel').value     = '';
    document.getElementById('voceroPrograma').value  = '';
    document.getElementById('voceroFechaInicio').value = '';
    document.getElementById('voceroFechaFin').value    = '';
    document.getElementById('fechasNivel').style.display     = 'none';
    document.getElementById('contrasenaAyuda').style.display = 'none';
    document.getElementById('voceroContrasena').required     = true;
    document.getElementById('voceroFechaInicio').required    = false;
    document.getElementById('voceroFechaFin').required       = false;
    document.getElementById('voceroFechaFin').readOnly       = false;
    document.getElementById('voceroFechaFin').style.background = '';
}

function cerrarModal() {
    document.getElementById('modalVocero').style.display = 'none';
}

function cambiarNivel() {
    const nivel       = document.getElementById('voceroNivel').value;
    const fechasDiv   = document.getElementById('fechasNivel');
    const inputInicio = document.getElementById('voceroFechaInicio');
    const inputFin    = document.getElementById('voceroFechaFin');
    const ayuda       = document.getElementById('ayudaFechaFin');

    if(nivel === '') {
        fechasDiv.style.display = 'none';
        inputInicio.required = false;
        inputFin.required    = false;
        return;
    }

    fechasDiv.style.display = 'block';
    inputInicio.required = true;
    inputFin.required    = true;

    if(nivel === 'tecnico') {
        ayuda.textContent    = 'Se calcula automáticamente: 1 año desde la fecha de inicio';
        ayuda.style.display  = 'block';
        inputFin.readOnly    = true;
        inputFin.style.background = '#f0f0f0';
    } else if(nivel === 'tecnologo') {
        ayuda.textContent    = 'Se calcula automáticamente: 2 años desde la fecha de inicio';
        ayuda.style.display  = 'block';
        inputFin.readOnly    = true;
        inputFin.style.background = '#f0f0f0';
    } else {
        ayuda.textContent        = '';
        ayuda.style.display      = 'none';
        inputFin.readOnly        = false;
        inputFin.style.background = '';
    }

    // Recalculate if there is already a start date
    autoFechaFin();
}

function autoFechaFin() {
    const nivel  = document.getElementById('voceroNivel').value;
    const inicio = document.getElementById('voceroFechaInicio').value;
    if(!inicio) return;

    const fecha = new Date(inicio + 'T00:00:00');
    if(nivel === 'tecnico') {
        fecha.setFullYear(fecha.getFullYear() + 1);
        document.getElementById('voceroFechaFin').value = fecha.toISOString().slice(0, 10);
    } else if(nivel === 'tecnologo') {
        fecha.setFullYear(fecha.getFullYear() + 2);
        document.getElementById('voceroFechaFin').value = fecha.toISOString().slice(0, 10);
    }
    // complementario: no auto-fill
}

document.addEventListener('DOMContentLoaded', () => {
    const fi = document.getElementById('voceroFechaInicio');
    if(fi) fi.addEventListener('change', autoFechaFin);
});

function editar(id) {
    document.getElementById('modalVocero').style.display  = 'flex';
    document.getElementById('modalTitulo').textContent    = 'Editar Vocero';
    document.getElementById('btnTexto').textContent       = '💾 Actualizar Vocero';
    document.getElementById('accion').value   = 'editar';
    document.getElementById('voceroId').value = id;
    document.getElementById('contrasenaAyuda').style.display  = 'block';
    document.getElementById('voceroContrasena').required = false;

    fetch(`../../controllers/ObtenerVocero.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                const v = data.vocero;
                document.getElementById('voceroNombre').value   = v.nombre;
                document.getElementById('voceroApellido').value = v.apellido;
                document.getElementById('voceroCedula').value   = v.cedula;
                document.getElementById('voceroEmail').value    = v.email || '';
                document.getElementById('voceroTelefono').value = v.telefono || '';
                document.getElementById('voceroNivel').value    = v.nivel_estudio    || '';
                document.getElementById('voceroPrograma').value = v.programa_estudio || '';

                // Trigger level UI before setting dates
                cambiarNivel();

                document.getElementById('voceroFechaInicio').value = v.fecha_inicio_contrato || '';
                document.getElementById('voceroFechaFin').value    = v.fecha_fin_contrato    || '';
            }
        });
}

function eliminar(id, nombre) {
    if(confirm(`¿Eliminar al vocero "${nombre}"?`)) {
        window.location.href = `../../controllers/EliminarVocero.php?id=${id}&sede_id=${sedeId}`;
    }
}

function activar(id) {
    if(confirm('¿Activar este vocero?')) {
        window.location.href = `../../controllers/CambiarEstadoVocero.php?id=${id}&estado=activo&sede_id=${sedeId}`;
    }
}

function desactivar(id) {
    if(confirm('¿Desactivar este vocero?')) {
        window.location.href = `../../controllers/CambiarEstadoVocero.php?id=${id}&estado=inactivo&sede_id=${sedeId}`;
    }
}

function filtrar() {
    const buscar = document.getElementById('buscarCelador').value.toLowerCase();
    const estado = document.getElementById('filtrarEstado').value;
    const nivel  = document.getElementById('filtrarNivel').value;
    document.querySelectorAll('.celador-card').forEach(card => {
        const matchNombre = card.dataset.nombre.includes(buscar) || card.dataset.cedula.includes(buscar);
        const matchEstado = !estado || card.dataset.estado === estado;
        const matchNivel  = !nivel  || card.dataset.nivel  === nivel;
        card.style.display = (matchNombre && matchEstado && matchNivel) ? '' : 'none';
    });
}

function ordenar() {
    const criterio = document.getElementById('ordenar').value;
    const grid = document.getElementById('verocerosGrid');
    const cards = Array.from(grid.querySelectorAll('.celador-card'));
    cards.sort((a, b) => {
        if(criterio === 'nombre') return a.dataset.nombre.localeCompare(b.dataset.nombre);
        if(criterio === 'cedula') return a.dataset.cedula.localeCompare(b.dataset.cedula);
        if(criterio === 'estado') return a.dataset.estado.localeCompare(b.dataset.estado);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

const formVocero = document.getElementById('formVocero');
if(formVocero) {
    formVocero.addEventListener('submit', function(e) {
        const accion = document.getElementById('accion').value;
        const clave  = document.getElementById('voceroContrasena').value.trim();
        if(accion === 'crear' && clave === '') {
            e.preventDefault();
            alert('La contraseña es obligatoria.');
            document.getElementById('voceroContrasena').focus();
        }
    });
}

document.addEventListener('keydown', (e) => { if(e.key === 'Escape') cerrarModal(); });
const modalVocero = document.getElementById('modalVocero');
if(modalVocero) {
    modalVocero.addEventListener('click', function(e) { if(e.target === this) cerrarModal(); });
}
