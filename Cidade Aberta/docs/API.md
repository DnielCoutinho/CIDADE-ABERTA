# 🔌 APIs do Sistema - Cidade Aberta

## 📋 **Visão Geral das APIs**

O sistema possui duas versões de APIs:
- **APIs Simples** (`*_simple.php`) - Recomendadas, conexão direta
- **APIs Originais** (`*.php`) - Mantidas para compatibilidade

## 🚀 **APIs Principais**

### 1. 📊 **Estatísticas** - `api/stats.php`

**Endpoint:** `GET /api/stats.php`

**Resposta:**
```json
{
  "total_ocorrencias": 5,
  "pendentes": 2,
  "em_andamento": 2,
  "concluidas": 1,
  "total_contatos": 0
}
```

**Características:**
- ✅ Conexão direta ao banco
- ✅ Consultas SQL otimizadas
- ✅ Sem dependências de classes
- ✅ Log de debug ativado

---

### 2. 🗂️ **Ocorrências** 

#### **API Simples** - `api/ocorrencias_simple.php` ⭐ **(RECOMENDADA)**

**GET - Listar Ocorrências:**
```
GET /api/ocorrencias_simple.php
```

**Resposta:**
```json
[
  {
    "id": 1,
    "codigo": "STM001",
    "tipo": "Buraco na via",
    "descricao": "Buraco grande na Av. Tapajós",
    "latitude": -2.419444,
    "longitude": -54.708333,
    "status": "pendente",
    "prioridade": "alta",
    "nome_cidadao": "João Silva",
    "email_cidadao": "joao@email.com",
    "telefone_cidadao": "(93) 99999-9999",
    "data_criacao": "2025-10-06 10:30:00"
  }
]
```

**POST - Criar Ocorrência:**
```
POST /api/ocorrencias_simple.php
Content-Type: application/json

{
  "tipo": "Buraco na via",
  "descricao": "Descrição da ocorrência",
  "latitude": -2.419444,
  "longitude": -54.708333,
  "prioridade": "media",
  "nome_cidadao": "Nome do Cidadão",
  "email_cidadao": "email@exemplo.com",
  "telefone_cidadao": "(93) 99999-9999"
}
```

#### **API Original** - `api/ocorrencias.php`

Mesma funcionalidade, mas com dependências de classes.

---

### 3. 📞 **Contato**

#### **API Simples** - `api/contato_simple.php` ⭐ **(RECOMENDADA)**

**POST - Enviar Mensagem:**
```
POST /api/contato_simple.php
Content-Type: application/json

{
  "nome": "Nome do Cidadão",
  "email": "email@exemplo.com",
  "telefone": "(93) 99999-9999",
  "assunto": "Assunto da mensagem",
  "mensagem": "Conteúdo da mensagem"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Mensagem enviada com sucesso",
  "id": 123
}
```

**Características:**
- ✅ Aceita JSON e POST form
- ✅ Cria tabela automaticamente se não existir
- ✅ Validação de email
- ✅ Log de debug detalhado

#### **API Original** - `api/contato.php`

Mesma funcionalidade, mas com dependências de classes.

---

### 4. 🔐 **Autenticação** - `api/login.php`

**POST - Login:**
```
POST /api/login.php
Content-Type: application/json

{
  "usuario": "admin",
  "senha": "admin123"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "user_id": 1,
  "nome": "Administrador",
  "departamento": "TI"
}
```

---

### 5. 🔍 **Rastreamento** - `api/rastreamento.php`

**GET - Rastrear Ocorrência:**
```
GET /api/rastreamento.php?codigo=STM001
```

**Resposta:**
```json
{
  "id": 1,
  "codigo": "STM001",
  "tipo": "Buraco na via",
  "status": "em_andamento",
  "data_criacao": "2025-10-06 10:30:00",
  "ultima_atualizacao": "2025-10-06 14:20:00"
}
```

## 🔧 **Configuração das APIs**

### **Conexão com Banco:**
Todas as APIs simples usam a classe `database/Connection.php`:

```php
<?php
require_once '../database/Connection.php';

try {
    $pdo = Connection::getInstance()->getPdo();
    // Usar $pdo para consultas
} catch (Exception $e) {
    error_log("Erro na API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
```

### **Headers CORS:**
Todas as APIs incluem headers CORS para desenvolvimento:

```php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

## 🧪 **Testando as APIs**

### **Com cURL:**

```bash
# Estatísticas
curl http://localhost/Cidade%20Aberta/api/stats.php

# Listar ocorrências
curl http://localhost/Cidade%20Aberta/api/ocorrencias_simple.php

# Criar ocorrência
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"tipo":"Teste","descricao":"Teste","latitude":-2.415,"longitude":-54.705,"prioridade":"media","nome_cidadao":"Teste","email_cidadao":"teste@email.com","telefone_cidadao":"93999999999"}' \
  http://localhost/Cidade%20Aberta/api/ocorrencias_simple.php
```

### **Com JavaScript:**

```javascript
// Buscar estatísticas
fetch('/api/stats.php')
  .then(response => response.json())
  .then(data => console.log(data));

// Buscar ocorrências
fetch('/api/ocorrencias_simple.php')
  .then(response => response.json())
  .then(data => console.log(data));
```

## 🐛 **Debug e Logs**

### **Logs de Debug:**
As APIs simples incluem logs detalhados em `logs/`:

```php
error_log("API Stats - Consulta executada: " . json_encode($stats));
```

### **Verificação de Conexão:**
Teste a conexão com o banco:

```php
// Em qualquer API
$connection = Connection::getInstance();
if ($connection->testConnection()) {
    echo "✅ Conexão OK";
} else {
    echo "❌ Erro na conexão";
}
```

## ⚡ **Performance**

### **APIs Simples vs Originais:**

| Característica | APIs Simples | APIs Originais |
|----------------|---------------|----------------|
| **Velocidade** | ⚡ Mais rápidas | 🐌 Mais lentas |
| **Dependências** | ✅ Mínimas | ❌ Muitas classes |
| **Debug** | ✅ Logs detalhados | ❌ Debug limitado |
| **Manutenção** | ✅ Fácil | ❌ Complexa |
| **Confiabilidade** | ✅ Alta | ❌ Média |

## 🔄 **Migração**

Para migrar para as APIs simples:

1. **Substitua as chamadas:**
   - `ocorrencias.php` → `ocorrencias_simple.php`
   - `contato.php` → `contato_simple.php`

2. **Teste as funcionalidades**

3. **Remova as APIs antigas** (opcional)

## 📞 **Suporte**

Para problemas com as APIs:
1. Verifique os logs em `logs/`
2. Teste a conexão com o banco
3. Verifique se `sql/setup_completo.sql` foi executado
4. Consulte `docs/DATABASE.md` para problemas de banco