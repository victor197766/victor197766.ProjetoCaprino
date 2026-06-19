<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Novo Usuário</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-hover: #1b5e20;
            --danger: #d32f2f;
            --bg: #f5f5f5;
            --card-bg: #ffffff;
            --text: #333333;
            --border: #e0e0e0;
            --muted: #888888;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 520px;
            margin: 40px auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        h2 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
        }

        /* Seletor de tipo */
        .type-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .type-option {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            user-select: none;
        }

        .type-option input[type="radio"] {
            display: none;
        }

        .type-option .type-icon {
            font-size: 28px;
            display: block;
            margin-bottom: 6px;
        }

        .type-option .type-label {
            font-weight: 600;
            font-size: 14px;
        }

        .type-option .type-desc {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        .type-option.selected {
            border-color: var(--primary);
            background-color: #f1f8f1;
        }

        /* Campos */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text);
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Campos exclusivos do produtor */
        #campos-produtor {
            display: none;
            border-top: 1px solid var(--border);
            padding-top: 18px;
            margin-top: 4px;
        }

        #campos-produtor.visivel {
            display: block;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 14px;
        }

        /* Alerta de erro */
        .alert-error {
            padding: 12px;
            background-color: #fdecea;
            color: #c62828;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert-error.visivel {
            display: block;
        }

        /* Botões */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-save {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-save:hover {
            background-color: var(--primary-hover);
        }

        .btn-cancel {
            background-color: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .btn-cancel:hover {
            background-color: var(--danger);
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Novo Usuário</h2>

    <div id="alerta-erro" class="alert-error"></div>

    <form action="createUserService.php" method="POST" id="form-criar">

        <!-- Seletor de tipo -->
        <div class="type-selector">
            <label class="type-option selected" id="opcao-produtor">
                <input type="radio" name="tipo" value="produtor" checked>
                <span class="type-icon">🌾</span>
                <span class="type-label">Produtor</span>
                <span class="type-desc">Acesso completo à fazenda</span>
            </label>
            <label class="type-option" id="opcao-visitante">
                <input type="radio" name="tipo" value="visitante">
                <span class="type-icon">👤</span>
                <span class="type-label">Empregado Rural</span>
                <span class="type-desc">Acesso restrito à propriedade</span>
            </label>
        </div>

        <!-- Campos comuns -->
        <div class="form-group">
            <label for="username">Nome de Usuário</label>
            <input type="text" id="username" name="username" required placeholder="ex: joao_silva">
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required placeholder="ex: joao@email.com">
        </div>

        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
        </div>

        <div class="form-group">
            <label for="num_telefone">Telefone <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
            <input type="text" id="num_telefone" name="num_telefone" placeholder="ex: (87) 99999-9999" maxlength="15">
        </div>

        <!-- Campos exclusivos do produtor -->
        <div id="campos-produtor" class="visivel">
            <p class="section-label">Informações da Propriedade</p>

            <div class="form-group">
                <label for="nome_propriedade">Nome da Propriedade</label>
                <input type="text" id="nome_propriedade" name="nome_propriedade" placeholder="ex: Fazenda São João">
            </div>

            <div class="form-group">
                <label for="cpf">CPF <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
                <input type="text" id="cpf" name="cpf" placeholder="ex: 12345678901" maxlength="11">
                <p class="hint">Somente números, sem pontos ou traços.</p>
            </div>

            <div class="form-group">
                <label for="cnpj">CNPJ <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
                <input type="text" id="cnpj" name="cnpj" placeholder="ex: 12345678000199" maxlength="14">
                <p class="hint">Somente números, sem pontos ou traços.</p>
            </div>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-save">Criar Usuário</button>
            <a href="listUser.php" class="btn-cancel">Cancelar</a>
        </div>

    </form>
</div>

<script>
    const opcaoProdutor  = document.getElementById('opcao-produtor');
    const opcaoVisitante = document.getElementById('opcao-visitante');
    const camposProdutor = document.getElementById('campos-produtor');
    const radioProdutor  = opcaoProdutor.querySelector('input');
    const radioVisitante = opcaoVisitante.querySelector('input');

    function selecionarTipo(tipo) {
        if (tipo === 'produtor') {
            opcaoProdutor.classList.add('selected');
            opcaoVisitante.classList.remove('selected');
            camposProdutor.classList.add('visivel');
        } else {
            opcaoVisitante.classList.add('selected');
            opcaoProdutor.classList.remove('selected');
            camposProdutor.classList.remove('visivel');
        }
    }

    opcaoProdutor.addEventListener('click', () => selecionarTipo('produtor'));
    opcaoVisitante.addEventListener('click', () => selecionarTipo('visitante'));

    // Validação básica antes de enviar
    document.getElementById('form-criar').addEventListener('submit', function(e) {
        const alerta = document.getElementById('alerta-erro');
        const senha = document.getElementById('password').value;

        if (senha.length < 6) {
            e.preventDefault();
            alerta.textContent = 'A senha deve ter pelo menos 6 caracteres.';
            alerta.classList.add('visivel');
            return;
        }

        alerta.classList.remove('visivel');
    });
</script>

</body>
</html>
