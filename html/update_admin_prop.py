import sys

filepath = r'c:\xampp\htdocs\caprinovinocultura\html\administracao.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add fetching of propriedades in the PHP section
php_inject = """// Fetch propriedades
$sqlPropriedades = "SELECT p.id, p.nome, u.username as produtor_nome FROM propriedades p JOIN usuario u ON p.produtor_id = u.user_id ORDER BY p.nome ASC";
$resultadoPropriedades = mysqli_query($conexao, $sqlPropriedades);
$propriedades_options = [];
if ($resultadoPropriedades) {
    while($p = mysqli_fetch_assoc($resultadoPropriedades)) {
        $propriedades_options[] = $p;
    }
}
"""

if "$propriedades_options = [];" not in content:
    # Insert right before // Fetch stats for charts
    content = content.replace("// Fetch stats for charts", php_inject + "\n// Fetch stats for charts")

# 2. Update POST actions to handle propriedade_id instead of nome_propriedade
content = content.replace("$nome_propriedade = trim($_POST['nome_propriedade'] ?? '');", "$propriedade_id = !empty($_POST['propriedade_id']) ? intval($_POST['propriedade_id']) : null;")
content = content.replace("$nome_propriedade, $num_telefone", "$propriedade_id, $num_telefone")
content = content.replace("nome_propriedade, num_telefone", "propriedade_id, num_telefone")
content = content.replace("nome_propriedade = ?, num_telefone", "propriedade_id = ?, num_telefone")

# Update binds: ssssssssi -> ssissssi or similar? Wait!
# Bind for insert:
# original: "ssssssss", $username, $email, $senhaHash, $tipo, $nome_propriedade, $num_telefone, $CPF, $CNPJ
# new: "ssssisss", $username, $email, $senhaHash, $tipo, $propriedade_id, $num_telefone, $CPF, $CNPJ
content = content.replace('mysqli_stmt_bind_param($stmtInsert, "ssssssss", $username, $email, $senhaHash, $tipo, $nome_propriedade, $num_telefone, $CPF, $CNPJ);', 
                          'mysqli_stmt_bind_param($stmtInsert, "ssssisss", $username, $email, $senhaHash, $tipo, $propriedade_id, $num_telefone, $CPF, $CNPJ);')

content = content.replace('mysqli_stmt_bind_param($stmtInsert, "sssssss", $username, $email, $tipo, $nome_propriedade, $num_telefone, $CPF, $CNPJ);',
                          'mysqli_stmt_bind_param($stmtInsert, "sssisss", $username, $email, $tipo, $propriedade_id, $num_telefone, $CPF, $CNPJ);')

content = content.replace('mysqli_stmt_bind_param($stmtUpdate, "sssssssi", $username, $email, $tipo, $nome_propriedade, $num_telefone, $CPF, $CNPJ, $user_id);',
                          'mysqli_stmt_bind_param($stmtUpdate, "sssisssi", $username, $email, $tipo, $propriedade_id, $num_telefone, $CPF, $CNPJ, $user_id);')

# 3. Modify table fetching to get propriedade_id and name
content = content.replace(
    "SELECT user_id, username, email, tipo, nome_propriedade, num_telefone, CPF, CNPJ, create_time, suspenso FROM usuario",
    "SELECT u.user_id, u.username, u.email, u.tipo, u.propriedade_id, p.nome as nome_propriedade, u.num_telefone, u.CPF, u.CNPJ, u.create_time, u.suspenso FROM usuario u LEFT JOIN propriedades p ON u.propriedade_id = p.id"
)

# 4. Modify HTML forms to use select
select_html = """<div class="form-group">
                    <label>Vincular Propriedade</label>
                    <select name="propriedade_id" id="modal_propriedade">
                        <option value="">Nenhuma / Criação Própria</option>
                        <?php foreach($propriedades_options as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= valorSeguro($p['nome']) ?> (Produtor: <?= valorSeguro($p['produtor_nome']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>"""

# There are two forms. One in addModal, one in editModal.
# addModal
content = content.replace("""<div class="form-group">
                    <label>Nome da Propriedade</label>
                    <input type="text" name="nome_propriedade">
                </div>""", select_html.replace('id="modal_propriedade"', 'id="add_propriedade"'))

# editModal
content = content.replace("""<div class="form-group">
                    <label>Nome da Propriedade</label>
                    <input type="text" id="modal_propriedade" name="nome_propriedade">
                </div>""", select_html)


# 5. Fix Javascript for editing
content = content.replace("document.getElementById('modal_propriedade').value = btn.getAttribute('data-propriedade') || '';",
                          "document.getElementById('modal_propriedade').value = btn.getAttribute('data-propriedade-id') || '';")
content = content.replace("data-propriedade='{$propriedadeAttr}'",
                          "data-propriedade-id='{$usuario['propriedade_id']}' data-propriedade='{$propriedadeAttr}'")

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to administracao.php!")
