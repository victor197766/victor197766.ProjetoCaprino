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


    // --- AÇÕES DA CONTA ---
    const btnSuspender = document.getElementById('btnSuspender');
    const btnDeletar = document.getElementById('btnDeletar');
    const btnSair = document.getElementById('btnSair');

    // SUSPENDER CONTA
    if (btnSuspender) {
        btnSuspender.addEventListener('click', () => {
            const confirmado = confirm(
                'Deseja suspender sua conta mesmo?\n\n' +
                'Sua conta ficará inativa e você não poderá acessar o sistema até que a suspensão seja removida.'
            );
            if (confirmado) {
                fetch('suspendUser.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            window.location.href = 'recuperarConta.html';
                        }
                    })
                    .catch(() => {
                        alert('Erro de conexão. Tente novamente mais tarde.');
                    });
            }
        });
    }

    // DELETAR CONTA
    if (btnDeletar) {
        btnDeletar.addEventListener('click', () => {
            const confirmado = confirm(
                'Deseja deletar sua conta mesmo?\n\n' +
                'ATENÇÃO: Esta ação é IRREVERSÍVEL!\n' +
                'Todos os seus dados, lotes, animais e registros serão permanentemente excluídos.'
            );
            if (confirmado) {
                // Pede uma segunda confirmação por segurança
                const confirmadoFinal = confirm(
                    'Tem certeza ABSOLUTA?\n\n' +
                    'Clique em "OK" para confirmar a exclusão permanente da sua conta.'
                );
                if (confirmadoFinal) {
                    fetch('deleteOwnAccount.php', { method: 'POST' })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) {
                                window.location.href = 'recuperarConta.html';
                            }
                        })
                        .catch(() => {
                            alert('Erro de conexão. Tente novamente mais tarde.');
                        });
                }
            }
        });
    }

    // SAIR DA CONTA
    if (btnSair) {
        btnSair.addEventListener('click', () => {
            const confirmado = confirm('Deseja sair da conta mesmo?');
            if (confirmado) {
                window.location.href = 'logout.php';
            }
        });
    }
});