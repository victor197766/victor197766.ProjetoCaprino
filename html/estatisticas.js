document.addEventListener('DOMContentLoaded', () => {
    
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