document.addEventListener('DOMContentLoaded', () => {

    // =============================================
    // TEMA
    // =============================================
    (function () {
        const t = localStorage.getItem('controlCabra-theme') || 'sistema';
        if (t === 'sistema') {
            document.documentElement.setAttribute('data-theme',
                window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', t);
        }
    })();

    // =============================================
    // MENU HAMBÚRGUER (mobile)
    // =============================================
    const menuToggle    = document.getElementById('menuToggle');
    const sidebar       = document.getElementById('sidebar');
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

    // =============================================
    // ABAS (TAB SWITCHER)
    // =============================================
    const tabBtns   = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    function switchTab(tabName) {
        tabBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabName));
        tabPanels.forEach(panel => panel.classList.toggle('active', panel.id === `tab-${tabName}`));
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // =============================================
    // FEEDBACK (ler params da URL)
    // =============================================
    const params      = new URLSearchParams(window.location.search);
    const feedbackBar = document.getElementById('feedback-bar');
    const formErrBox  = document.getElementById('form-error');

    if (params.get('sucesso') === '1') {
        // Registro de novo animal
        showFeedback(feedbackBar, 'Animal registrado com sucesso!', 'success');
        switchTab('lista');
    } else if (params.get('sucesso') === 'editado') {
        showFeedback(feedbackBar, 'Animal atualizado com sucesso!', 'success');
        switchTab('lista');
    } else if (params.get('sucesso') === 'deletado') {
        showFeedback(feedbackBar, 'Animal excluído com sucesso.', 'success');
        switchTab('lista');
    } else if (params.get('erro')) {
        showFeedback(feedbackBar, 'Erro: ' + decodeURIComponent(params.get('erro')), 'error');
    }

    // Limpa o parâmetro da URL sem reload
    if (params.has('sucesso') || params.has('erro')) {
        history.replaceState(null, '', window.location.pathname);
    }

    function showFeedback(el, msg, type) {
        if (!el) return;
        el.textContent = msg;
        el.className   = 'feedback-bar ' + (type === 'success' ? 'is-success' : 'is-error');
        el.style.display = 'block';
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; }, 500);
        }, 4000);
    }

    // =============================================
    // BOTÕES ESPÉCIE / SEXO — FORMULÁRIO DE CADASTRO
    // =============================================
    function setupOptionButtons(groupName, hiddenInputId) {
        const btns = document.querySelectorAll(`[data-group="${groupName}"]`);
        const inp  = document.getElementById(hiddenInputId);
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (inp) inp.value = btn.dataset.value;

                if (groupName === 'sexo') {
                    const gp = document.getElementById('groupPrenha');
                    if (gp) {
                        gp.style.display = btn.dataset.value === 'Fêmea' ? 'block' : 'none';
                        if (btn.dataset.value !== 'Fêmea') {
                            const cp = document.getElementById('checkPrenha');
                            const tg = document.getElementById('tempoGestacao');
                            if (cp) cp.checked = false;
                            if (tg) { tg.disabled = true; tg.value = ''; }
                        }
                    }
                }
            });
        });
    }

    setupOptionButtons('especie', 'inputEspecie');
    setupOptionButtons('sexo',    'inputSexo');

    // Checkbox prenha (cadastro)
    const checkPrenha   = document.getElementById('checkPrenha');
    const tempoGestacao = document.getElementById('tempoGestacao');
    if (checkPrenha && tempoGestacao) {
        checkPrenha.addEventListener('change', e => {
            tempoGestacao.disabled = !e.target.checked;
            if (!e.target.checked) tempoGestacao.value = '';
        });
    }

    // =============================================
    // VALIDAÇÃO DO FORMULÁRIO DE CADASTRO
    // =============================================
    const formCadastro = document.getElementById('registroAnimalForm');
    if (formCadastro) {
        formCadastro.addEventListener('submit', e => {
            e.preventDefault();
            if (formErrBox) formErrBox.style.display = 'none';

            const peso        = document.getElementById('peso')?.value.trim();
            const idade       = document.getElementById('idade')?.value.trim();
            const identificador = document.getElementById('identificador')?.value.trim();
            const raca        = document.getElementById('raca')?.value.trim();

            if (!peso || parseFloat(peso) <= 0) {
                showFeedback(formErrBox, 'Informe um peso válido (maior que 0 Kg).', 'error');
                return;
            }
            if (!idade) {
                showFeedback(formErrBox, 'Informe a idade do animal.', 'error');
                return;
            }
            if (!identificador) {
                showFeedback(formErrBox, 'O identificador (brinco/tatuagem/microchip) é obrigatório.', 'error');
                return;
            }
            if (!raca) {
                showFeedback(formErrBox, 'Informe a raça do animal.', 'error');
                return;
            }

            formCadastro.submit();
        });
    }

    // =============================================
    // BUSCA E FILTRO DA LISTA
    // =============================================
    const searchInput  = document.getElementById('searchAnimal');
    const filterSelect = document.getElementById('filterSelect');
    const noResults    = document.getElementById('noResults');

    function filterAnimals() {
        const q = searchInput  ? searchInput.value.toLowerCase()  : '';
        const f = filterSelect ? filterSelect.value               : 'todos';
        let visible = 0;

        document.querySelectorAll('.animal-item').forEach(item => {
            const textMatch = (item.dataset.search || '').includes(q);
            let filterMatch = true;

            if (f === 'caprino'  && item.dataset.especie !== 'caprino')  filterMatch = false;
            if (f === 'ovino'    && item.dataset.especie !== 'ovino')    filterMatch = false;
            if (f === 'macho'    && item.dataset.sexo    !== 'macho')    filterMatch = false;
            if (f === 'femea'    && item.dataset.sexo    !== 'fêmea' && item.dataset.sexo !== 'femea') filterMatch = false;
            if (f === 'lote'     && item.dataset.lote    !== 'sim')      filterMatch = false;
            if (f === 'sem_lote' && item.dataset.lote    !== 'nao')      filterMatch = false;

            const show = textMatch && filterMatch;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    if (searchInput)  searchInput.addEventListener('input',  filterAnimals);
    if (filterSelect) filterSelect.addEventListener('change', filterAnimals);

    // =============================================
    // MODAL DE EDIÇÃO
    // =============================================
    const modalEdicao   = document.getElementById('modalEdicao');
    const fecharModal   = document.getElementById('fecharModal');
    const cancelarEdit  = document.getElementById('cancelarEdicao');

    function abrirModalEdicao(animal) {
        // Popula inputs simples
        document.getElementById('edit_animal_id').value    = animal.id        ?? '';
        document.getElementById('edit_nome').value         = animal.nome       ?? '';
        document.getElementById('edit_raca').value         = animal.raca       ?? '';
        document.getElementById('edit_peso').value         = animal.peso_kg    ?? '';
        document.getElementById('edit_idade').value        = animal.idade      ?? '';
        document.getElementById('edit_tratamento').value   = animal.estado_atual ?? '';
        document.getElementById('edit_identificador').value = animal.identificador ?? '';
        document.getElementById('edit_info').value         = animal.info_extras  ?? '';

        // Checkboxes
        document.getElementById('edit_nasceu').checked    = animal.nascimento_fazenda == 1;
        document.getElementById('edit_vacinado').checked  = animal.vacinado_prev == 1;

        // Prenha
        const gp        = document.getElementById('edit_groupPrenha');
        const chkPrenha = document.getElementById('edit_prenha');
        const tgEst     = document.getElementById('edit_gestacao');
        const isFemea   = (animal.sexo ?? '').toLowerCase() === 'fêmea' || (animal.sexo ?? '').toLowerCase() === 'femea';
        if (gp)        gp.style.display  = isFemea ? 'block' : 'none';
        if (chkPrenha) { chkPrenha.checked = animal.esta_prenha == 1; }
        if (tgEst)     { tgEst.disabled = !(animal.esta_prenha == 1); tgEst.value = animal.tempo_gestacao ?? ''; }

        // Botões espécie e sexo no modal
        setEditOption('edit_especie', 'edit_inputEspecie', animal.especie ?? 'Caprino');
        setEditOption('edit_sexo',    'edit_inputSexo',    animal.sexo    ?? 'Macho');

        // Selects
        setSelectValue('edit_lote_id',   animal.lote_id      ?? '');
        setSelectValue('edit_reprodutor', animal.reprodutor_id ?? '');
        setSelectValue('edit_matriz',     animal.matriz_id    ?? '');

        modalEdicao.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function setEditOption(group, hiddenId, value) {
        const btns = document.querySelectorAll(`[data-group="${group}"]`);
        const inp  = document.getElementById(hiddenId);
        btns.forEach(b => {
            const match = b.dataset.value === value;
            b.classList.toggle('active', match);
        });
        if (inp) inp.value = value;
    }

    function setSelectValue(id, value) {
        const sel = document.getElementById(id);
        if (!sel) return;
        const strVal = String(value ?? '');
        for (const opt of sel.options) {
            if (String(opt.value) === strVal) { opt.selected = true; return; }
        }
        sel.selectedIndex = 0; // fallback
    }

    function fecharModalEdicao() {
        modalEdicao.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (fecharModal)  fecharModal.addEventListener('click',  fecharModalEdicao);
    if (cancelarEdit) cancelarEdit.addEventListener('click', fecharModalEdicao);
    modalEdicao?.addEventListener('click', e => { if (e.target === modalEdicao) fecharModalEdicao(); });

    // Botões espécie/sexo do modal (lógica separada)
    function setupModalOptionButtons(group, hiddenId, prenhaGroupId) {
        const btns = document.querySelectorAll(`[data-group="${group}"]`);
        const inp  = document.getElementById(hiddenId);
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (inp) inp.value = btn.dataset.value;

                if (prenhaGroupId && group === 'edit_sexo') {
                    const gp = document.getElementById(prenhaGroupId);
                    if (gp) {
                        const isFemea = btn.dataset.value === 'Fêmea';
                        gp.style.display = isFemea ? 'block' : 'none';
                        if (!isFemea) {
                            const cp = document.getElementById('edit_prenha');
                            const tg = document.getElementById('edit_gestacao');
                            if (cp) cp.checked = false;
                            if (tg) { tg.disabled = true; tg.value = ''; }
                        }
                    }
                }
            });
        });
    }

    setupModalOptionButtons('edit_especie', 'edit_inputEspecie', null);
    setupModalOptionButtons('edit_sexo',    'edit_inputSexo',    'edit_groupPrenha');

    const editPrenha  = document.getElementById('edit_prenha');
    const editGestacao = document.getElementById('edit_gestacao');
    if (editPrenha && editGestacao) {
        editPrenha.addEventListener('change', e => {
            editGestacao.disabled = !e.target.checked;
            if (!e.target.checked) editGestacao.value = '';
        });
    }

    // Expor função para o inline onclick do PHP
    window.abrirModalEdicao = abrirModalEdicao;

    // =============================================
    // MODAL DE EXCLUSÃO
    // =============================================
    const modalExclusao    = document.getElementById('modalExclusao');
    const fecharModalExc   = document.getElementById('fecharModalExclusao');
    const cancelarExclusao = document.getElementById('cancelarExclusao');

    function confirmarExclusao(id, nome) {
        document.getElementById('delete_animal_id').value = id;
        document.getElementById('deleteAnimalNome').textContent = nome;
        modalExclusao.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function fecharModalExclusao() {
        modalExclusao.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (fecharModalExc)   fecharModalExc.addEventListener('click',   fecharModalExclusao);
    if (cancelarExclusao) cancelarExclusao.addEventListener('click', fecharModalExclusao);
    modalExclusao?.addEventListener('click', e => { if (e.target === modalExclusao) fecharModalExclusao(); });

    // Expor para inline onclick
    window.confirmarExclusao = confirmarExclusao;

});