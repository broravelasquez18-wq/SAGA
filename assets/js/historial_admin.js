// ============================================
// CONTROL DE MENÚ SIDEBAR
// ============================================

const btnMenu = document.getElementById("btnMenu");
const modalMenu = document.getElementById("modalMenu");

if(btnMenu && modalMenu) {
    btnMenu.addEventListener("click", () => {
        modalMenu.classList.add("active");
    });

    modalMenu.addEventListener("click", (e) => {
        if(e.target === modalMenu) {
            modalMenu.classList.remove("active");
        }
    });
}

// ============================================
// AUTO-REFRESH (Opcional - Comentado)
// ============================================

// Descomenta para actualizar automáticamente cada 30 segundos
/*
setInterval(() => {
    location.reload();
}, 30000); // 30 segundos
*/

// ============================================
// SMOOTH SCROLL (Opcional)
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});