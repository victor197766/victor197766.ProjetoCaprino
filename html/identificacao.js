document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DO MENU HAMBÚRGUER ---
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

    // --- FUNCIONALIDADE: ALTERAÇÃO DE TEMA (SINCRONIA) ---
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

    // --- SELEÇÃO DE BOTÕES (ESPECIE E SEXO) ---
    const setupOptionButtons = (groupName) => {
        const buttons = document.querySelectorAll(`[data-group="${groupName}"]`);
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    };

    setupOptionButtons('especie');
    setupOptionButtons('sexo');

    // --- ENVIO DO FORMULÁRIO (SIMULADO) ---
    const form = document.getElementById('registroAnimalForm');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Animal registrado com sucesso (Simulação)');
        console.log('Dados salvos no ControlCabra');
    });
});