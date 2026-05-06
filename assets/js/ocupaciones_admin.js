// Control de menú
const btnMenu = document.getElementById("btnMenu");
const modalMenu = document.getElementById("modalMenu");

if(btnMenu && modalMenu) {
    btnMenu.addEventListener("click", () => modalMenu.classList.add("active"));
    modalMenu.addEventListener("click", (e) => {
        if(e.target === modalMenu) modalMenu.classList.remove("active");
    });
}

// Control de modal de ocupación
function abrirModal() {
    document.getElementById('modalOcupacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalOcupacion').style.display = 'none';
}

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') cerrarModal();
});

// Auto-refresh cada 2 minutos para actualizar estados
setInterval(() => {
    location.reload();
}, 120000); // 2 minutos