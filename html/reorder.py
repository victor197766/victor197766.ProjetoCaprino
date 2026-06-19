import re
import sys

filepath = r'c:\xampp\htdocs\caprinovinocultura\html\administracao.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Identify the start of each section using specific strings
idx_estatisticas = content.find('<section class="card mt-4">\n                <div class="card-header admin-tools">\n                    <div class="admin-tools-left">\n                        <h3 class="card-title" style="margin: 0;">Estatísticas Gerais</h3>')
if idx_estatisticas == -1:
    print("Could not find Estatísticas Gerais")
    sys.exit(1)

idx_avisos = content.find('<section class="card mt-4">\n                <div class="card-header admin-tools">\n                    <div class="admin-tools-left">\n                        <h3 class="card-title" style="margin: 0;">Avisos do Sistema</h3>')
if idx_avisos == -1:
    print("Could not find Avisos do Sistema")
    sys.exit(1)

idx_usuarios = content.find('<section class="card mt-4">\n                <div class="card-header admin-tools">\n                    <div class="admin-tools-left">\n                        <h3 class="card-title" style="margin: 0;">Usuários Cadastrados</h3>')
if idx_usuarios == -1:
    print("Could not find Usuários Cadastrados")
    sys.exit(1)

idx_end_usuarios = content.find('</section>', idx_usuarios) + len('</section>')

# Slice the sections
estatisticas_block = content[idx_estatisticas:idx_avisos]
avisos_block = content[idx_avisos:idx_usuarios]
usuarios_block = content[idx_usuarios:idx_end_usuarios]

# Reconstruct
new_content = content[:idx_estatisticas] + usuarios_block + '\n\n            ' + estatisticas_block + avisos_block + content[idx_end_usuarios:]

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Reordered successfully!")
