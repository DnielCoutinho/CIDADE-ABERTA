# 🏛️ Cidade Aberta Santarém

Portal de transparência e gestão de ocorrências da Prefeitura de Santarém, Pará.

## � **Início Rápido**

1. **📚 Documentação Completa:** [`docs/`](docs/) - Toda documentação organizada
2. **⚙️ Instalação:** [`docs/SETUP.md`](docs/SETUP.md) - Guia completo de setup
3. **🗺️ Coordenadas:** [`docs/COORDENADAS.md`](docs/COORDENADAS.md) - Coordenadas corretas de Santarém
4. **🗄️ Banco de Dados:** Execute [`sql/setup_completo.sql`](sql/setup_completo.sql)

## 📁 **Estrutura Organizada**

```
Cidade Aberta/
├── 📄 index.html                    # Página principal
├── 📄 README.md                     # Este arquivo
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
- �️ **Mapa Interativo** - Leaflet com coordenadas corretas de Santarém
- 📊 **Estatísticas** - API nova e limpa (`api/stats.php`)
- 🗂️ **Ocorrências** - Sistema completo de gestão
- 📞 **Contato** - Formulário funcional
- 🔐 **Login** - Sistema de autenticação
- 📱 **Responsivo** - Interface adaptável

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

### 4. **Teste as Funcionalidades**
- ✅ Visualize ocorrências no mapa
- ✅ Clique no mapa para adicionar nova ocorrência
- ✅ Verifique estatísticas no header
- ✅ Teste formulário de contato

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

## 🗑️ **Arquivos Limpos**

Removidos arquivos desnecessários de debug e teste:
- ❌ `forcar_correcao.php`, `teste_direto.php`, `corrigir_agora.php`
- ❌ `teste_estatisticas.html`, `teste_banco.php`, `verificacao.html`
- ❌ Arquivos duplicados e versões antigas

## � **Suporte**

- **Documentação:** [`docs/`](docs/)
- **Problemas de setup:** [`docs/SETUP.md`](docs/SETUP.md)
- **Problemas de coordenadas:** [`docs/COORDENADAS.md`](docs/COORDENADAS.md)
- **Problemas de API:** [`docs/API.md`](docs/API.md)

---

**🏛️ Sistema Cidade Aberta - Prefeitura de Santarém, Pará**  
*Portal de Transparência e Gestão Municipal*

### 1. Execute o SQL de Correção:
```sql
-- Abra phpMyAdmin e execute sql/fix_status.sql
-- Isso vai corrigir os status das ocorrências no banco
```

### 2. Teste a Nova API:
```
http://localhost/Cidade Aberta/api/stats.php
```

### 3. Teste o Site:
```
http://localhost/Cidade Aberta/
```

## 🎯 Funcionalidades

- ✅ Mapa interativo com marcação de ocorrências
- ✅ Estatísticas em tempo real
- ✅ Sistema de rastreamento por código
- ✅ Formulário de contato
- ✅ Geocodificação reversa (clique no mapa)
- ✅ Design responsivo

## 🐛 Status dos Bugs

### Bug 1: Estatísticas zeradas ⚠️ EM CORREÇÃO
- **Problema:** Header mostrava 0 em todas as estatísticas
- **Causa:** Status vazios no banco de dados
- **Solução:** ✅ API limpa criada + script SQL de correção
- **Próximo passo:** Execute `sql/fix_status.sql` no phpMyAdmin

### Bug 2: Clique no mapa ✅ RESOLVIDO  
- **Problema:** "Digite o endereço ou clique no mapa" não funcionava
- **Causa:** Falta de geocodificação reversa
- **Solução:** ✅ Integração com Nominatim API implementada

## 📊 Tecnologias

- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Mapas:** Leaflet.js + OpenStreetMap
- **Backend:** PHP 7.4+, MySQL 5.7+
- **APIs:** Nominatim (geocoding)

## 🔄 Última Limpeza

**06/10/2025 21:30** - Projeto COMPLETAMENTE LIMPO E REORGANIZADO
- ✅ Arquivos inúteis removidos
- ✅ Estrutura de pastas organizada
- ✅ API de estatísticas reescrita
- ✅ Documentação atualizada