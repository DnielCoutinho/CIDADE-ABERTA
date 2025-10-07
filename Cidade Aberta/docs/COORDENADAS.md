# 🗺️ COORDENADAS CORRETAS DE SANTARÉM

## 📍 **Problema Anterior:**
- Coordenadas estavam apontando para o meio do Rio Tapajós
- Marcadores apareciam na água em vez das ruas

## ✅ **Coordenadas Corretas:**

### 🏛️ **Centro de Santarém:**
- **Praça Tiradentes:** -2.415000, -54.705556 (CENTRO DE REFERÊNCIA)
- **Av. Tapajós:** -2.408333, -54.708889
- **Rua Floriano Peixoto:** -2.412500, -54.706944
- **Rua Barão do Rio Branco:** -2.413889, -54.707222

### 🏘️ **Bairros:**
- **Aparecida (Av. Cuiabá):** -2.420000, -54.703333
- **Aparecida (Rua 15 de Novembro):** -2.425000, -54.700000
- **Fatima (Av. Mendonça Furtado):** -2.435000, -54.695000
- **Terminal Rodoviário:** -2.430000, -54.698889

## 🔧 **Arquivos Corrigidos:**

1. **sql/setup_completo.sql** - Coordenadas dos dados de exemplo
2. **sql/corrigir_coordenadas.sql** - Script rápido para atualizar BD
3. **js/app.js** - Centro do mapa e coordenadas de exemplo

## 📋 **Para Aplicar a Correção:**

### Opção 1: Executar script rápido
```sql
-- Execute no phpMyAdmin:
sql/corrigir_coordenadas.sql
```

### Opção 2: Recrear banco completo
```sql  
-- Execute no phpMyAdmin:
sql/setup_completo.sql
```

## 🎯 **Resultado:**
Agora as ocorrências aparecerão nas ruas corretas de Santarém em vez do meio do rio!

**Coordenada de Centro Atualizada:**
- Antes: -2.4194, -54.7083 (no rio)
- Depois: -2.415000, -54.705556 (Praça Tiradentes)