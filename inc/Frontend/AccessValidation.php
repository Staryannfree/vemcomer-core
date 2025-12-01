<?php
/**
 * Página de validação de acesso para restaurantes aprovados.
 * Permite que restaurantes criem email e senha usando o token access_url.
 *
 * @package VemComerCore
 */

namespace VC\Frontend;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AccessValidation {
    private bool $assets_enqueued = false;

    public function init(): void {
        add_action( 'template_redirect', [ $this, 'handle_validation_page' ] );
        add_action( 'admin_post_vc_validate_access', [ $this, 'handle_validation_form' ] );
        add_action( 'admin_post_nopriv_vc_validate_access', [ $this, 'handle_validation_form' ] );
    }

    private function ensure_assets(): void {
        if ( $this->assets_enqueued ) {
            return;
        }

        wp_enqueue_style( 'vemcomer-front' );
        wp_enqueue_style( 'vemcomer-style' );
        $this->assets_enqueued = true;
    }

    /**
     * Intercepta a requisição para /validar-acesso/ e renderiza a página de validação.
     */
    public function handle_validation_page(): void {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        
        // Verifica se é a página de validação
        if ( ! preg_match( '#/validar-acesso/#', $request_uri ) ) {
            return;
        }

        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

        if ( empty( $token ) ) {
            $this->render_error_page( __( 'Token de acesso não fornecido.', 'vemcomer' ) );
            return;
        }

        $restaurant = $this->get_restaurant_by_token( $token );
        if ( ! $restaurant ) {
            $this->render_error_page( __( 'Token de acesso inválido ou expirado.', 'vemcomer' ) );
            return;
        }

        // Verifica se já existe um usuário vinculado a este restaurante
        $existing_user = $this->get_user_for_restaurant( $restaurant->ID );
        if ( $existing_user ) {
            $this->render_error_page( __( 'Este restaurante já possui uma conta criada. Use a página de login para acessar.', 'vemcomer' ) );
            return;
        }

        $this->render_validation_form( $restaurant, $token );
    }

