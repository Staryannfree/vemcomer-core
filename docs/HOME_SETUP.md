# Como Criar a Página Home do VemComer

Este guia mostra como criar a página inicial completa do marketplace usando os shortcodes do plugin.

## Opção 1: Usando Template PHP (Recomendado)

### Passo 1: Criar a Página

1. Vá em **Páginas ▸ Adicionar Nova**
2. Título: **Início**
3. No editor, selecione **Atributos da Página** (painel direito)
4. Em **Template**, selecione **Home VemComer**
5. **Não precisa adicionar conteúdo** - o template já tem tudo!

### Passo 2: Definir como Página Inicial

1. Vá em **Configurações ▸ Leitura**
2. Em **Sua página inicial exibe**, selecione **Uma página estática**
3. Em **Página inicial**, selecione **Início**
4. Clique em **Salvar alterações**

**Pronto!** A Home já está funcionando com todas as seções.

---

## Opção 2: Usando Gutenberg (Sem Template)

Se preferir montar manualmente no editor de blocos:

### Passo 1: Criar a Página

1. Vá em **Páginas ▸ Adicionar Nova**
2. Título: **Início**

### Passo 2: Adicionar Seções (na ordem)

#### 1. Hero
- Adicione um bloco **Cobertura (Cover)** ou **Grupo**
- Adicione:
  - **Título**: "Peça dos melhores restaurantes da sua cidade"
  - **Parágrafo**: "Entrega, retirada e cardápios atualizados em tempo real"
  - **Botão** com link `#vc-restaurants-list` e texto "Explorar restaurantes"

#### 2. Banners
- Adicione um bloco **Título** (H2): "Promoções e destaques"
- Adicione um bloco **Shortcode** com: `[vc_banners]`

#### 3. Restaurantes
- Adicione um bloco **Grupo** e defina o ID como `vc-restaurants-list`
- Dentro do grupo:
  - **Título** (H2): "Restaurantes"
  - **Shortcode**: `[vemcomer_restaurants]`

#### 4. Mapa
- Adicione um bloco **Título** (H2): "Veja restaurantes no mapa"
- Adicione um bloco **Parágrafo**: "Encontre restaurantes próximos a você usando o mapa interativo."
- Adicione um bloco **Shortcode** com: `[vc_restaurants_map]`

#### 5. Para Você (Opcional - só aparece para logados)
- Adicione um bloco **Título** (H2): "Para você"
- Adicione um bloco **Shortcode** com: `[vc_favorites]`
- Adicione um bloco **Shortcode** com: `[vc_orders_history per_page="5"]`

#### 6. CTA para Donos
- Adicione um bloco **Grupo** com fundo cinza
- Dentro:
  - **Título** (H2): "Tem um restaurante? Venda pelo VemComer"
  - **Parágrafo**: "Cadastre seu restaurante e comece a receber pedidos hoje mesmo."
  - **Botão** apontando para a página de cadastro

#### 7. Rodapé (Opcional)
- Adicione links para: Como funciona, Cadastre seu restaurante, Política de Privacidade, Termos de Uso

### Passo 3: Definir como Página Inicial

1. Vá em **Configurações ▸ Leitura**
2. Em **Sua página inicial exibe**, selecione **Uma página estática**
3. Em **Página inicial**, selecione **Início**
4. Clique em **Salvar alterações**

---

## Opção 3: Usar o Instalador (Mais Rápido)

O instalador pode criar automaticamente uma página básica:

1. Vá em **VemComer ▸ Instalador**
2. Procure por **"Lista de Restaurantes (VemComer)"**
3. Clique em **Criar**
4. Depois, edite a página e adicione as outras seções manualmente

---

## Estrutura da Home (Template PHP)

O template `templates/page-home.php` já inclui todas as seções:

1. **Hero** - Título, subtítulo, busca e botão CTA
2. **Banners** - `[vc_banners]`
3. **Restaurantes** - `[vemcomer_restaurants]` com filtros
4. **Mapa** - `[vc_restaurants_map]`
5. **Para Você** - `[vc_favorites]` e `[vc_orders_history]` (só para logados)
6. **CTA Donos** - Link para cadastro de restaurante
7. **Rodapé** - Links importantes

---

## CSS e JavaScript

Os assets são carregados automaticamente quando:
- A página usa o template `Home VemComer`
- OU a página é a página inicial e contém shortcodes do VemComer

Arquivos:
- `assets/css/home.css` - Estilos da Home
- `assets/js/home.js` - Tabs e scroll suave

---

## Personalização

### Alterar Textos

Edite o template `templates/page-home.php` ou use o editor de blocos.

### Alterar Cores

Edite `assets/css/home.css`:
- Hero: `#2f9e44` (verde)
- Botões: `#2f9e44`
- Fundo CTA: `#f3f4f6`

### Adicionar Seções

Basta adicionar novos blocos no Gutenberg ou editar o template PHP.

---

## Troubleshooting

### CSS não está carregando

1. Verifique se a página está usando o template "Home VemComer"
2. Ou se é a página inicial configurada
3. Limpe o cache do WordPress e do navegador

### Shortcodes não aparecem

1. Verifique se o plugin está ativo
2. Verifique se os shortcodes estão registrados (vá em **VemComer ▸ Instalador**)
3. Limpe o cache

### Mapa não aparece

1. Verifique se o Leaflet está carregando (console do navegador)
2. Verifique se há restaurantes com coordenadas cadastradas

---

## Próximos Passos

1. **Criar página de cadastro de restaurante**:
   - Crie uma página com `[vemcomer_restaurant_signup]`
   - Atualize o link no CTA da Home

2. **Criar páginas de políticas**:
   - Política de Privacidade
   - Termos de Uso
   - Como funciona

3. **Personalizar banners**:
   - Vá em **VemComer ▸ Banners** (se disponível)
   - Ou crie posts do tipo `vc_banner`

---

## Suporte

Para dúvidas, consulte:
- [`README.md`](../README.md) - Documentação geral
- [`docs/ANALISE_INTEGRACAO_SHORTCODES.md`](ANALISE_INTEGRACAO_SHORTCODES.md) - Análise de integração

