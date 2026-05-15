document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU HAMBÚRGUER (MOBILE) ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar && sidebarOverlay) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    // --- LEITURA DO TEMA SALVO (Sincronizado com configurações) ---
    const temaSalvo = localStorage.getItem('controlCabra-theme') || 'sistema';
    
    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }
    }

    // Aplica na inicialização da página
    aplicarTema(temaSalvo);

    // Monitora alterações de tema nativo do sistema do usuário
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('controlCabra-theme') === 'sistema') {
            aplicarTema('sistema');
        }
    });

});