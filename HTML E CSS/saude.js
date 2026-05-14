document.addEventListener('DOMContentLoaded', () => {
    // Lógica para abrir/fechar o menu lateral na versão Mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if(menuToggle) {
        menuToggle.addEventListener('click', () => {
            // Alterna entre display flex e none
            if (sidebar.style.display === 'flex') {
                sidebar.style.display = 'none';
            } else {
                sidebar.style.display = 'flex';
            }
        });
    }

    // Funcionalidade extra: Interatividade visual básica nos botões de "Saiba mais"
    const linkActions = document.querySelectorAll('.link-action, .btn-outline');
    linkActions.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Apenas para simular uma ação no protótipo visual
            console.log('Ação clicada:', link.textContent.trim());
        });
    });
});