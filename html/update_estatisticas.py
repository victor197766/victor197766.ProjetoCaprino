import sys

filepath = r'c:\xampp\htdocs\caprinovinocultura\html\estatisticas.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Add query for avisos
php_avisos = """// ==========================================
// CONSULTA PARA AVISOS DO SISTEMA
// ==========================================
$query_avisos = "SELECT id, mensagem, data_criacao FROM avisos WHERE destinatario_id IS NULL OR destinatario_id = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $usuario_id);
mysqli_stmt_execute($stmt_avisos);
$resultado_avisos = mysqli_stmt_get_result($stmt_avisos);
"""

if "CONSULTA PARA AVISOS DO SISTEMA" not in content:
    content = content.replace("// ==========================================\n// CONSULTAS PARA OS CARDS DE RESUMO", php_avisos + "\n// ==========================================\n// CONSULTAS PARA OS CARDS DE RESUMO")

html_avisos = """
            <?php if (mysqli_num_rows($resultado_avisos) > 0): ?>
            <section class="card mt-4" style="background-color: #fff9e6; border-left: 5px solid #ffc107;">
                <div class="card-header" style="padding: 15px; border-bottom: 1px solid #ffe082;">
                    <h3 class="card-title" style="margin: 0; color: #b08d00;">🔔 Notificações e Avisos</h3>
                </div>
                <div style="padding: 15px;">
                    <ul style="list-style-type: none; padding: 0; margin: 0;">
                        <?php while ($aviso = mysqli_fetch_assoc($resultado_avisos)): ?>
                            <li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ffe082;">
                                <strong><?= date('d/m/Y H:i', strtotime($aviso['data_criacao'])) ?>:</strong> <?= htmlspecialchars($aviso['mensagem']) ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </section>
            <?php endif; ?>
"""

if "🔔 Notificações e Avisos" not in content:
    content = content.replace('<section class="summary-grid mt-4">', html_avisos + '\n            <section class="summary-grid mt-4">')

# Modify access logic: if Empregado Rural, maybe they don't see settings?
# Actually the user only asked for "Fazer o sistema de notificações totalmente funcional"
# Also need to add link to "Propriedades" in sidebar if user is not Empregado Rural, 
# or just hide Propriedades link if it's Empregado.
# Wait, Propriedades is only for Producer. In sidebar of estatisticas:
sidebar_prop = """
                <?php if (!isset($_SESSION['usuario_tipo']) || strtolower($_SESSION['usuario_tipo']) !== 'visitante'): ?>
                    <a href="propriedades.php" class="nav-item">Propriedades</a>
                <?php endif; ?>
"""
if "propriedades.php" not in content:
    content = content.replace('<a href="saude.php" class="nav-item">Saúde</a> <a href="cuidados.php" class="nav-item">Cuidados</a>',
                              '<a href="saude.php" class="nav-item">Saúde</a>\n                <a href="cuidados.php" class="nav-item">Cuidados</a>\n' + sidebar_prop)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Estatisticas updated.")
