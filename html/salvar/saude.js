document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU HAMBÚRGUER (MOBILE) ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar && sidebarOverlay) {
        menuToggle.addEventListener('click', () => {
            // CORREÇÃO: No CSS as classes se chamam '.open', não '.active'
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('open');
        });

        // Fecha o menu se o usuário clicar no fundo escuro
        sidebarOverlay.addEventListener('click', () => {
            // CORREÇÃO: Removendo 'open' em vez de 'active'
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('open');
        });
    }

    // --- LEITURA DO TEMA SALVO (Sincronizado com as configurações gerais) ---
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

    // Monitora alterações caso o tema do sistema operacional do usuário mude
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('controlCabra-theme') === 'sistema') {
            aplicarTema('sistema');
        }
    });

});