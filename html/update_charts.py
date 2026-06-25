import re

filepath = r'c:\xampp\htdocs\caprinovinocultura\html\administracao.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace <canvas id="lotsChart"></canvas> with PHP check
lots_canvas = '<canvas id="lotsChart"></canvas>'
lots_php = '''<?php if (count($lots_types) > 0): ?>
                            <canvas id="lotsChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum dado disponível ainda.</p>
                        <?php endif; ?>'''
content = content.replace(lots_canvas, lots_php)

# Replace <canvas id="animalsChart"></canvas> with PHP check
animals_canvas = '<canvas id="animalsChart"></canvas>'
animals_php = '''<?php if (count($animals_types) > 0): ?>
                            <canvas id="animalsChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum dado disponível ainda.</p>
                        <?php endif; ?>'''
content = content.replace(animals_canvas, animals_php)

users_canvas = '<canvas id="usersChart"></canvas>'
users_php = '''<?php if (array_sum($users_types) > 0): ?>
                            <canvas id="usersChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum dado disponível ainda.</p>
                        <?php endif; ?>'''
content = content.replace(users_canvas, users_php)

# Fix title case in pie chart
content = content.replace("labels: usersLabels.map(l => String(l).charAt(0).toUpperCase() + String(l).slice(1)),",
"""labels: usersLabels.map(l => {
                        let words = String(l).split(' ');
                        return words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                    }),""")


with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated charts in administracao.php")
