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
        const hiddenInput = document.getElementById(`input${groupName.charAt(0).toUpperCase() + groupName.slice(1)}`);
        
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (hiddenInput) hiddenInput.value = btn.dataset.value;

                // Lógica especial para exibir/esconder Prenha
                if (groupName === 'sexo') {
                    const groupPrenha = document.getElementById('groupPrenha');
                    if (groupPrenha) {
                        groupPrenha.style.display = btn.dataset.value === 'Fêmea' ? 'block' : 'none';
                        if (btn.dataset.value !== 'Fêmea') {
                            document.getElementById('checkPrenha').checked = false;
                            document.getElementById('tempoGestacao').disabled = true;
                            document.getElementById('tempoGestacao').value = '';
                        }
                    }
                }
            });
        });
    };

    setupOptionButtons('especie');
    setupOptionButtons('sexo');

    const checkPrenha = document.getElementById('checkPrenha');
    const tempoGestacao = document.getElementById('tempoGestacao');
    if (checkPrenha && tempoGestacao) {
        checkPrenha.addEventListener('change', (e) => {
            tempoGestacao.disabled = !e.target.checked;
            if (!e.target.checked) tempoGestacao.value = '';
        });
    }

    // --- VALIDAÇÃO E ENVIO DO FORMULÁRIO ---
    const form = document.getElementById('registroAnimalForm');
    const errBox = document.getElementById('form-error');

    function mostrarErro(msg) {
        errBox.textContent = msg;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function limparErro() { errBox.style.display = 'none'; }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        limparErro();

        // Campos obrigatórios
        const peso        = document.getElementById('peso')?.value.trim();
        const idade       = document.getElementById('idade')?.value.trim();
        const identificador = document.getElementById('identificador')?.value.trim();
        const raca        = document.getElementById('raca')?.value.trim();

        if (!peso || parseFloat(peso) <= 0) {
            mostrarErro('Informe um peso válido (maior que 0 Kg) para o animal.');
            return;
        }
        if (!idade) {
            mostrarErro('Informe a idade do animal antes de salvar.');
            return;
        }
        if (!identificador) {
            mostrarErro('O número do brinco, tatuagem ou microchip é obrigatório.');
            return;
        }
        if (!raca) {
            mostrarErro('Informe a raça do animal.');
            return;
        }

        // Tudo OK — enviar para o servidor
        limparErro();
        form.submit();
    });
});