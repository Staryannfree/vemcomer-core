<?php
/**
 * Painel do Restaurante no front-end.
 *
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Order\Statuses;
use VC\Subscription\Plan_Manager;
use WP_Post;
use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class RestaurantPanel {
    private bool $assets_enqueued = false;

    public function init(): void {
        add_shortcode( 'vemcomer_restaurant_panel', [ $this, 'render_panel' ] );
        add_action( 'admin_post_vc_panel_login', [ $this, 'handle_login' ] );
        add_action( 'admin_post_nopriv_vc_panel_login', [ $this, 'handle_login' ] );
        add_filter( 'wp_nav_menu_items', [ $this, 'maybe_add_nav_items' ], 10, 2 );
        add_action( 'admin_init', [ $this, 'ensure_caps_in_admin' ] );
        add_action( 'load-edit.php', [ $this, 'ensure_caps_in_admin' ] );
    }

    private function ensure_assets(): void {
        if ( $this->assets_enqueued ) {
            return;
        }

        wp_enqueue_style( 'vemcomer-front' );
        wp_enqueue_style( 'vemcomer-style' );
        $this->assets_enqueued = true;
    }

    public function render_panel(): string {
        $this->ensure_assets();
        $panel_url = $this->panel_url();

        if ( ! is_user_logged_in() ) {
            return $this->render_login_box( $panel_url );
        }

        $user       = wp_get_current_user();
        // Garante que o usuário logado (dono do restaurante) tenha as permissões
        // mínimas para editar seus dados e gerenciar o cardápio, mesmo que a conta
        // tenha sido criada antes da tela de validação via access_url.
        $this->ensure_caps_for_user( $user );

        $restaurant = $this->get_restaurant_for_user( $user );

        if ( ! $restaurant ) {
            return $this->render_empty_state();
        }

        // --- Lógica de Plano Básico ---
        $plan = Plan_Manager::get_restaurant_plan( $restaurant->ID );
        // Se não tiver plano ou preço for 0, consideramos básico/vitrine
        $is_basic = ! $plan || ( (float) $plan['monthly_price'] <= 0 );
        
        if ( $is_basic ) {
            wp_enqueue_style( 'vemcomer-admin-basic', plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-panel-basic.css', [], '1.0' );
        }

        // Dados de uso do plano
        $max_items = Plan_Manager::get_max_menu_items( $restaurant->ID );
        $items_count = 0;
        $usage_pct = 0;
        $is_limit_near = false;

        if ( $max_items > 0 ) {
            $items_count = (int) (new WP_Query([
                'post_type'      => 'vc_menu_item',
                'author'         => $user->ID,
                'fields'         => 'ids',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ]))->found_posts;
            
            $usage_pct = min( 100, ( $items_count / $max_items ) * 100 );
            $is_limit_near = ( $usage_pct >= 80 );
        }
        // ------------------------------

        $status_obj     = get_post_status_object( $restaurant->post_status );
        $status_label   = $status_obj ? $status_obj->label : $restaurant->post_status;
        $meta           = $this->restaurant_meta( $restaurant->ID );
        $orders         = $this->order_summary( $restaurant->ID );
        $edit_url       = $this->edit_url( $restaurant );
        $public_url     = get_permalink( $restaurant );
        $menu_admin_url = $this->menu_admin_url( $restaurant );

        // Prepara onboarding (será exibido via botão)
        $onboarding = new \VC\Frontend\Onboarding();
        $onboarding_html = $onboarding->render( $user, $restaurant, false ); // false = não exibir automaticamente

        ob_start();
        ?>
        <?php echo $onboarding_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <div class="vc-panel">
            <div class="vc-panel__header">
                <div>
                    <p class="vc-panel__eyebrow"><?php echo esc_html__( 'Painel do restaurante', 'vemcomer' ); ?></p>
                    <h2 class="vc-panel__title">
                        <?php echo esc_html( $restaurant->post_title ); ?>
                        <?php if ( $is_basic ) : ?>
                            <span class="vc-plan-badge" style="margin-left: 10px; font-size: 12px; vertical-align: middle;">Plano Vitrine</span>
                        <?php endif; ?>
                    </h2>
                    <p class="vc-panel__status"><?php echo esc_html__( 'Status:', 'vemcomer' ) . ' ' . esc_html( $status_label ); ?></p>
                </div>
                <div class="vc-panel__actions">
                    <?php
                    // Botão de Configuração Rápida (onboarding)
                    if ( $onboarding->should_show( $user, $restaurant ) ) :
                        ?>
                        <button class="vc-btn vc-btn--primary vc-btn--onboarding" type="button" data-action="open-onboarding">
                            <?php echo esc_html__( '⚡ Configuração Rápida', 'vemcomer' ); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ( $edit_url ) : ?>
                        <a class="vc-btn" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Editar dados', 'vemcomer' ); ?></a>
                    <?php endif; ?>

                    <?php if ( $menu_admin_url ) : ?>
                        <a class="vc-btn vc-btn--secondary" href="<?php echo esc_url( $menu_admin_url ); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html__( 'Gerenciar cardápio', 'vemcomer' ); ?>
                        </a>
                    <?php endif; ?>

                    <a class="vc-btn vc-btn--ghost" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html__( 'Ver página pública', 'vemcomer' ); ?>
                    </a>
                </div>
            </div>

            <?php if ( $is_limit_near ) : ?>
                <div class="vc-limit-alert">
                    <span>
                        <strong><?php echo esc_html__( 'Atenção: Você quase atingiu seu limite', 'vemcomer' ); ?></strong><br>
                        <?php echo esc_html( sprintf( __( 'Restam apenas %d itens para cadastrar no cardápio gratuito.', 'vemcomer' ), $max_items - $items_count ) ); ?>
                    </span>
                    <a href="#upgrade-modal"><?php echo esc_html__( 'Liberar itens ilimitados →', 'vemcomer' ); ?></a>
                </div>
            <?php endif; ?>

            <?php if ( $is_basic && $max_items > 0 ) : ?>
                <div class="vc-plan-widget">
                    <h4>
                        <?php echo esc_html__( 'Seu Plano', 'vemcomer' ); ?>
                        <span class="vc-plan-badge">VITRINE</span>
                    </h4>
                    <div class="vc-plan-progress">
                        <div class="vc-plan-progress-bar <?php echo $usage_pct > 90 ? 'danger' : ''; ?>" style="width: <?php echo esc_attr( $usage_pct ); ?>%;"></div>
                    </div>
                    <div class="vc-plan-stats">
                        <span><?php echo esc_html( sprintf( __( '%d de %d itens', 'vemcomer' ), $items_count, $max_items ) ); ?></span>
                        <span><?php echo esc_html( round( $usage_pct ) . '%' ); ?></span>
                    </div>
                    <a href="#upgrade-modal" class="vc-btn-upgrade-sm"><?php echo esc_html__( 'Fazer Upgrade 🚀', 'vemcomer' ); ?></a>
                </div>
            <?php endif; ?>

            <div class="vc-panel__grid">
                <div class="vc-card vc-panel__card">
                    <h3><?php echo esc_html__( 'Dados do restaurante', 'vemcomer' ); ?></h3>
                    <dl class="vc-panel__meta">
                        <?php foreach ( $meta as $label => $value ) : ?>
                            <div class="vc-panel__meta-row">
                                <dt><?php echo esc_html( $label ); ?></dt>
                                <dd><?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>

                <div class="vc-card vc-panel__card">
                    <h3><?php echo esc_html__( 'Resumo de pedidos', 'vemcomer' ); ?></h3>
                    <?php if ( ! Plan_Manager::can_view_analytics( $restaurant->ID ) ) : ?>
                        <div class="vc-blur-container">
                            <div class="vc-blur-overlay">
                                <div class="vc-blur-message">
                                    <span class="vc-blur-icon">🔒</span>
                                    <h4 class="vc-blur-title"><?php echo esc_html__( 'Quem visitou sua loja?', 'vemcomer' ); ?></h4>
                                    <p class="vc-blur-text"><?php echo esc_html__( 'Saiba com o Analytics Pro.', 'vemcomer' ); ?></p>
                                    <a href="#upgrade-modal" class="vc-btn-upgrade-lg"><?php echo esc_html__( 'Liberar Dados', 'vemcomer' ); ?></a>
                                </div>
                            </div>
                            <div class="vc-blur-content">
                                <ul class="vc-panel__summary">
                                    <li>
                                        <span class="vc-panel__summary-label"><?php echo esc_html__( 'Visitantes', 'vemcomer' ); ?></span>
                                        <strong class="vc-panel__summary-value">1.2k</strong>
                                    </li>
                                    <li>
                                        <span class="vc-panel__summary-label"><?php echo esc_html__( 'Conversão', 'vemcomer' ); ?></span>
                                        <strong class="vc-panel__summary-value">3.5%</strong>
                                    </li>
                                    <li>
                                        <span class="vc-panel__summary-label"><?php echo esc_html__( 'Pedidos Pendentes', 'vemcomer' ); ?></span>
                                        <strong class="vc-panel__summary-value">5</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php else : ?>
                        <ul class="vc-panel__summary">
                            <?php foreach ( $orders['counts'] as $key => $count ) : ?>
                                <li>
                                    <span class="vc-panel__summary-label"><?php echo esc_html( $orders['labels'][ $key ] ?? $key ); ?></span>
                                    <strong class="vc-panel__summary-value"><?php echo esc_html( (string) $count ); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="vc-card vc-panel__card">
                <h3><?php echo esc_html__( 'Últimos pedidos', 'vemcomer' ); ?></h3>
                <?php if ( empty( $orders['latest'] ) ) : ?>
                    <p class="vc-empty"><?php echo esc_html__( 'Nenhum pedido para este restaurante ainda.', 'vemcomer' ); ?></p>
                <?php else : ?>
                    <ul class="vc-panel__orders">
                        <?php foreach ( $orders['latest'] as $order ) : ?>
                            <li class="vc-panel__order">
                                <div>
                                    <strong>#<?php echo esc_html( (string) $order['id'] ); ?></strong>
                                    <span class="vc-panel__order-date"><?php echo esc_html( $order['date'] ); ?></span>
                                </div>
                                <div class="vc-panel__order-meta">
                                    <span class="vc-badge"><?php echo esc_html( $order['status_label'] ); ?></span>
                                    <span class="vc-panel__order-total"><?php echo esc_html( $order['total'] ); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function render_login_box( string $redirect ): string {
        $action  = esc_url( admin_url( 'admin-post.php' ) );
        $message = '';
        if ( isset( $_GET['vc_panel_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- apenas leitura
            $message = '<div class="vc-alert vc-alert--error">' . esc_html__( 'Usuário ou senha inválidos. Tente novamente.', 'vemcomer' ) . '</div>';
        }

        ob_start();
        ?>
        <div class="vc-panel vc-panel--login">
            <div class="vc-card vc-panel__card">
                <h3><?php echo esc_html__( 'Entrar no painel do restaurante', 'vemcomer' ); ?></h3>
                <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <form class="vc-form" method="post" action="<?php echo $action; ?>">
                    <input type="hidden" name="action" value="vc_panel_login" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>" />
                    <?php wp_nonce_field( 'vc_panel_login', '_vc_panel_login_nonce' ); ?>
                    <label>
                        <?php echo esc_html__( 'E-mail ou usuário', 'vemcomer' ); ?>
                        <input type="text" name="vc_username" required autocomplete="username" />
                    </label>
                    <label>
                        <?php echo esc_html__( 'Senha', 'vemcomer' ); ?>
                        <input type="password" name="vc_password" required autocomplete="current-password" />
                    </label>
                    <label class="vc-form__check">
                        <input type="checkbox" name="vc_remember" value="1" />
                        <span><?php echo esc_html__( 'Lembrar de mim', 'vemcomer' ); ?></span>
                    </label>
                    <div class="vc-form__actions">
                        <button type="submit" class="vc-btn"><?php echo esc_html__( 'Entrar', 'vemcomer' ); ?></button>
                        <a class="vc-link" href="<?php echo esc_url( wp_lostpassword_url( $redirect ) ); ?>"><?php echo esc_html__( 'Esqueci minha senha', 'vemcomer' ); ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function render_empty_state(): string {
        $signup_url = (string) apply_filters( 'vemcomer/restaurant_panel_signup_url', home_url( '/cadastro-de-restaurante/' ) );

        ob_start();
        ?>
        <div class="vc-panel">
            <div class="vc-card vc-panel__card">
                <h3><?php echo esc_html__( 'Nenhum restaurante vinculado à sua conta.', 'vemcomer' ); ?></h3>
                <p><?php echo esc_html__( 'Peça para o administrador associar você a um restaurante ou envie um novo cadastro.', 'vemcomer' ); ?></p>
                <div class="vc-form__actions">
                    <a class="vc-btn" href="<?php echo esc_url( $signup_url ); ?>">
                        <?php echo esc_html__( 'Enviar cadastro', 'vemcomer' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function restaurant_meta( int $restaurant_id ): array {
        $fields = [
            __( 'CNPJ', 'vemcomer' )     => get_post_meta( $restaurant_id, 'vc_restaurant_cnpj', true ),
            __( 'WhatsApp', 'vemcomer' ) => get_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', true ),
            __( 'Site', 'vemcomer' )     => get_post_meta( $restaurant_id, 'vc_restaurant_site', true ),
            __( 'Endereço', 'vemcomer' ) => get_post_meta( $restaurant_id, 'vc_restaurant_address', true ),
            __( 'Horário', 'vemcomer' )  => get_post_meta( $restaurant_id, 'vc_restaurant_open_hours', true ),
            __( 'Delivery', 'vemcomer' ) => get_post_meta( $restaurant_id, 'vc_restaurant_delivery', true ) === '1'
                ? __( 'Oferece delivery', 'vemcomer' )
                : __( 'Somente retirada', 'vemcomer' ),
        ];

        $out = [];
        foreach ( $fields as $label => $value ) {
            $display = $value;
            if ( $label === __( 'Site', 'vemcomer' ) && $value ) {
                $display = '<a href="' . esc_url( (string) $value ) . '" target="_blank" rel="noopener">' . esc_html( (string) $value ) . '</a>';
            } elseif ( $label === __( 'WhatsApp', 'vemcomer' ) && $value ) {
                $phone = preg_replace( '/\D+/', '', (string) $value );
                $wa    = 'https://wa.me/' . $phone;
                $display = '<a href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . esc_html( (string) $value ) . '</a>';
            } else {
                $display = esc_html( (string) ( $value ?: '—' ) );
            }
            $out[ $label ] = $display;
        }

        return $out;
    }

    private function order_summary( int $restaurant_id ): array {
        $labels = Statuses::STATUSES;
        $counts = array_fill_keys( array_keys( $labels ), 0 );

        $query = new WP_Query([
            'post_type'      => 'vc_pedido',
            'post_status'    => array_keys( $labels ),
            'posts_per_page' => 200,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [ 'key' => '_vc_restaurant_id', 'value' => (string) $restaurant_id ],
            ],
        ]);

        foreach ( $query->posts as $order_id ) {
            $status = get_post_status( $order_id );
            if ( isset( $counts[ $status ] ) ) {
                $counts[ $status ]++;
            }
        }
        wp_reset_postdata();

        $latest_posts = get_posts([
            'post_type'      => 'vc_pedido',
            'post_status'    => array_keys( $labels ),
            'numberposts'    => 5,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [ 'key' => '_vc_restaurant_id', 'value' => (string) $restaurant_id ],
            ],
        ]);

        $latest = [];
        foreach ( $latest_posts as $post ) {
            $status = get_post_status( $post );
            $latest[] = [
                'id'           => $post->ID,
                'date'         => get_the_date( '', $post ),
                'status_label' => $labels[ $status ] ?? $status,
                'total'        => (string) get_post_meta( $post->ID, '_vc_total', true ),
            ];
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'latest' => $latest,
        ];
    }

    public function get_restaurant_for_user( WP_User $user ): ?WP_Post {
        $filtered = (int) apply_filters( 'vemcomer/restaurant_id_for_user', 0, $user );
        if ( $filtered > 0 ) {
            $post = get_post( $filtered );
            if ( $post && 'vc_restaurant' === $post->post_type ) {
                return $post;
            }
        }

        $meta_id = (int) get_user_meta( $user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $post = get_post( $meta_id );
            if ( $post && 'vc_restaurant' === $post->post_type ) {
                return $post;
            }
        }

        $q = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'author'         => $user->ID,
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'no_found_rows'  => true,
        ]);

        $post = $q->have_posts() ? $q->posts[0] : null;
        wp_reset_postdata();

        return $post instanceof WP_Post ? $post : null;
    }

    private function handle_redirect( string $redirect ): void {
        wp_safe_redirect( $redirect ?: home_url() );
        exit;
    }

    public function handle_login(): void {
        check_admin_referer( 'vc_panel_login', '_vc_panel_login_nonce' );

        $username = sanitize_text_field( wp_unslash( $_POST['vc_username'] ?? '' ) );
        $password = (string) ( $_POST['vc_password'] ?? '' );
        $remember = ! empty( $_POST['vc_remember'] );
        $redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : $this->panel_url();

        $user = wp_signon(
            [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ],
            is_ssl()
        );

        if ( is_wp_error( $user ) ) {
            $this->handle_redirect( add_query_arg( 'vc_panel_error', 'login_failed', $redirect ) );
        }

        $this->handle_redirect( $redirect );
    }

    private function panel_url(): string {
        return (string) apply_filters( 'vemcomer/restaurant_panel_url', home_url( '/painel-restaurante/' ) );
    }

    public function edit_url( WP_Post $restaurant ): string {
        $url = (string) apply_filters( 'vemcomer/restaurant_panel_edit_url', '', $restaurant );
        if ( $url ) {
            return $url;
        }

        return (string) get_edit_post_link( $restaurant );
    }

    /**
     * URL para gerenciamento do cardápio (admin) filtrado pelo restaurante.
     * Permite que o dono cadastre/edite itens de menu de forma profissional.
     */
    public function menu_admin_url( WP_Post $restaurant ): string {
        // Permite override completo via filtro (por exemplo, para um painel 100% front-end).
        $url = (string) apply_filters( 'vemcomer/restaurant_panel_menu_url', '', $restaurant );
        if ( $url ) {
            return $url;
        }

        // Garante que o usuário tenha permissão mínima para gerenciar itens de cardápio.
        if ( ! current_user_can( 'edit_vc_menu_items' ) ) {
            return '';
        }

        $base = admin_url( 'edit.php?post_type=vc_menu_item' );

        return (string) add_query_arg(
            [
                '_vc_restaurant_id' => (int) $restaurant->ID,
            ],
            $base
        );
    }

    public function maybe_add_nav_items( string $items, $args ): string {
        if ( ! is_user_logged_in() ) {
            return $items;
        }

        $allow = (bool) apply_filters( 'vemcomer/restaurant_panel_nav', true, $args );
        if ( ! $allow ) {
            return $items;
        }

        $panel  = $this->panel_url();
        $logout = wp_logout_url( $panel );

        $items .= '<li class="menu-item menu-item-vc-panel"><a href="' . esc_url( $panel ) . '">' . esc_html__( 'Painel', 'vemcomer' ) . '</a></li>';
        $items .= '<li class="menu-item menu-item-vc-logout"><a href="' . esc_url( $logout ) . '">' . esc_html__( 'Sair', 'vemcomer' ) . '</a></li>';

        return $items;
    }

    /**
     * Garante que usuários "lojista" tenham as capabilities necessárias
     * ao acessar o admin do WordPress.
     */
    public function ensure_caps_in_admin(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof WP_User ) {
            return;
        }

        // Aplica apenas para usuários com role "lojista"
        if ( ! in_array( 'lojista', $user->roles, true ) ) {
            return;
        }

        $this->ensure_caps_for_user( $user );
    }

    /**
     * Garante que o usuário logado tenha as capabilities necessárias
     * para editar o próprio restaurante e gerenciar os itens de cardápio.
     *
     * Isso é útil para contas criadas antes do fluxo de validação via access_url.
     *
     * @param WP_User $user Usuário logado.
     */
    private function ensure_caps_for_user( WP_User $user ): void {
        // Caps do CPT vc_restaurant.
        if ( function_exists( 'vc_get_restaurant_caps' ) ) {
            $restaurant_caps = (array) vc_get_restaurant_caps();
            foreach ( $restaurant_caps as $cap ) {
                if ( $cap && ! $user->has_cap( $cap ) ) {
                    $user->add_cap( $cap );
                }
            }
        }

        // Caps mínimos para gerenciar itens de cardápio (vc_menu_item).
        if ( function_exists( 'vc_get_menu_caps' ) ) {
            $menu_caps = (array) vc_get_menu_caps();
        } else {
            $menu_caps = [
                'edit_vc_menu_item',
                'read_vc_menu_item',
                'delete_vc_menu_item',
                'edit_vc_menu_items',
                'publish_vc_menu_items',
                'delete_vc_menu_items',
                'edit_published_vc_menu_items',
                'delete_published_vc_menu_items',
                'create_vc_menu_items',
            ];
        }

        foreach ( $menu_caps as $cap ) {
            if ( $cap && ! $user->has_cap( $cap ) ) {
                $user->add_cap( $cap );
            }
        }

        // Adiciona capability básica edit_posts que o WordPress pode verificar
        // antes de mapear para edit_vc_menu_items via map_meta_cap
        if ( ! $user->has_cap( 'edit_posts' ) ) {
            $user->add_cap( 'edit_posts' );
        }
    }
}
