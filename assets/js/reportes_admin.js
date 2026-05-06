// MENÚ
const btnMenu = document.getElementById("btnMenu");
const modalMenu = document.getElementById("modalMenu");

btnMenu.addEventListener("click", () => modalMenu.classList.add("active"));
modalMenu.addEventListener("click", (e) => {
    if(e.target === modalMenu) modalMenu.classList.remove("active");
});

// GENERAR REPORTE OCUPACIONES CON FECHAS
function generarReporteOcupaciones(formato) {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    if(!fechaInicio || !fechaFin) {
        alert('Por favor selecciona un rango de fechas');
        return;
    }
    
    if(new Date(fechaInicio) > new Date(fechaFin)) {
        alert('La fecha de inicio no puede ser mayor que la fecha fin');
        return;
    }
    
    // Redirigir al controlador con los parámetros
    window.location.href = `../../controllers/ReporteOcupaciones.php?formato=${formato}&inicio=${fechaInicio}&fin=${fechaFin}`;
}