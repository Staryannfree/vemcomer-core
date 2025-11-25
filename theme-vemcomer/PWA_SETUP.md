# Configuração PWA - VemComer

## Arquivos Criados

### 1. `manifest.json` (Raiz do tema)
Arquivo de manifesto PWA com configurações do app.

### 2. `assets/js/sw.js` (Service Worker)
Service Worker com estratégia de cache híbrida.

### 3. `functions.php` (Modificações)
Integração WordPress para PWA:
- Meta tags no `wp_head`
- Registro do Service Worker
- Rewrite rule para `/sw.js`

## Ícones Necessários

Você precisa criar os seguintes ícones e salvá-los em `theme-vemcomer/assets/images/`:

1. **icon-pwa-192.png** (192x192 pixels)
   - Ícone para dispositivos Android e iOS
   - Formato: PNG com transparência
   - Cores: Use a paleta do tema (#ea1d2c)

2. **icon-pwa-512.png** (512x512 pixels)
   - Ícone de alta resolução para splash screens
   - Formato: PNG com transparência
   - Cores: Use a paleta do tema (#ea1d2c)

### Ferramentas Recomendadas para Criar Ícones

- **Online**: [PWA Asset Generator](https://www.pwabuilder.com/imageGenerator)
- **Figma**: Template PWA Icons
- **Photoshop/GIMP**: Exportar em múltiplos tamanhos

## Ativação

Após criar os ícones:

1. **Flush Rewrite Rules**:
   - Vá em WordPress Admin → Configurações → Links Permanentes
   - Clique em "Salvar alterações" (sem mudar nada)
   - Isso ativa a rota `/sw.js`

2. **Testar o Service Worker**:
   - Abra o DevTools (F12)
   - Vá em Application → Service Workers
   - Verifique se o SW está registrado

3. **Testar o Manifest**:
   - DevTools → Application → Manifest
   - Verifique se está carregando corretamente

4. **Testar Instalação PWA**:
   - No Chrome/Edge mobile, aparecerá um banner "Adicionar à tela inicial"
   - No desktop, aparecerá um ícone de instalação na barra de endereços

## Estratégia de Cache

### Network First (APIs REST)
- Tenta rede primeiro
- Se falhar, usa cache
- Se offline, retorna erro JSON

### Cache First (Assets)
- Retorna cache imediatamente
- Atualiza em background (stale-while-revalidate)

### Network First (Navegação HTML)
- Tenta rede primeiro
- Se offline, retorna App Shell (Home cacheada)

## Recursos Pré-Cacheados (App Shell)

- `/` (Home)
- `/inicio/` (Página inicial alternativa)
- `style.css` do tema
- `main.css` do tema
- `product-modal.css` do plugin

## Debug

### Console do Service Worker
Abra DevTools → Application → Service Workers → Console

### Cache Storage
DevTools → Application → Cache Storage → `vemcomer-pwa-v1`

### Network Tab
Verifique quais requisições estão sendo interceptadas pelo SW

## Notas Importantes

- O Service Worker **não intercepta** requisições para `/wp-admin/` ou `/wp-login.php`
- APIs REST usam Network First para garantir dados atualizados
- Imagens e assets usam Cache First para performance
- Navegação HTML sempre tenta rede primeiro para conteúdo fresco

