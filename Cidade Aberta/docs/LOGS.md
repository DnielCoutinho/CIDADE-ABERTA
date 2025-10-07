# Diretório de Logs - Cidade Aberta

Este diretório armazena logs de atividades do sistema.

## Estrutura dos Logs

- `YYYY-MM-DD.log` - Logs diários das atividades
- `error.log` - Logs de erros específicos
- `security.log` - Logs de segurança (tentativas de login, etc.)

## Formato dos Logs

```
YYYY-MM-DD HH:MM:SS - {"timestamp":"...","action":"...","user_ip":"...","details":{...}}
```

## Retenção

Logs são mantidos por 90 dias automaticamente.

## Segurança

Este diretório deve ter permissões apropriadas:
- Leitura/escrita apenas para o servidor web
- Não acessível via HTTP direto