# Sistema de Onboarding para Donos de Restaurantes

## Visão Geral

Sistema de onboarding que guia novos donos de restaurantes através dos primeiros passos de configuração do seu estabelecimento na plataforma VemComer.

## Funcionalidades

### Steps do Onboarding

1. **Bem-vindo** - Tela inicial de boas-vindas
2. **Complete seu perfil** - Incentiva adicionar informações essenciais (WhatsApp, endereço, horários)
3. **Adicione itens ao cardápio** - Orienta criar pelo menos 3 itens
4. **Configure delivery** - Garante que delivery esteja configurado
5. **Veja sua página pública** - Mostra como os clientes veem o restaurante

### Características

- ✅ **Progresso persistente** - Salva o progresso do usuário
- ✅ **Verificação automática** - Detecta quando steps são completados
- ✅ **Dismissível** - Usuário pode pular e retomar depois
- ✅ **Responsivo** - Funciona em desktop e mobile
- ✅ **Acessível** - Segue boas práticas de acessibilidade

## Como Funciona

### Detecção

O onboarding é exibido automaticamente quando:
- Usuário tem role "lojista"
- Usuário tem um restaurante vinculado
- Onboarding ainda não foi completado
- Onboarding não foi dispensado

### Persistência

O progresso é salvo em user meta:
- `vc_onboarding_completed` - Boolean indicando se foi completado
- `vc_onboarding_steps` - Array com steps completados
- `vc_onboarding_dismissed` - Boolean indicando se foi dispensado

### Verificação Automática

Alguns steps são verificados automaticamente:
- **Perfil completo**: Verifica se WhatsApp, endereço e horários estão preenchidos
- **Itens no cardápio**: Verifica se há pelo menos 3 itens publicados
- **Delivery configurado**: Verifica se delivery está definido

## Implementação Técnica

### Arquivos

- `inc/Frontend/Onboarding.php` - Classe principal
- Integrado em `inc/Frontend/RestaurantPanel.php`
- Registrado em `vemcomer-core.php`

### Endpoints AJAX

- `vc_onboarding_complete_step` - Marca um step como completo
- `vc_onboarding_dismiss` - Dispensa o onboarding
- `vc_onboarding_reset` - Reseta o onboarding (admin only)

### Hooks e Filtros

Nenhum filtro customizado ainda, mas pode ser estendido facilmente.

## Próximos Passos

### CSS e JavaScript

Precisa criar:
1. **CSS** (`assets/css/onboarding.css`) - Estilos do modal e steps
2. **JavaScript** (`assets/js/onboarding.js`) - Interatividade e AJAX

### Melhorias Futuras

- [ ] Adicionar animações suaves
- [ ] Tooltips explicativos
- [ ] Vídeos tutoriais (opcional)
- [ ] Checklist visual mais elaborado
- [ ] Notificações quando steps são completados
- [ ] Integração com analytics

## Uso

O onboarding é exibido automaticamente no painel do restaurante. Não requer configuração adicional.

Para resetar o onboarding de um usuário (admin):
```php
delete_user_meta( $user_id, 'vc_onboarding_completed' );
delete_user_meta( $user_id, 'vc_onboarding_steps' );
delete_user_meta( $user_id, 'vc_onboarding_dismissed' );
```

## Estrutura de Dados

### User Meta

```php
// Completado
get_user_meta( $user_id, 'vc_onboarding_completed' ); // true/false

// Steps completados
get_user_meta( $user_id, 'vc_onboarding_steps' ); // ['welcome', 'complete_profile', ...]

// Dispensado
get_user_meta( $user_id, 'vc_onboarding_dismissed' ); // true/false
```

