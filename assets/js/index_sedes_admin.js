// ════════════════════════════════════════════════════════════════════
// JAVASCRIPT PARA CRUD DE SEDES
// Archivo: assets/js/index_sedes_admin.js
// ════════════════════════════════════════════════════════════════════

// Obtener elementos del DOM
const modal = document.getElementById('modalSede');
const btnNuevaSede = document.getElementById('btnNuevaSede');
const btnPrimeraSede = document.getElementById('btnPrimeraSede');
const cerrarModal = document.getElementById('cerrarModal');
const btnCancelar = document.getElementById('btnCancelar');
const formSede = document.getElementById('formSede');
const tituloModal = document.getElementById('tituloModal');

// Abrir modal para CREAR nueva sede
if(btnNuevaSede) {
    btnNuevaSede.addEventListener('click', function() {
        abrirModalCrear();
    });
}

if(btnPrimeraSede) {
    btnPrimeraSede.addEventListener('click', function() {
        abrirModalCrear();
    });
}

// Función para abrir modal en modo CREAR
function abrirModalCrear() {
    tituloModal.textContent = 'Nueva Sede';
    formSede.reset();
    document.getElementById('sede_id').value = '';
    modal.style.display = 'flex';
}

// Función para EDITAR sede (llamada desde PHP)
function editarSede(id) {
    tituloModal.textContent = 'Editar Sede';
    
    // Hacer petición AJAX para obtener datos de la sede
    fetch('../../controllers/ObtenerSede.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById('sede_id').value = data.sede.id;
                document.getElementById('nombre').value = data.sede.nombre;
                document.getElementById('ciudad').value = data.sede.ciudad;
                document.getElementById('direccion').value = data.sede.direccion || '';
                document.getElementById('telefono').value = data.sede.telefono || '';
                document.getElementById('email').value = data.sede.email || '';
                document.getElementById('estado').value = data.sede.estado;
                
                modal.style.display = 'flex';
            } else {
                alert('Error al cargar los datos de la sede');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos de la sede');
        });
}

// Función para CONFIRMAR eliminación
function confirmarEliminar(id, nombre) {
    if(confirm('¿Está seguro que desea eliminar la sede "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        // Redirigir al controlador de eliminar
        window.location.href = '../../controllers/EliminarSede.php?id=' + id;
    }
}

// Cerrar modal
if(cerrarModal) {
    cerrarModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
}

if(btnCancelar) {
    btnCancelar.addEventListener('click', function() {
        modal.style.display = 'none';
    });
}

// Cerrar modal al hacer clic fuera
window.addEventListener('click', function(event) {
    if(event.target === modal) {
        modal.style.display = 'none';
    }
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(event) {
    if(event.key === 'Escape' && modal.style.display === 'flex') {
        modal.style.display = 'none';
    }
});