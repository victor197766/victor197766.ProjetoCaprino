document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU HAMBÚRGUER (IGUAL AO SEU MENU DE SAÚDE) ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar && sidebarOverlay) {
        // Abre ou fecha o menu ao clicar no botão hambúrguer ☰
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        // Fecha o menu se o usuário clicar no fundo escuro escurecido
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }


    // --- FUNCIONALIDADE: ALTERAÇÃO DE TEMA DO SISTEMA ---
    const themeButtons = document.querySelectorAll('[data-group="theme"]');
    
    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }
        
        // Atualiza o estado visual do botão ativo
        themeButtons.forEach(btn => {
            if (btn.getAttribute('data-tema') === tema) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        // Mantém a escolha mesmo após dar Recarregar (F5)
        localStorage.setItem('controlCabra-theme', tema);
    }

    // Inicialização do tema salvo no cache
    const temaSalvo = localStorage.getItem('controlCabra-theme') || 'sistema';
    aplicarTema(temaSalvo);

    themeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const temaSelecionado = button.getAttribute('data-tema');
            aplicarTema(temaSelecionado);
        });
    });

    // Monitora caso o sistema mude automaticamente o tema nativo
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('controlCabra-theme') === 'sistema') {
            aplicarTema('sistema');
        }
    });


    // --- SELEÇÃO DE LAYOUT VISUAL ---
    const layoutButtons = document.querySelectorAll('[data-group="layout"]');
    layoutButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            layoutButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
});