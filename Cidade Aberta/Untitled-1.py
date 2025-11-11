import time

class RouterBGP:
    def __init__(self, name, as_number):
        self.name = name
        self.as_number = as_number
        # O 'RIB' (Routing Information Base)
        # Armazena a *melhor* rota para cada destino
        # Formato: { 'destino': {'path': [...], 'local_pref': 100} }
        self.routes = {}

    # Compara a rota 'new' com a 'current' e retorna True se 'new' for melhor
    def _is_better_path(self, current, new):
        """Implementa o algoritmo de best-path selection (simplificado)."""
        
        # 1. Decisão de política: Maior LOCAL_PREFERENCE ganha
        # Este é o atributo de política mais importante.
        if new['local_pref'] > current['local_pref']:
            return True
        if new['local_pref'] < current['local_pref']:
            return False

        # 2. Se Local Pref for igual, menor AS_PATH ganha (seu código original)
        if len(new['path']) < len(current['path']):
            return True

        # (Outros critérios como ORIGIN, MED, eBGP/iBGP viriam aqui)
        
        return False # Mantém a rota atual

    # Simula o recebimento de uma rota de um vizinho
    def receive_route(self, destination, received_path, received_local_pref=100):
        # Prepara os atributos da rota *recebida* antes de adicionar nosso AS
        new_attributes = {
            'path': [self.as_number] + received_path,
            'local_pref': received_local_pref # O LP é configurado por quem recebe
        }

        # Verifica se já temos uma rota para comparar
        if destination not in self.routes:
            self.routes[destination] = new_attributes
            print(f"[{self.name}] Instalou nova rota para {destination}: {new_attributes['path']} (LP: {new_attributes['local_pref']})")
            return

        current_best = self.routes[destination]

        # Executa o algoritmo de "melhor caminho"
        if self._is_better_path(current_best, new_attributes):
            print(f"[{self.name}] Rota ATUALIZADA para {destination}: {new_attributes['path']} (LP: {new_attributes['local_pref']}) (venceu {current_best['path']})")
            self.routes[destination] = new_attributes
        else:
            print(f"[{self.name}] Rota ignorada para {destination}: {new_attributes['path']} (LP: {new_attributes['local_pref']}) (manteve {current_best['path']})")


    def show_routes(self):
        print(f"\n--- Tabela de Rotas: {self.name} (AS{self.as_number}) ---")
        if not self.routes:
            print("  (Tabela de rotas vazia)")
            return
            
        for dest, attrs in self.routes.items():
            print(f"  Destino {dest} -> Caminho: {attrs['path']} (LocalPref: {attrs['local_pref']})")

# --- Configuração ---
# A rede 10.1.0.0/16 está em R1 (AS100)
# R3 (AS300) aprenderá sobre ela por dois caminhos:
# 1. De R2 (AS200), que aprendeu de R1. Caminho recebido: [200, 100]
# 2. De R4 (AS400), que aprendeu de R5 (AS500), que aprendeu de R1. Caminho recebido: [400, 500, 100]

r3 = RouterBGP("Roteador3-Empresa", 300)
rede_destino = "10.1.0.0/16"

# --- Simulação ---

print("### Simulação de Roteamento BGP com Política ###")
r3.show_routes() # Mostra tabela vazia

time.sleep(1) # Pausa para facilitar a leitura

# 1. R3 recebe a rota do Caminho 1 (via R2, link de satélite)
# O administrador não mexe na política, então o Local Pref é 100 (padrão).
print("\n[Evento] Recebendo rota do vizinho R2 (AS200)...")
path_via_r2 = [200, 100]
r3.receive_route(rede_destino, path_via_r2, received_local_pref=100)
r3.show_routes()

time.sleep(1) # Pausa para facilitar a leitura

# 2. R3 recebe a rota do Caminho 2 (via R4, link de fibra)
# O caminho é mais longo, mas o administrador do AS300 cria uma política:
# "Qualquer rota vinda do AS400 (R4) deve ter Local Preference 200".
print("\n[Evento] Recebendo rota do vizinho R4 (AS400)...")
path_via_r4 = [400, 500, 100]
r3.receive_route(rede_destino, path_via_r4, received_local_pref=200)
r3.show_routes()

