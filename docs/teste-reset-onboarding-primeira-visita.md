# Como testar o reset para primeira visita do lojista

Este roteiro verifica se o atalho de **Resetar onboarding (primeira visita)** no admin realmente faz o lojista reenxergar o wizard como se fosse o primeiro acesso.

## Pré-requisitos
- Super admin (ou usuário com `manage_options`) autenticado no WordPress.
- Um restaurante (`vc_restaurant`) já existente e vinculado a um usuário lojista.
- Wizard de onboarding previamente concluído para esse restaurante (ou seja, não deve abrir mais por padrão).

## Passo a passo
1. Acesse **WP-Admin → Restaurantes** (`/wp-admin/edit.php?post_type=vc_restaurant`).
2. Passe o mouse sobre o restaurante alvo e clique em **Resetar onboarding (primeira visita)**.
   - Para múltiplos restaurantes, selecione-os, escolha a ação em massa **Resetar onboarding (primeira visita)** e aplique.
3. Confirme a mensagem de sucesso *“Onboarding resetado: os lojistas verão a experiência de primeira visita.”* na listagem.
4. Entre como o usuário lojista vinculado (ou abra uma janela anônima e faça login no painel do lojista).
5. Abra o painel ou o gestor de cardápio:
   - O wizard deve aparecer do **passo 1**, mesmo que o cardápio/dados já estejam completos.
6. Conclua o wizard até o fim. Ao terminar, atualize a página:
   - O wizard não deve reaparecer, mostrando que o flag de primeira visita foi limpo.

## Dicas de validação
- O reset **não apaga dados** do restaurante; apenas limpa metas de conclusão e define o flag `_vc_onboarding_force_first_visit`.
- Se o wizard não abrir após o reset, verifique se você está usando o restaurante correto e se o usuário está realmente vinculado via meta `vc_restaurant_id`.
