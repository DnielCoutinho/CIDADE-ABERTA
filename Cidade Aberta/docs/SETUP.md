# Guia de Execução - Sistema Cidade Aberta

## 🚀 Setup Completo do Sistema

### 1. Configuração do Banco de Dados

**Passo 1: Acessar phpMyAdmin**
- Abra: http://localhost/phpmyadmin
- Clique na aba "SQL"

**Passo 2: Executar Script**
- Copie todo o conteúdo do arquivo `database_setup.sql`
- Cole na área de texto do phpMyAdmin
- Clique em "Executar"

**Resultado esperado:**
```
✅ Banco 'cidade_aberta' criado
✅ 4 tabelas criadas (ocorrencias, gestores, contatos, logs)
✅ 5 ocorrências de exemplo inseridas
✅ Usuário admin criado
```

### 2. Credenciais de Acesso

**Login Administrativo:**
- Usuário: `admin`
- Senha: `admin123`

### 3. URLs para Teste

**Frontend Principal:**
```
http://localhost/Cidade%20Aberta/
```

**APIs Disponíveis:**
```
GET  http://localhost/Cidade%20Aberta/api/ocorrencias.php
POST http://localhost/Cidade%20Aberta/api/ocorrencias.php
GET  http://localhost/Cidade%20Aberta/api/rastreamento.php?codigo=STM001
POST http://localhost/Cidade%20Aberta/api/contato.php
POST http://localhost/Cidade%20Aberta/api/login.php
```

### 4. Teste Completo das Funcionalidades

**✅ Mapa Interativo:**
- Visualizar marcadores de ocorrências existentes
- Clicar no mapa para selecionar localização
- Usar botão de geolocalização (canto superior direito)
- Buscar ocorrências na barra de pesquisa

**✅ Registro de Ocorrência:**
1. Selecione o tipo de ocorrência
2. Descreva o problema
3. Informe o endereço
4. Clique no mapa para marcar localização
5. Preencha dados pessoais
6. Clique "Registrar Ocorrência"

**✅ Rastreamento:**
- Use códigos de teste: STM001, STM002, STM003, STM004, STM005
- Digite o código na seção "Rastrear Ocorrência"
- Visualize timeline de status

**✅ Contato:**
- Preencha formulário de contato
- Mensagem será salva no banco

**✅ Login Gestor:**
- Clique no botão "Login" no header
- Use admin/admin123
- Redirecionará para dashboard (se existir)

### 5. Códigos de Ocorrência para Teste

```
STM001 - Buraco grande na via principal (Pendente)
STM002 - Acúmulo de lixo na esquina (Em Andamento)  
STM003 - Poste de luz queimado (Concluída)
STM004 - Vazamento de água na calçada (Pendente)
STM005 - Cratera na pista de rolamento (Em Andamento)
```

### 6. Verificações de Funcionamento

**Frontend:**
- [ ] Mapa carrega corretamente
- [ ] Marcadores aparecem no mapa
- [ ] Formulários funcionam
- [ ] Notificações aparecem
- [ ] Interface responsiva

**Backend:**
- [ ] APIs respondem JSON válido
- [ ] Dados são salvos no banco
- [ ] Validações funcionam
- [ ] Logs são gerados

**Banco de Dados:**
- [ ] Tabelas criadas
- [ ] Dados de exemplo inseridos
- [ ] Triggers funcionando
- [ ] Views disponíveis

### 7. Estrutura Final do Projeto

```
📁 Cidade Aberta/
├── 🌐 index.html              # Interface principal
├── 🎨 styles.css              # Design responsivo
├── ⚡ script.js               # Lógica frontend + API
├── 📁 config/
│   └── 🔧 database.php        # Conexão DB
├── 📁 classes/
│   ├── 🏗️ BaseModel.php       # Classe base
│   ├── 📋 OcorrenciaModel.php # CRUD ocorrências
│   └── 👤 GestorModel.php     # Gestão usuários
├── 📁 api/
│   ├── 📡 ocorrencias.php     # REST API principal
│   ├── 🔍 rastreamento.php    # Busca por código
│   ├── 📧 contato.php         # Mensagens
│   └── 🔐 login.php           # Autenticação
├── 📁 logs/                   # Sistema de logs
├── 📁 uploads/                # Upload arquivos
├── 🗄️ database_setup.sql     # Schema completo
└── 📖 SETUP.md               # Este guia
```

### 8. Próximos Desenvolvimentos

**Administrativo:**
- [ ] Dashboard completo para gestores
- [ ] Relatórios e estatísticas
- [ ] Gestão de usuários
- [ ] Configurações do sistema

**Melhorias:**
- [ ] Sistema de notificações
- [ ] Upload de fotos funcionando
- [ ] API mobile
- [ ] PWA (Progressive Web App)

### 9. Tecnologias Utilizadas

**Frontend:**
- HTML5 Semântico
- CSS3 com Grid/Flexbox
- JavaScript ES6+
- Leaflet.js (Mapas)
- Font Awesome (Ícones)

**Backend:**
- PHP 7.4+ com POO
- MySQL 8.0
- PDO para segurança
- Arquitetura MVC

**Segurança:**
- Validação de dados
- Prevenção CSRF
- SQL Injection protection
- XSS prevention

---

## 🎯 Sistema Pronto para Uso!

O portal "Cidade Aberta" está totalmente funcional e pronto para receber as demandas dos cidadãos de Santarém. 

**Para executar:** Certifique-se que o XAMPP está rodando e acesse a URL principal.