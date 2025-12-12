# Sistema de Captura Automática do Navegador

Este sistema captura **AUTOMATICAMENTE** tudo que acontece no navegador enquanto você testa, sem precisar abrir o DevTools manualmente.

## 🎯 O que é capturado automaticamente:

- ✅ **Todos os console.log/error/warn** - Cada mensagem do console
- ✅ **Todas as requisições fetch/XHR** - Todas as chamadas de API
- ✅ **Erros JavaScript não tratados** - Erros que quebram o código
- ✅ **Promessas rejeitadas** - Erros em async/await
- ✅ **Métricas de performance** - Tempo de carregamento, DNS, etc.
- ✅ **URL e viewport** - Onde e como você está testando

## 🚀 Como funciona:

1. **O script é carregado automaticamente** quando `VC_DEBUG` está ativo
2. **Captura tudo em tempo real** enquanto você navega
3. **Salva no servidor** via REST API
4. **Você pode ler depois** com o script PowerShell

## 📋 Como usar:

### 1. Ativar o sistema:

O sistema já está ativo se `VC_DEBUG` estiver definido no `wp-config.php` (já está configurado).

### 2. Testar normalmente:

Apenas use o site normalmente no navegador. Tudo é capturado automaticamente:
- Abra o wizard de onboarding
- Clique nos botões
- Navegue pelas páginas
- Tudo é capturado!

### 3. Ler os logs:

```powershell
# Ler logs mais recentes
.\scripts\read-browser-logs.ps1 -Latest

# Ler logs de uma data específica
.\scripts\read-browser-logs.ps1 -Date "2025-12-03"

# Ler apenas erros
.\scripts\read-browser-logs.ps1 -Latest -ErrorsOnly

# Ler apenas requisições de rede
.\scripts\read-browser-logs.ps1 -Latest -NetworkOnly
```

### 4. Exportar logs manualmente:

No console do navegador (F12), execute:

```javascript
// Exportar todos os logs
window.vcBrowserDebug.exportLogs()

// Ver logs no console
console.log(window.vcBrowserDebug.getLogs())

// Ver requisições de rede
console.log(window.vcBrowserDebug.getNetworkRequests())

// Enviar logs para servidor agora
window.vcBrowserDebug.flushLogs()
```

## 📁 Onde os logs são salvos:

- **No servidor:** `wp-content/uploads/vemcomer-browser-debug/`
  - `browser-logs-YYYY-MM-DD.json` - Logs do console
  - `network-requests-YYYY-MM-DD.json` - Requisições de rede
  - `performance-YYYY-MM-DD.json` - Métricas de performance

- **No navegador:** `localStorage` (chave: `vc_browser_debug_logs`)

## 🔍 Exemplo de uso:

1. **Inicie o monitor em tempo real** (opcional):
   ```powershell
   .\scripts\monitor-logs-realtime.ps1
   ```

2. **Teste o wizard de onboarding** no navegador:
   - Passo 1: Selecione categorias
   - Passo 4: Veja se categorias aparecem
   - Passo 5: Tente adicionar produto

3. **Leia os logs capturados**:
   ```powershell
   .\scripts\read-browser-logs.ps1 -Latest -ErrorsOnly
   ```

4. **Compartilhe comigo**:
   - "Lê os logs do navegador mais recentes"
   - Ou copie o conteúdo do arquivo gerado

## 💡 Vantagens:

- ✅ **Não precisa abrir DevTools** - Tudo é capturado automaticamente
- ✅ **Não precisa copiar manualmente** - Scripts fazem tudo
- ✅ **Captura tudo** - Nada escapa
- ✅ **Funciona em tempo real** - Logs são enviados automaticamente
- ✅ **Organizado por data** - Fácil de encontrar logs específicos

## 🎛️ Configuração:

O script pode ser configurado editando `assets/js/browser-debug-capture.js`:

```javascript
const CONFIG = {
    enabled: true, // Ativar/desativar
    sendToServer: true, // Enviar para servidor
    saveToLocalStorage: true, // Salvar no navegador
    maxLogs: 1000, // Máximo de logs
    autoFlush: true, // Enviar automaticamente
    flushInterval: 5000, // A cada 5 segundos
    captureNetwork: true, // Capturar rede
    captureConsole: true, // Capturar console
    captureErrors: true, // Capturar erros
    capturePerformance: true, // Capturar performance
};
```

## 🔧 Troubleshooting:

### Logs não aparecem:

1. Verifique se `VC_DEBUG` está ativo no `wp-config.php`
2. Verifique se o script está sendo carregado (F12 → Network → procure `browser-debug-capture.js`)
3. Verifique o console do navegador por erros

### Requisições não são capturadas:

- Algumas requisições podem ser feitas antes do script carregar
- Recarregue a página após o script carregar

### Logs muito grandes:

- O sistema limita automaticamente a 1000 logs
- Arquivos são organizados por data
- Delete arquivos antigos manualmente se necessário

---

**Agora você não precisa mais abrir o DevTools manualmente! Tudo é capturado automaticamente! 🎉**

