document.addEventListener('DOMContentLoaded', () => {
    
    // --- lógica do menu hambúrguer (gaveta mobile) ---
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

    // --- funcionalidade: tema claro/escuro ---
    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }

        localStorage.setItem('controlCabra-theme', tema);
    }

    const temaSalvo = localStorage.getItem('controlCabra-theme') || 'sistema';
    aplicarTema(temaSalvo);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('controlCabra-theme') === 'sistema') {
            aplicarTema('sistema');
        }
    });

    // --- agenda de vacinação ---
    const calendarTitle = document.getElementById('calendarTitle');
    const calendarDateButton = document.getElementById('calendarDateButton');
    const calendarDateInput = document.getElementById('calendarDateInput');
    const calendarDays = document.getElementById('calendarDays');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');

    const eventsTitle = document.getElementById('eventsTitle');
    const eventsList = document.getElementById('eventsList');
    const openEventForm = document.getElementById('openEventForm');
    const eventForm = document.getElementById('eventForm');
    const cancelEventForm = document.getElementById('cancelEventForm');
    const eventTime = document.getElementById('eventTime');
    const eventTitle = document.getElementById('eventTitle');
    const eventDescription = document.getElementById('eventDescription');

    if (
        calendarTitle &&
        calendarDateButton &&
        calendarDateInput &&
        calendarDays &&
        prevMonth &&
        nextMonth &&
        eventsTitle &&
        eventsList &&
        openEventForm &&
        eventForm &&
        cancelEventForm &&
        eventTime &&
        eventTitle &&
        eventDescription
    ) {
        const meses = [
            'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
            'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
        ];

        const storageKey = 'controlCabra-agenda-vacinacao';

        let selectedDate = new Date();
        let currentMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        let eventos = carregarEventos();

        function carregarEventos() {
            try {
                return JSON.parse(localStorage.getItem(storageKey)) || {};
            } catch (error) {
                return {};
            }
        }

        function salvarEventos() {
            localStorage.setItem(storageKey, JSON.stringify(eventos));
        }

        function formatarChaveData(data) {
            const ano = data.getFullYear();
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const dia = String(data.getDate()).padStart(2, '0');

            return `${ano}-${mes}-${dia}`;
        }

        function criarDataLocal(valor) {
            const partes = valor.split('-').map(Number);
            return new Date(partes[0], partes[1] - 1, partes[2]);
        }

        function atualizarInputData() {
            calendarDateInput.value = formatarChaveData(selectedDate);
        }

        function renderizarCalendario() {
            const ano = currentMonth.getFullYear();
            const mes = currentMonth.getMonth();

            calendarTitle.textContent = `${meses[mes]} ${ano}`;
            atualizarInputData();

            calendarDays.innerHTML = '';

            const primeiroDiaMes = new Date(ano, mes, 1);
            const ultimoDiaMes = new Date(ano, mes + 1, 0).getDate();

            const espacosAntes = (primeiroDiaMes.getDay() + 6) % 7;

            for (let i = 0; i < espacosAntes; i++) {
                const empty = document.createElement('div');
                empty.className = 'calendar-empty';
                calendarDays.appendChild(empty);
            }

            for (let dia = 1; dia <= ultimoDiaMes; dia++) {
                const dataAtual = new Date(ano, mes, dia);
                const chaveData = formatarChaveData(dataAtual);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'calendar-day';
                button.textContent = dia;

                if (chaveData === formatarChaveData(selectedDate)) {
                    button.classList.add('selected');
                }

                if (eventos[chaveData] && eventos[chaveData].length > 0) {
                    button.classList.add('has-event');
                }

                button.addEventListener('click', () => {
                    selectedDate = dataAtual;
                    currentMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                    eventForm.classList.add('hidden');
                    renderizarCalendario();
                    renderizarEventos();
                });

                calendarDays.appendChild(button);
            }
        }

        function renderizarEventos() {
            const chaveData = formatarChaveData(selectedDate);
            const listaEventos = eventos[chaveData] || [];

            eventsTitle.textContent = `eventos de ${selectedDate.getDate()} de ${meses[selectedDate.getMonth()]} de ${selectedDate.getFullYear()}`;
            eventsList.innerHTML = '';

            if (listaEventos.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'empty-events';
                empty.textContent = 'nenhum evento cadastrado para esta data.';
                eventsList.appendChild(empty);
                return;
            }

            listaEventos
                .sort((a, b) => a.time.localeCompare(b.time))
                .forEach((evento) => {
                    const card = document.createElement('div');
                    card.className = 'event-card';

                    const content = document.createElement('div');

                    const time = document.createElement('span');
                    time.className = 'event-time';
                    time.textContent = evento.time;

                    const title = document.createElement('h4');
                    title.textContent = evento.title;

                    const description = document.createElement('p');
                    description.textContent = evento.description || 'sem descrição cadastrada.';

                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'delete-event-btn';
                    deleteButton.innerHTML = '<i class="ph ph-trash"></i>';

                    deleteButton.addEventListener('click', () => {
                        eventos[chaveData] = eventos[chaveData].filter(item => item.id !== evento.id);

                        if (eventos[chaveData].length === 0) {
                            delete eventos[chaveData];
                        }

                        salvarEventos();
                        renderizarCalendario();
                        renderizarEventos();
                    });

                    content.appendChild(time);
                    content.appendChild(title);
                    content.appendChild(description);

                    card.appendChild(content);
                    card.appendChild(deleteButton);

                    eventsList.appendChild(card);
                });
        }

        calendarDateButton.addEventListener('click', () => {
            if (calendarDateInput.showPicker) {
                calendarDateInput.showPicker();
            } else {
                calendarDateInput.click();
                calendarDateInput.focus();
            }
        });

        calendarDateInput.addEventListener('change', () => {
            if (!calendarDateInput.value) return;

            selectedDate = criarDataLocal(calendarDateInput.value);
            currentMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);

            eventForm.classList.add('hidden');
            renderizarCalendario();
            renderizarEventos();
        });

        prevMonth.addEventListener('click', () => {
            const diaAtual = selectedDate.getDate();

            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);

            const ultimoDiaNovoMes = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).getDate();
            selectedDate = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), Math.min(diaAtual, ultimoDiaNovoMes));

            eventForm.classList.add('hidden');
            renderizarCalendario();
            renderizarEventos();
        });

        nextMonth.addEventListener('click', () => {
            const diaAtual = selectedDate.getDate();

            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);

            const ultimoDiaNovoMes = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).getDate();
            selectedDate = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), Math.min(diaAtual, ultimoDiaNovoMes));

            eventForm.classList.add('hidden');
            renderizarCalendario();
            renderizarEventos();
        });

        openEventForm.addEventListener('click', () => {
            eventForm.classList.remove('hidden');
            eventTime.focus();
        });

        cancelEventForm.addEventListener('click', () => {
            eventForm.reset();
            eventForm.classList.add('hidden');
        });

        eventForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const chaveData = formatarChaveData(selectedDate);

            if (!eventos[chaveData]) {
                eventos[chaveData] = [];
            }

            eventos[chaveData].push({
                id: Date.now(),
                time: eventTime.value,
                title: eventTitle.value.trim(),
                description: eventDescription.value.trim()
            });

            salvarEventos();

            eventForm.reset();
            eventForm.classList.add('hidden');

            renderizarCalendario();
            renderizarEventos();
        });

        renderizarCalendario();
        renderizarEventos();
    }
});