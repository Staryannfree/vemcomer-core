<?php
/**
 * Painel do Restaurante no front-end.
 *
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Order\Statuses;
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
        $restaurant = $this->get_restaurant_for_user( $user );

        if ( ! $restaurant ) {
            return $this->render_empty_state();
        }

        $status_obj   = get_post_status_object( $restaurant->post_status );
        $status_label = $status_obj ? $status_obj->label : $restaurant->post_status;
        $meta         = $this->restaurant_meta( $restaurant->ID );
        $orders       = $this->order_summary( $restaurant->ID );
        $edit_url     = $this->edit_url( $restaurant );
        $public_url   = get_permalink( $restaurant );

        ob_start();
        ?>
        <div class="vc-panel">
            <div class="vc-panel__header">
                <div>
                    <p class="vc-panel__eyebrow"><?php echo esc_html__( 'Painel do restaurante', 'vemcomer' ); ?></p>
                    <h2 class="vc-panel__title"><?php echo esc_html( $restaurant->post_title ); ?></h2>
                    <p class="vc-panel__status"><?php echo esc_html__( 'Status:', 'vemcomer' ) . ' ' . esc_html( $status_label ); ?></p>
                </div>
                <div class="vc-panel__actions">
                    <?php if ( $edit_url ) : ?>
                        <a class="vc-btn" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Editar dados', 'vemcomer' ); ?></a>
                    <?php endif; ?>
                    <a class="vc-btn vc-btn--ghost" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html__( 'Ver página pública', 'vemcomer' ); ?>
                    </a>
                </div>
            </div>

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
                    <ul class="vc-panel__summary">
                        <?php foreach ( $orders['counts'] as $key => $count ) : ?>
                            <li>
                                <span class="vc-panel__summary-label"><?php echo esc_html( $orders['labels'][ $key ] ?? $key ); ?></span>
                                <strong class="vc-panel__summary-value"><?php echo esc_html( (string) $count ); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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

    private function get_restaurant_for_user( WP_User $user ): ?WP_Post {
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

    private function edit_url( WP_Post $restaurant ): string {
        $url = (string) apply_filters( 'vemcomer/restaurant_panel_edit_url', '', $restaurant );
        if ( $url ) {
            return $url;
        }

        return (string) get_edit_post_link( $restaurant );
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
}
