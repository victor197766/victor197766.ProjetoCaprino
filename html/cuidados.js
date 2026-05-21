document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU HAMBÚRGUER (GAVETA MOBILE) ---
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

    // --- FUNCIONALIDADE: TEMA (CLARO/ESCURO) ---
    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }
        localStorage.setItem('controlCabra-theme', tema);
    }

    // Verifica cache ao carregar
    const temaSalvo = localStorage.getItem('controlCabra-theme') || 'sistema';
    aplicarTema(temaSalvo);

    // Monitora caso o sistema do usuário mude para modo escuro automaticamente
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('controlCabra-theme') === 'sistema') {
            aplicarTema('sistema');
        }
    });
});