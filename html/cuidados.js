document.addEventListener('DOMContentLoaded', () => {

    // =============================================
    // TEMA
    // =============================================
    (function () {
        const t = localStorage.getItem('controlCabra-theme') || 'sistema';
        document.documentElement.setAttribute('data-theme',
            t === 'sistema'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro')
                : t
        );
    })();

    // =============================================
    // MENU HAMBÚRGUER
    // =============================================
    const menuToggle     = document.getElementById('menuToggle');
    const sidebar        = document.getElementById('sidebar');
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
    // TROCA DE ABAS
    // =============================================
    const tabBtns   = document.querySelectorAll('.ctab-btn');
    const tabPanels = document.querySelectorAll('.ctab-panel');

    function switchTab(tabName, updateURL = true) {
        tabBtns.forEach(btn   => btn.classList.toggle('active',   btn.dataset.tab === tabName));
        tabPanels.forEach(pnl => pnl.classList.toggle('active',   pnl.id === 'ctab-' + tabName));
        if (updateURL) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            history.replaceState(null, '', url.toString());
        }
    }

    tabBtns.forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));

    // =============================================
    // FEEDBACK (ler params da URL)
    // =============================================
    const params      = new URLSearchParams(window.location.search);
    const feedbackBar = document.getElementById('feedback-bar');

    function showFeedback(el, msg, type) {
        if (!el) return;
        el.textContent = msg;
        el.className   = 'feedback-bar ' + (type === 'success' ? 'is-success' : 'is-error');
        el.style.display = 'block';
        setTimeout(() => {
            el.style.transition = 'opacity 0.6s';
            el.style.opacity    = '0';
            setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; }, 600);
        }, 4500);
    }

    const sucesso = params.get('sucesso');
    const erro    = params.get('erro');
    const tab     = params.get('tab');

    if (sucesso === '1')       showFeedback(feedbackBar, '✓ Animal registrado com sucesso!', 'success');
    else if (sucesso === 'editado')   showFeedback(feedbackBar, '✓ Animal atualizado com sucesso!', 'success');
    else if (sucesso === 'deletado')  showFeedback(feedbackBar, '✓ Animal excluído.', 'success');
    else if (erro)             showFeedback(feedbackBar, '✗ Erro: ' + decodeURIComponent(erro), 'error');

    // Limpar params da URL
    if (sucesso || erro) {
        const clean = new URL(window.location.href);
        clean.searchParams.delete('sucesso');
        clean.searchParams.delete('erro');
        history.replaceState(null, '', clean.toString());
    }

    // Garantir aba correta após redirect
    if (tab) switchTab(tab, false);

    // =============================================
    // BOTÕES ESPÉCIE/SEXO — FORMULÁRIO DE CADASTRO
    // =============================================
    function setupOptionBtns(group, hiddenId, prenhaGroupId) {
        const btns = document.querySelectorAll(`[data-group="${group}"]`);
        const inp  = document.getElementById(hiddenId);
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (inp) inp.value = btn.dataset.value;

                if (prenhaGroupId && group === 'sexo') {
                    const gp = document.getElementById(prenhaGroupId);
                    if (gp) {
                        const isFemea = btn.dataset.value === 'Fêmea';
                        gp.style.display = isFemea ? 'block' : 'none';
                        if (!isFemea) {
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

    setupOptionBtns('especie', 'inputEspecie', null);
    setupOptionBtns('sexo',    'inputSexo',    'groupPrenha');

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
    const errCadastro  = document.getElementById('form-error-reg');

    if (formCadastro) {
        formCadastro.addEventListener('submit', e => {
            e.preventDefault();
            if (errCadastro) errCadastro.style.display = 'none';

            const peso          = document.getElementById('reg_peso')?.value.trim();
            const idade         = document.getElementById('reg_idade')?.value.trim();
            const identificador = document.getElementById('reg_identificador')?.value.trim();
            const raca          = document.getElementById('reg_raca')?.value.trim();

            if (!peso || parseFloat(peso) <= 0) {
                showFeedback(errCadastro, 'Informe um peso válido (maior que 0 Kg).', 'error');
                return;
            }
            if (!idade) {
                showFeedback(errCadastro, 'Informe a idade do animal.', 'error');
                return;
            }
            if (!identificador) {
                showFeedback(errCadastro, 'O identificador (brinco/tatuagem/microchip) é obrigatório.', 'error');
                return;
            }
            if (!raca) {
                showFeedback(errCadastro, 'Informe a raça do animal.', 'error');
                return;
            }
            formCadastro.submit();
        });
    }

    // =============================================
    // BUSCA AJAX (aba Pesquisar)
    // =============================================
    const searchQuery   = document.getElementById('searchQuery');
    const btnSearch     = document.getElementById('btnSearch');
    const searchResults = document.getElementById('searchResults');

    let searchDebounce = null;

    function renderResultados(animais) {
        if (!searchResults) return;

        if (animais.length === 0) {
            searchResults.innerHTML = `
                <div class="search-placeholder">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <p>Nenhum animal encontrado para "<strong>${escHtml(searchQuery?.value || '')}</strong>"</p>
                </div>`;
            return;
        }

        const html = animais.map(a => {
            const nome  = a.nome || 'Animal #' + a.id;
            const esp   = (a.especie || '').toLowerCase();
            const cls   = esp === 'caprino' ? 'badge-caprino' : (esp === 'ovino' ? 'badge-ovino' : 'badge-outro');
            const lote  = a.lote_nome || 'Sem lote';
            return `
            <div class="search-result-item">
                <div class="search-result-avatar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><circle cx="9" cy="12" r="1.5"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 17H20a2 2 0 0 1 0 4H6.5"/><path d="M9 12H4a2 2 0 0 0-1.5 3.3L4 19.5"/><path d="M20 17V9a2 2 0 0 0-2-2h-3.5"/><path d="M14.5 7C14.5 5 16 3 18 3s3.5 2 3.5 4-1 4-3 4-4.5-1-4.5-1"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="search-result-title">
                        ${escHtml(nome)}
                        <span class="especie-badge ${cls}">${escHtml(cap(a.especie || '—'))}</span>
                        ${a.sexo ? `<span class="especie-badge badge-sexo">${escHtml(cap(a.sexo))}</span>` : ''}
                    </div>
                    <div class="search-result-meta">
                        ID: <strong>${escHtml(a.identificador || '—')}</strong>
                        · Raça: ${escHtml(a.raca || '—')}
                        ${a.peso_kg ? ' · ' + parseFloat(a.peso_kg).toFixed(1).replace('.', ',') + ' Kg' : ''}
                        · ${escHtml(lote)}
                    </div>
                </div>
            </div>`;
        }).join('');

        searchResults.innerHTML = `<div style="padding-top:8px;">${html}</div>`;
    }

    function executarBusca() {
        const q = searchQuery?.value.trim() || '';
        if (q.length === 0) {
            if (searchResults) searchResults.innerHTML = `
                <div class="search-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <p>Digite para buscar animais do seu rebanho</p>
                </div>`;
            return;
        }
        if (searchResults) searchResults.innerHTML = '<div class="search-placeholder"><p style="color:var(--text-muted);">Buscando...</p></div>';

        fetch(`animalSearchService.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) renderResultados(data.animais);
                else if (searchResults) searchResults.innerHTML = `<div class="search-placeholder"><p>Erro na busca.</p></div>`;
            })
            .catch(() => {
                if (searchResults) searchResults.innerHTML = `<div class="search-placeholder"><p>Erro de conexão.</p></div>`;
            });
    }

    if (searchQuery) {
        searchQuery.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(executarBusca, 350);
        });
        searchQuery.addEventListener('keydown', e => { if (e.key === 'Enter') { clearTimeout(searchDebounce); executarBusca(); } });
    }
    if (btnSearch) btnSearch.addEventListener('click', () => { clearTimeout(searchDebounce); executarBusca(); });

    // =============================================
    // FILTRO/BUSCA DA LISTA
    // =============================================
    const listSearch    = document.getElementById('listSearch');
    const listFilter    = document.getElementById('listFilter');
    const listNoResults = document.getElementById('listNoResults');

    function filtrarLista() {
        const q = (listSearch?.value || '').toLowerCase();
        const f = listFilter?.value || 'todos';
        let visible = 0;

        document.querySelectorAll('.animal-item').forEach(item => {
            const textMatch = (item.dataset.search || '').includes(q);
            let fm = true;
            if (f === 'caprino'  && item.dataset.especie !== 'caprino')  fm = false;
            if (f === 'ovino'    && item.dataset.especie !== 'ovino')    fm = false;
            if (f === 'macho'    && item.dataset.sexo    !== 'macho')    fm = false;
            if (f === 'femea'    && !['fêmea','femea'].includes(item.dataset.sexo)) fm = false;
            if (f === 'lote'     && item.dataset.lote    !== 'sim')      fm = false;
            if (f === 'sem_lote' && item.dataset.lote    !== 'nao')      fm = false;

            const show = textMatch && fm;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (listNoResults) listNoResults.style.display = visible === 0 ? 'flex' : 'none';
    }

    if (listSearch)  listSearch.addEventListener('input',  filtrarLista);
    if (listFilter)  listFilter.addEventListener('change', filtrarLista);

    // =============================================
    // MODAL DE EDIÇÃO
    // =============================================
    const modalEdicao    = document.getElementById('modalEdicao');
    const fecharEdicao   = document.getElementById('fecharModalEdicao');
    const cancelarEdicao = document.getElementById('cancelarEdicao');

    function abrirModalEdicao(animal) {
        setValue('edit_animal_id',    animal.id         ?? '');
        setValue('edit_nome',         animal.nome        ?? '');
        setValue('edit_raca',         animal.raca        ?? '');
        setValue('edit_peso',         animal.peso_kg     ?? '');
        setValue('edit_idade',        animal.idade       ?? '');
        setValue('edit_tratamento',   animal.estado_atual ?? '');
        setValue('edit_identificador',animal.identificador ?? '');
        setTextArea('edit_info',      animal.info_extras  ?? '');

        setCheck('edit_nasceu',   animal.nascimento_fazenda == 1);
        setCheck('edit_vacinado', animal.vacinado_prev == 1);

        // Espécie e sexo
        setEditOption('edit_especie', 'edit_inputEspecie', animal.especie ?? 'Caprino');
        setEditOption('edit_sexo',    'edit_inputSexo',    animal.sexo    ?? 'Macho');

        // Prenha
        const isFemea = ['fêmea','femea'].includes((animal.sexo ?? '').toLowerCase());
        const gp      = document.getElementById('edit_groupPrenha');
        const cp      = document.getElementById('edit_prenha');
        const tg      = document.getElementById('edit_gestacao');
        if (gp) gp.style.display = isFemea ? 'block' : 'none';
        if (cp) cp.checked = animal.esta_prenha == 1;
        if (tg) { tg.disabled = !(animal.esta_prenha == 1); tg.value = animal.tempo_gestacao ?? ''; }

        // Selects
        setSelectVal('edit_lote_id',   animal.lote_id       ?? '');
        setSelectVal('edit_reprodutor', animal.reprodutor_id ?? '');
        setSelectVal('edit_matriz',     animal.matriz_id     ?? '');

        modalEdicao.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function fecharModalEdicaoFn() {
        modalEdicao.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (fecharEdicao)   fecharEdicao.addEventListener('click',   fecharModalEdicaoFn);
    if (cancelarEdicao) cancelarEdicao.addEventListener('click',  fecharModalEdicaoFn);
    modalEdicao?.addEventListener('click', e => { if (e.target === modalEdicao) fecharModalEdicaoFn(); });

    // Botões espécie/sexo do modal de edição
    setupOptionBtns('edit_especie', 'edit_inputEspecie', null);
    setupOptionBtns('edit_sexo',    'edit_inputSexo',    'edit_groupPrenha');

    const editPrenha  = document.getElementById('edit_prenha');
    const editGestacao = document.getElementById('edit_gestacao');
    if (editPrenha && editGestacao) {
        editPrenha.addEventListener('change', e => {
            editGestacao.disabled = !e.target.checked;
            if (!e.target.checked) editGestacao.value = '';
        });
    }

    window.abrirModalEdicao = abrirModalEdicao;

    // =============================================
    // MODAL DE EXCLUSÃO
    // =============================================
    const modalExclusao     = document.getElementById('modalExclusao');
    const fecharExclusao    = document.getElementById('fecharModalExclusao');
    const cancelarExclusao  = document.getElementById('cancelarExclusao');

    function confirmarExclusao(id, nome) {
        setValue('delete_animal_id', id);
        const el = document.getElementById('deleteAnimalNome');
        if (el) el.textContent = nome;
        modalExclusao.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function fecharModalExclusaoFn() {
        modalExclusao.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (fecharExclusao)   fecharExclusao.addEventListener('click',   fecharModalExclusaoFn);
    if (cancelarExclusao) cancelarExclusao.addEventListener('click',  fecharModalExclusaoFn);
    modalExclusao?.addEventListener('click', e => { if (e.target === modalExclusao) fecharModalExclusaoFn(); });

    window.confirmarExclusao = confirmarExclusao;

    // =============================================
    // HELPERS
    // =============================================
    function setValue(id, val)      { const el = document.getElementById(id); if (el) el.value = val; }
    function setTextArea(id, val)   { const el = document.getElementById(id); if (el) el.value = val; }
    function setCheck(id, checked)  { const el = document.getElementById(id); if (el) el.checked = checked; }
    function escHtml(str)           { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function cap(str)               { return str.charAt(0).toUpperCase() + str.slice(1); }

    function setEditOption(group, hiddenId, value) {
        const btns = document.querySelectorAll(`[data-group="${group}"]`);
        const inp  = document.getElementById(hiddenId);
        btns.forEach(b => b.classList.toggle('active', b.dataset.value === value));
        if (inp) inp.value = value;
    }

    function setSelectVal(id, value) {
        const sel = document.getElementById(id);
        if (!sel) return;
        const sv = String(value ?? '');
        for (const opt of sel.options) {
            if (String(opt.value) === sv) { opt.selected = true; return; }
        }
        sel.selectedIndex = 0;
    }

});