# --- Resultado ---
print("\n### Decisão Final do R3 ###")
print("O R3 escolheu o caminho via AS400, mesmo sendo mais longo,")
print("porque o Local Preference (200) era maior que o do caminho via AS200 (100).")

import networkx as nx
import matplotlib.pyplot as plt

# 1. Criar o grafo
G = nx.DiGraph() # Grafo Direcionado

# Adicionar todos os ASes como nós
G.add_nodes_from([
    ("AS100", {"label": "AS100\n(Rede: 10.1.0.0/16)"}),
    ("AS200", {"label": "AS200"}),
    ("AS300", {"label": "AS300\n(Sua Empresa)"}),
    ("AS400", {"label": "AS400"}),
    ("AS500", {"label": "AS500"}),
])

# Adicionar as conexões (links)
G.add_edges_from([
    ("AS200", "AS100"),
    ("AS500", "AS100"),
    ("AS400", "AS500"),
    ("AS300", "AS200"),
    ("AS300", "AS400"),
])

# 2. Definir os caminhos para colorir
path_rejected = [("AS300", "AS200"), ("AS200", "AS100")]
path_chosen   = [("AS300", "AS400"), ("AS400", "AS500"), ("AS500", "AS100")]

# 3. Preparar cores para o desenho
edge_colors = []
edge_widths = []

for u, v in G.edges():
    if (u, v) in path_chosen:
        edge_colors.append('green')
        edge_widths.append(3.0)
    elif (u, v) in path_rejected:
        edge_colors.append('red')
        edge_widths.append(1.5)
    else:
        edge_colors.append('gray')
        edge_widths.append(1.0)

# ===================================================================
# <<< MUDANÇA PRINCIPAL AQUI >>>
# Definir um layout fixo (manual) para garantir a estrutura
# (x, y)
pos = {
    "AS100": (0.5, 3), # Topo
    "AS200": (0, 2),   # Meio-Esquerda
    "AS500": (1, 2),   # Meio-Direita
    "AS400": (1, 1),   # Baixo-Direita
    "AS300": (0.5, 0)  # Base
}
# ===================================================================


# Pegar os labels dos nós
labels = nx.get_node_attributes(G, 'label')

# 4. Desenhar o gráfico
plt.figure(figsize=(10, 8)) # Ajustei o tamanho
plt.title("Simulação BGP: Local Preference (LP) vs. AS-PATH", size=16)

# Desenha Nós
nx.draw_networkx_nodes(G, pos, node_size=4000, node_color='lightblue', alpha=0.9, 
                       edgecolors='black', linewidths=1.0) # Adicionei bordas

# Desenha Labels
nx.draw_networkx_labels(G, pos, labels=labels, font_size=10, font_weight='bold')

# Desenha Edges (Links)
# Usando 'connectionstyle' para curvar as linhas
nx.draw_networkx_edges(G, pos, 
                       edge_color=edge_colors, 
                       width=edge_widths,
                       arrows=True, arrowstyle='->', arrowsize=20,
                       connectionstyle='arc3,rad=0.1') # Curvatura leve

# Legenda (Melhor posicionada)
plt.text(0.0, -0.1, 
         "Caminho ESCOLHIDO (Verde): LP 200, Path: [300, 400, 500, 100]", 
         bbox=dict(facecolor='green', alpha=0.2), transform=plt.gca().transAxes,
         fontsize=12, verticalalignment='top')
plt.text(0.0, -0.2, 
         "Caminho REJEITADO (Vermelho): LP 100, Path: [300, 200, 100]", 
         bbox=dict(facecolor='red', alpha=0.2), transform=plt.gca().transAxes,
         fontsize=12, verticalalignment='top')


plt.axis('off') # Remove os eixos x/y
plt.tight_layout()
plt.show()