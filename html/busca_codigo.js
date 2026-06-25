document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU GAVETA ---
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

    // --- SINCRONIA DE TEMA ---
    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }
    }

    const temaSalvo = localStorage.getItem('controlCabra-theme') || 'sistema';
    aplicarTema(temaSalvo);

    // --- LÓGICA DE BUSCA ---
    const searchForm = document.getElementById('searchForm');
    const codigoInput = document.getElementById('codigoInput');

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita recarregar a página

        const codigoDigitado = codigoInput.value.trim();

        if (codigoDigitado === "") {
            alert("Por favor, digite um código de identificação válido.");
            return;
        }

        alert('Nenhum dado cadastrado para este código ainda.');
    });
});