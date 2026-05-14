document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DE TEMA (Claro, Escuro, Sistema) ---
    const preferemTemaEscuro = window.matchMedia('(prefers-color-scheme: dark)');
    
    function aplicarTema(escolha) {
        if (escolha === 'sistema') {
            // Se for sistema, checa a preferência da máquina
            const eEscuro = preferemTemaEscuro.matches;
            document.documentElement.setAttribute('data-theme', eEscuro ? 'escuro' : 'claro');
        } else {
            // Se for 'claro' ou 'escuro', aplica diretamente
            document.documentElement.setAttribute('data-theme', escolha);
        }
    }

    // Puxa o tema salvo ou usa 'sistema' como padrão no primeiro acesso
    const temaSalvo = localStorage.getItem('tema-escolhido') || 'sistema';
    aplicarTema(temaSalvo);

    // Se o usuário mudar o tema do Windows/Mac, e estiver na opção 'sistema', atualiza na hora
    preferemTemaEscuro.addEventListener('change', () => {
        if (localStorage.getItem('tema-escolhido') === 'sistema') {
            aplicarTema('sistema');
        }
    });

    // --- FUNCIONALIDADE DOS BOTÕES DE OPÇÃO ---
    const optionButtons = document.querySelectorAll('.option-btn');
    
    // Prepara os botões corretos com a classe "active" baseado no cache do navegador
    optionButtons.forEach(btn => {
        if (btn.getAttribute('data-group') === 'theme') {
            if (btn.getAttribute('data-tema') === temaSalvo) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });
    
    // Quando qualquer botão for clicado
    optionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const groupName = this.getAttribute('data-group');
            
            // Remove a classe 'active' de todos os botões do mesmo grupo (ex: theme ou layout)
            const groupButtons = document.querySelectorAll(`.option-btn[data-group="${groupName}"]`);
            groupButtons.forEach(btn => btn.classList.remove('active'));
            
            // Adiciona a classe 'active' ao botão clicado
            this.classList.add('active');

            // --- AÇÃO ESPECÍFICA DO TEMA ---
            if (groupName === 'theme') {
                const novaEscolha = this.getAttribute('data-tema');
                // Salva no navegador e aplica
                localStorage.setItem('tema-escolhido', novaEscolha);
                aplicarTema(novaEscolha);
            }
        });
    });

    // (A parte do JS dos color-circles foi completamente removida como solicitado)
});