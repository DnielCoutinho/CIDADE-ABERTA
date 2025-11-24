# 🏛️ Cidade Aberta Santarém

Portal de transparência e gestão de ocorrências da Prefeitura de Santarém, Pará.

## 🚀 **Início Rápido**

1.  **📚 Documentação Completa:** [`docs/`](docs/) - Toda documentação organizada
2.  **⚙️ Instalação:** [`docs/SETUP.md`](docs/SETUP.md) - Guia completo de setup
3.  **🗺️ Coordenadas:** [`docs/COORDENADAS.md`](docs/COORDENADAS.md) - Coordenadas corretas de Santarém
4.  **🗄️ Banco de Dados:** Execute [`sql/setup_completo.sql`](sql/setup_completo.sql)

## 📁 **Estrutura Organizada**

```
Cidade Aberta/
├── 📄 index.html                    # Página principal
├── 📄 README.md                     # Este arquivo
│
├── 📁 admin/                        # Painel administrativo
│   ├── 📄 index.html                # Página principal do admin
│   ├── 📄 gerenciar-ocorrencias.html # Página de gerenciamento
│   └── 📄 admin-map.js              # Script do mapa do admin
│
├── 📁 docs/                         # 📚 Documentação completa
│   ├── README.md                    # Índice da documentação
│   ├── SETUP.md                     # Guia de instalação
│   ├── ESTRUTURA.md                 # Estrutura detalhada
│   ├── COORDENADAS.md               # Coordenadas de Santarém
│   ├── MAPAS.md                     # Integração Leaflet
│   ├── DATABASE.md                  # Documentação do banco
│   ├── API.md                       # Documentação das APIs
│   └── ...                          # Mais documentação
│
├── 📁 css/main.css                  # Estilos principais
├── 📁 js/app.js                     # JavaScript principal
├── 📁 api/                          # APIs do sistema
├── 📁 assets/                       # Logos e recursos
├── 📁 classes/                      # Classes PHP
├── 📁 config/                       # Configurações
├── 📁 database/                     # Conexão com banco
├── 📁 sql/setup_completo.sql        # Script único do banco
├── 📁 uploads/                      # Arquivos enviados
└── 📁 logs/                         # Logs do sistema
```

## ⚡ **Status Atual**

### ✅ **Funcionalidades Implementadas**
- 🗺️ **Mapa Interativo** - Leaflet com coordenadas corretas de Santarém
- 📍 **Mapa do Administrador** - Visualização de ocorrências no painel de admin
- 📊 **Estatísticas** - API nova e limpa (`api/stats.php`)
- 🗂️ **Ocorrências** - Sistema completo de gestão
- 📞 **Contato** - Formulário funcional
- 🔐 **Login** - Sistema de autenticação
- 📱 **Responsivo** - Interface adaptável
- ✅ **Geocodificação Reversa** - Clique no mapa para adicionar endereço

### 🗄️ **Banco de Dados**
- **Arquivo único:** [`sql/setup_completo.sql`](sql/setup_completo.sql)
- **Coordenadas corretas** de Santarém (nas ruas, não no rio)
- **Dados de exemplo** incluídos
- **Estrutura otimizada**

### 🔌 **APIs Disponíveis**
- [`api/stats.php`](api/stats.php) - Estatísticas (NOVA - RECOMENDADA)
- [`api/ocorrencias_simple.php`](api/ocorrencias_simple.php) - Ocorrências (SIMPLES)
- [`api/contato_simple.php`](api/contato_simple.php) - Contato (SIMPLES)
- [`api/login.php`](api/login.php) - Autenticação
- [`api/rastreamento.php`](api/rastreamento.php) - Rastreamento

> **📝 Nota:** Use as APIs "simple" para melhor performance e confiabilidade.

## 🎯 **Para Usar o Sistema**

### 1. **Configure o Banco**
```sql
-- Execute no phpMyAdmin:
sql/setup_completo.sql
```

### 2. **Acesse o Sistema**
```
http://localhost/Cidade%20Aberta/
```

### 3. **Login Administrativo**
- **Usuário:** `admin`
- **Senha:** `admin123`

## 📚 **Documentação Detalhada**

Para informações completas, consulte a pasta [`docs/`](docs/):

| Arquivo | Descrição |
|---------|-----------|
| [`docs/SETUP.md`](docs/SETUP.md) | Guia completo de instalação |
| [`docs/ESTRUTURA.md`](docs/ESTRUTURA.md) | Estrutura detalhada do projeto |
| [`docs/COORDENADAS.md`](docs/COORDENADAS.md) | Coordenadas corretas de Santarém |
| [`docs/MAPAS.md`](docs/MAPAS.md) | Integração com Leaflet |
| [`docs/DATABASE.md`](docs/DATABASE.md) | Documentação do banco de dados |
| [`docs/API.md`](docs/API.md) | Documentação completa das APIs |
| [`docs/REORGANIZACAO.md`](docs/REORGANIZACAO.md) | Histórico de mudanças |
| [`docs/LOGS.md`](docs/LOGS.md) | Sistema de logs |

## 🛠️ **Tecnologias Utilizadas**

- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Mapas:** Leaflet.js + OpenStreetMap (Gratuito)
- **Backend:** PHP 7.4+
- **Banco:** MySQL 5.7+
- **Conexão:** PDO (PHP Data Objects)

---

**🏛️ Sistema Cidade Aberta - Prefeitura de Santarém, Pará**  
*Portal de Transparência e Gestão Municipal*