    /**
     * Busca restaurante pelo token de acesso.
     *
     * @param string $token Token de acesso.
     * @return WP_Post|null
     */
    private function get_restaurant_by_token( string $token ): ?WP_Post {
        $query = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => 'vc_restaurant_access_url',
                    'value' => $token,
                ],
            ],
            'no_found_rows'  => true,
        ]);

        $post = $query->have_posts() ? $query->posts[0] : null;
        wp_reset_postdata();

        return $post instanceof WP_Post ? $post : null;
    }

    /**
     * Busca usuário vinculado ao restaurante.
     *
     * @param int $restaurant_id ID do restaurante.
     * @return WP_User|null
     */
    private function get_user_for_restaurant( int $restaurant_id ): ?WP_User {
        $users = get_users([
            'meta_key'   => 'vc_restaurant_id',
            'meta_value' => $restaurant_id,
            'number'     => 1,
        ]);

        return ! empty( $users ) && $users[0] instanceof WP_User ? $users[0] : null;
    }

    /**
     * Renderiza o formulário de validação.
     *
     * @param WP_Post $restaurant Restaurante.
     * @param string  $token      Token de acesso.
     */
    private function render_validation_form( WP_Post $restaurant, string $token ): void {
        $this->ensure_assets();

        $message = $this->get_feedback_message();
        $action  = esc_url( admin_url( 'admin-post.php' ) );
        $restaurant_name = get_the_title( $restaurant );

        // Define o título da página
        add_filter( 'wp_title', function() use ( $restaurant_name ) {
            return sprintf( __( 'Validar acesso - %s', 'vemcomer' ), $restaurant_name ) . ' - ' . get_bloginfo( 'name' );
        }, 10, 1 );

        // Interrompe o carregamento normal do template
        status_header( 200 );
        get_header();
        ?>
        <div class="vc-validation-page">
            <div class="vc-card vc-card--centered">
                <h2><?php echo esc_html__( 'Criar conta de acesso', 'vemcomer' ); ?></h2>
                <p class="vc-validation__restaurant-name">
                    <strong><?php echo esc_html__( 'Restaurante:', 'vemcomer' ); ?></strong>
                    <?php echo esc_html( $restaurant_name ); ?>
                </p>
                <?php if ( $message ) : ?>
                    <div class="vc-message <?php echo esc_attr( $message['type'] ); ?>">
                        <?php echo esc_html( $message['text'] ); ?>
                    </div>
                <?php endif; ?>
                <form class="vc-form" method="post" action="<?php echo $action; ?>">
                    <input type="hidden" name="action" value="vc_validate_access" />
                    <input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>" />
                    <?php wp_nonce_field( 'vc_validate_access', '_vc_validate_nonce' ); ?>
                    
                    <label>
                        <?php echo esc_html__( 'E-mail', 'vemcomer' ); ?>
                        <input 
                            type="email" 
                            name="email" 
                            required 
                            autocomplete="email"
                            placeholder="<?php echo esc_attr__( 'seu@email.com', 'vemcomer' ); ?>"
                        />
                    </label>
                    
                    <label>
                        <?php echo esc_html__( 'Senha', 'vemcomer' ); ?>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            autocomplete="new-password"
                            minlength="6"
                            placeholder="<?php echo esc_attr__( 'Mínimo 6 caracteres', 'vemcomer' ); ?>"
                        />
                    </label>
                    
                    <label>
                        <?php echo esc_html__( 'Confirmar senha', 'vemcomer' ); ?>
                        <input 
                            type="password" 
                            name="password_confirm" 
                            required 
                            autocomplete="new-password"
                            minlength="6"
                            placeholder="<?php echo esc_attr__( 'Digite a senha novamente', 'vemcomer' ); ?>"
                        />
                    </label>
                    
                    <div class="vc-form__actions">
                        <button type="submit" class="vc-btn vc-btn--primary">
                            <?php echo esc_html__( 'Criar conta e acessar', 'vemcomer' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }

    /**
     * Renderiza página de erro.
     *
     * @param string $message Mensagem de erro.
     */
    private function render_error_page( string $message ): void {
        $this->ensure_assets();

        status_header( 404 );
        get_header();
        ?>
        <div class="vc-validation-page">
            <div class="vc-card vc-card--centered">
                <h2><?php echo esc_html__( 'Erro na validação', 'vemcomer' ); ?></h2>
                <div class="vc-message vc-message--error">
                    <?php echo esc_html( $message ); ?>
                </div>
                <p>
                    <a href="<?php echo esc_url( home_url() ); ?>" class="vc-btn">
                        <?php echo esc_html__( 'Voltar ao início', 'vemcomer' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }

    /**
     * Obtém mensagem de feedback da URL.
     *
     * @return array|null
     */
    private function get_feedback_message(): ?array {
        if ( ! isset( $_GET['vc_validation_error'] ) && ! isset( $_GET['vc_validation_success'] ) ) {
            return null;
        }

        $error_code = isset( $_GET['vc_validation_error'] ) ? sanitize_text_field( wp_unslash( $_GET['vc_validation_error'] ) ) : '';
        $success = isset( $_GET['vc_validation_success'] );

        if ( $success ) {
            return [
                'type' => 'success',
                'text' => __( 'Conta criada com sucesso! Você será redirecionado...', 'vemcomer' ),
            ];
        }

        $messages = [
            'invalid_token'      => __( 'Token de acesso inválido.', 'vemcomer' ),
            'invalid_email'      => __( 'E-mail inválido.', 'vemcomer' ),
            'email_exists'       => __( 'Este e-mail já está em uso.', 'vemcomer' ),
            'password_short'     => __( 'A senha deve ter pelo menos 6 caracteres.', 'vemcomer' ),
            'password_mismatch'  => __( 'As senhas não coincidem.', 'vemcomer' ),
            'user_exists'        => __( 'Este restaurante já possui uma conta criada.', 'vemcomer' ),
            'insert_error'       => __( 'Erro ao criar conta. Tente novamente.', 'vemcomer' ),
        ];

        return [
            'type' => 'error',
            'text' => $messages[ $error_code ] ?? __( 'Erro desconhecido.', 'vemcomer' ),
        ];
    }

    /**
     * Processa o formulário de validação.
     */
    public function handle_validation_form(): void {
        check_admin_referer( 'vc_validate_access', '_vc_validate_nonce' );

        $token           = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $email           = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password        = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
        $password_confirm = isset( $_POST['password_confirm'] ) ? (string) $_POST['password_confirm'] : '';

        $redirect_url = home_url( '/validar-acesso/?token=' . urlencode( $token ) );

        // Validações
        if ( empty( $token ) ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'invalid_token', $redirect_url ) );
            exit;
        }

        $restaurant = $this->get_restaurant_by_token( $token );
        if ( ! $restaurant ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'invalid_token', $redirect_url ) );
            exit;
        }

        // Verifica se já existe usuário
        $existing_user = $this->get_user_for_restaurant( $restaurant->ID );
        if ( $existing_user ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'user_exists', $redirect_url ) );
            exit;
        }

        if ( ! is_email( $email ) ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'invalid_email', $redirect_url ) );
            exit;
        }

        if ( email_exists( $email ) ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'email_exists', $redirect_url ) );
            exit;
        }

        if ( strlen( $password ) < 6 ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'password_short', $redirect_url ) );
            exit;
        }

        if ( $password !== $password_confirm ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'password_mismatch', $redirect_url ) );
            exit;
        }

        // Cria o usuário (role básica, capabilities específicas são atribuídas abaixo)
        $restaurant_name = get_the_title( $restaurant );
        $user_id = wp_insert_user([
            'user_login'   => $email,
            'user_email'   => $email,
            'display_name' => $restaurant_name,
            'user_pass'    => $password,
            'role'         => 'lojista',
        ]);

        if ( is_wp_error( $user_id ) ) {
            wp_safe_redirect( add_query_arg( 'vc_validation_error', 'insert_error', $redirect_url ) );
            exit;
        }

        // Vincula o usuário ao restaurante (lado do usuário)
        update_user_meta( $user_id, 'vc_restaurant_id', $restaurant->ID );

        // Garante que o restaurante também reconheça este usuário como dono:
        // - define o autor do post como o lojista recém-criado
        // - dispara um hook para extensões futuras
        wp_update_post(
            [
                'ID'          => $restaurant->ID,
                'post_author' => (int) $user_id,
            ]
        );

        /**
         * Dispara quando um usuário lojista é vinculado a um restaurante.
         *
         * @param int $restaurant_id ID do restaurante.
         * @param int $user_id       ID do usuário lojista.
         */
        do_action( 'vemcomer/restaurant_owner_linked', (int) $restaurant->ID, (int) $user_id );

        // Concede permissões para gerenciar o próprio restaurante e o cardápio
        $this->grant_restaurant_caps( (int) $user_id );

        // Faz login automático
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        // Redireciona para o painel do restaurante
        $panel_url = apply_filters( 'vemcomer/restaurant_panel_url', home_url( '/painel-restaurante/' ) );
        wp_safe_redirect( add_query_arg( 'vc_validation_success', '1', $panel_url ) );
        exit;
    }

    /**
     * Concede capabilities ao usuário do restaurante para gerenciar
     * seus próprios dados e itens de cardápio.
     *
     * @param int $user_id ID do usuário criado.
     */
    private function grant_restaurant_caps( int $user_id ): void {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return;
        }

        // Caps do CPT vc_restaurant (se helper existir).
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
    }
}

