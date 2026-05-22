document.addEventListener('DOMContentLoaded', () => {
    
    // --- MENU HAMBÚRGUER E TEMA ---
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

    function aplicarTema(tema) {
        if (tema === 'sistema') {
            const modoEscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', modoEscuroSistema ? 'escuro' : 'claro');
        } else {
            document.documentElement.setAttribute('data-theme', tema);
        }
    }
    aplicarTema(localStorage.getItem('controlCabra-theme') || 'sistema');

    // --- LÓGICA DO CALENDÁRIO ---
    const monthYearDisplay = document.getElementById('monthYearDisplay');
    const calendarDays = document.getElementById('calendarDays');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    let dataAtual = new Date(); // Inicia com a data de hoje

    const meses = [
        "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", 
        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
    ];

    function renderizarCalendario() {
        calendarDays.innerHTML = "";
        
        const mes = dataAtual.getMonth();
        const ano = dataAtual.getFullYear();
        
        // Atualiza o título (Ex: Outubro 2026)
        monthYearDisplay.textContent = `${meses[mes]} ${ano}`;

        // Dia da semana do primeiro dia do mês (0 = Domingo, 1 = Segunda...)
        // Ajustado para Segunda-feira ser o início da grade
        let primeiroDiaMes = new Date(ano, mes, 1).getDay();
        primeiroDiaMes = primeiroDiaMes === 0 ? 6 : primeiroDiaMes - 1; 

        // Quantidade de dias no mês
        const diasNoMes = new Date(ano, mes + 1, 0).getDate();

        // Dia de hoje real (para destaque inicial se for o mês atual)
        const hojeReal = new Date();
        const ehMesAtual = hojeReal.getMonth() === mes && hojeReal.getFullYear() === ano;
        const diaSelecionado = ehMesAtual ? hojeReal.getDate() : 1; // Seleciona hoje, ou dia 1

        // Preenche espaços vazios antes do dia 1
        for (let i = 0; i < primeiroDiaMes; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.classList.add('calendar-day', 'empty');
            calendarDays.appendChild(emptyDiv);
        }

        // Preenche os dias do mês
        for (let i = 1; i <= diasNoMes; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('calendar-day');
            dayDiv.textContent = i;

            if (i === diaSelecionado) {
                dayDiv.classList.add('active');
            }

            // Evento de clique para selecionar o dia
            dayDiv.addEventListener('click', () => {
                document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('active'));
                dayDiv.classList.add('active');
                atualizarAgenda(i, meses[mes], ano);
            });

            calendarDays.appendChild(dayDiv);
        }
    }

    // Navegação dos meses
    prevMonthBtn.addEventListener('click', () => {
        dataAtual.setMonth(dataAtual.getMonth() - 1);
        renderizarCalendario();
    });

    nextMonthBtn.addEventListener('click', () => {
        dataAtual.setMonth(dataAtual.getMonth() + 1);
        renderizarCalendario();
    });

    // Simula a atualização da lista de eventos ao clicar no dia
    function atualizarAgenda(dia, mesNome, ano) {
        const headerAgenda = document.querySelector('.agenda-header h3');
        headerAgenda.textContent = `Eventos de ${dia} de ${mesNome}`;
        // Aqui você faria o fetch/busca no banco de dados para os eventos reais da data selecionada
    }

    // Inicializa o calendário na tela
    renderizarCalendario();
});