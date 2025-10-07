# 📁 Estrutura do Projeto - Cidade Aberta

## 🏗️ **Estrutura Organizada Atual**

```
Cidade Aberta/
├── 📄 index.html                    # Página principal do sistema
├── 📄 README.md                     # Documentação principal
│
├── 📁 docs/                         # 📚 Documentação completa
│   ├── README.md                    # Índice da documentação
│   ├── SETUP.md                     # Guia de instalação
│   ├── ESTRUTURA.md                 # Este arquivo
│   ├── COORDENADAS.md               # Coordenadas de Santarém
│   ├── MAPAS.md                     # Integração Leaflet
│   ├── DATABASE.md                  # Documentação do banco
│   ├── REORGANIZACAO.md             # Histórico de mudanças
│   ├── LOGS.md                      # Sistema de logs
│   └── API.md                       # Documentação das APIs
│
├── 📁 css/                          # 🎨 Estilos
│   └── main.css                     # Estilos principais
│
├── 📁 js/                           # ⚙️ JavaScript
│   └── app.js                       # JavaScript principal
│
├── 📁 api/                          # 🔌 APIs do Sistema
│   ├── stats.php                    # Estatísticas (NOVA - LIMPA)
│   ├── ocorrencias.php              # Ocorrências (original)
│   ├── ocorrencias_simple.php       # Ocorrências (simplificada)
│   ├── contato.php                  # Contato (original)
│   ├── contato_simple.php           # Contato (simplificada)
│   ├── login.php                    # Autenticação
│   └── rastreamento.php             # Rastreamento de ocorrências
│
├── 📁 assets/                       # 🖼️ Recursos
│   ├── logo-santarem.png            # Logo da prefeitura
│   └── logo-santarem-white.png      # Logo branca
│
├── 📁 classes/                      # 🏗️ Classes PHP
│   ├── BaseModel.php                # Modelo base
│   ├── OcorrenciaModel.php          # Modelo de ocorrências
│   └── GestorModel.php              # Modelo de gestores
│
├── 📁 config/                       # ⚙️ Configurações
│   └── database.php                 # Configuração do banco
│
├── 📁 database/                     # 🗄️ Conexão com Banco
│   └── Connection.php               # Classe de conexão PDO
│
├── 📁 sql/                          # 📊 Scripts SQL
│   └── setup_completo.sql           # Script único e completo
│
├── 📁 uploads/                      # 📎 Arquivos Enviados
│   └── (arquivos enviados pelos cidadãos)
│
└── 📁 logs/                         # 📝 Logs do Sistema
    └── (logs diários do sistema)
```

## 🔍 **Descrição dos Diretórios**

### 📚 **docs/** - Documentação
Toda a documentação técnica e guias do sistema organizados em arquivos MD específicos.

### 🎨 **css/** - Estilos
- `main.css` - Todos os estilos CSS organizados e otimizados

### ⚙️ **js/** - JavaScript
- `app.js` - Todo o JavaScript principal com Leaflet e funcionalidades

### 🔌 **api/** - APIs
- **APIs Simples** (`*_simple.php`) - Conexão direta ao banco, mais confiáveis
- **APIs Originais** (`*.php`) - Mantidas para compatibilidade
- **stats.php** - Nova API limpa para estatísticas

### 🖼️ **assets/** - Recursos
Logos, imagens e outros recursos estáticos do sistema.

### 🏗️ **classes/** - Classes PHP
Modelos PHP organizados seguindo padrões MVC.

### ⚙️ **config/** - Configurações
Arquivos de configuração do sistema.

### 🗄️ **database/** - Conexão
Nova classe de conexão PDO simplificada e otimizada.

### 📊 **sql/** - Scripts SQL
- `setup_completo.sql` - **ÚNICO ARQUIVO** necessário para o banco

### 📎 **uploads/** - Arquivos
Diretório para arquivos enviados pelos cidadãos.

### 📝 **logs/** - Logs
Sistema de logs para monitoramento e debug.

## ✅ **Arquivos Principais**

| Arquivo | Função | Status |
|---------|--------|--------|
| `index.html` | Página principal | ✅ Funcionando |
| `css/main.css` | Estilos principais | ✅ Organizado |
| `js/app.js` | JavaScript principal | ✅ Coordenadas corrigidas |
| `api/stats.php` | Estatísticas | ✅ Nova versão limpa |
| `sql/setup_completo.sql` | Banco completo | ✅ Único arquivo necessário |
| `database/Connection.php` | Conexão PDO | ✅ Simplificada |

## 🗑️ **Arquivos Removidos**

### ❌ Arquivos de Debug/Teste
- `forcar_correcao.php`
- `teste_direto.php` 
- `corrigir_agora.php`
- `teste_estatisticas.html`
- `teste_banco.php`
- `verificacao.html`

### ❌ Arquivos Antigos/Duplicados
- `script.js` → movido para `js/app.js`
- `styles.css` → movido para `css/main.css`
- `database_setup.sql` → substituído por `setup_completo.sql`

## 🎯 **Próximos Passos**

1. **Execute:** `sql/setup_completo.sql` no phpMyAdmin
2. **Teste:** Acesse `http://localhost/Cidade%20Aberta/`
3. **Verifique:** Coordenadas no mapa (devem estar nas ruas)
4. **Configure:** Ajustes específicos se necessário