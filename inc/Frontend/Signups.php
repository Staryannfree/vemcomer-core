<?php
/**
 * Formulários públicos de cadastro (restaurantes e clientes)
 *
 * @package VemComerCore
 */

namespace VC\Frontend;

use function VC\Utils\validate_cnpj;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Signups {
    private bool $assets_enqueued = false;

    public function init(): void {
        add_shortcode( 'vemcomer_restaurant_signup', [ $this, 'restaurant_form' ] );
        add_shortcode( 'vemcomer_customer_signup', [ $this, 'customer_form' ] );

        add_action( 'admin_post_vc_restaurant_signup', [ $this, 'handle_restaurant_form' ] );
        add_action( 'admin_post_nopriv_vc_restaurant_signup', [ $this, 'handle_restaurant_form' ] );
        add_action( 'admin_post_vc_customer_signup', [ $this, 'handle_customer_form' ] );
        add_action( 'admin_post_nopriv_vc_customer_signup', [ $this, 'handle_customer_form' ] );
    }

    private function ensure_assets(): void {
        if ( $this->assets_enqueued ) {
            return;
        }

        wp_enqueue_style( 'vemcomer-front' );
        $this->assets_enqueued = true;
    }

    public function restaurant_form(): string {
        $this->ensure_assets();

        $message = $this->get_feedback_box( 'vc_restaurant' );
        $action  = esc_url( admin_url( 'admin-post.php' ) );

        ob_start();
        ?>
        <form class="vc-form" method="post" action="<?php echo $action; ?>">
            <h3><?php echo esc_html__( 'Cadastro de restaurante', 'vemcomer' ); ?></h3>
            <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <input type="hidden" name="action" value="vc_restaurant_signup" />
            <?php wp_nonce_field( 'vc_restaurant_signup', '_vc_restaurant_nonce' ); ?>
            <label>
                <?php echo esc_html__( 'Nome do restaurante', 'vemcomer' ); ?>
                <input type="text" name="restaurant_name" required />
            </label>
            <label>
                <?php echo esc_html__( 'CNPJ', 'vemcomer' ); ?>
                <input type="text" name="restaurant_cnpj" placeholder="00.000.000/0000-00" required />
            </label>
            <div class="vc-form__row">
                <label>
                    <?php echo esc_html__( 'WhatsApp', 'vemcomer' ); ?>
                    <input type="text" name="restaurant_whatsapp" placeholder="55 11 99999-9999" />
                </label>
                <label>
                    <?php echo esc_html__( 'Site', 'vemcomer' ); ?>
                    <input type="url" name="restaurant_site" placeholder="https://" />
                </label>
            </div>
            <label>
                <?php echo esc_html__( 'Endereço completo', 'vemcomer' ); ?>
                <input type="text" name="restaurant_address" />
            </label>
            <div class="vc-form__row">
                <label>
                    <?php echo esc_html__( 'Cozinha/Categoria', 'vemcomer' ); ?>
                    <input type="text" name="restaurant_cuisine" placeholder="ex.: pizza" />
                </label>
                <label>
                    <?php echo esc_html__( 'Localização/bairro', 'vemcomer' ); ?>
                    <input type="text" name="restaurant_location" placeholder="ex.: centro" />
                </label>
            </div>
            <label>
                <?php echo esc_html__( 'Horário de funcionamento', 'vemcomer' ); ?>
                <textarea name="restaurant_open_hours" rows="3"></textarea>
            </label>
            <label class="vc-form__check">
                <input type="checkbox" name="restaurant_delivery" value="1" />
                <span><?php echo esc_html__( 'Oferece delivery próprio', 'vemcomer' ); ?></span>
            </label>
            <div class="vc-form__actions">
                <button type="submit" class="vc-btn"><?php echo esc_html__( 'Enviar para análise', 'vemcomer' ); ?></button>
            </div>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public function customer_form(): string {
        $this->ensure_assets();

        $message = $this->get_feedback_box( 'vc_customer' );
        $action  = esc_url( admin_url( 'admin-post.php' ) );

        ob_start();
        ?>
        <form class="vc-form" method="post" action="<?php echo $action; ?>">
            <h3><?php echo esc_html__( 'Crie sua conta', 'vemcomer' ); ?></h3>
            <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <input type="hidden" name="action" value="vc_customer_signup" />
            <?php wp_nonce_field( 'vc_customer_signup', '_vc_customer_nonce' ); ?>
            <label>
                <?php echo esc_html__( 'Nome completo', 'vemcomer' ); ?>
                <input type="text" name="customer_name" required />
            </label>
            <label>
                <?php echo esc_html__( 'E-mail', 'vemcomer' ); ?>
                <input type="email" name="customer_email" required />
            </label>
            <label>
                <?php echo esc_html__( 'Telefone (opcional)', 'vemcomer' ); ?>
                <input type="text" name="customer_phone" />
            </label>
            <div class="vc-form__row">
                <label>
                    <?php echo esc_html__( 'Senha', 'vemcomer' ); ?>
                    <input type="password" name="customer_password" required />
                </label>
                <label>
                    <?php echo esc_html__( 'Confirmar senha', 'vemcomer' ); ?>
                    <input type="password" name="customer_password_confirm" required />
                </label>
            </div>
            <div class="vc-form__actions">
                <button type="submit" class="vc-btn"><?php echo esc_html__( 'Criar conta', 'vemcomer' ); ?></button>
            </div>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_restaurant_form(): void {
        check_admin_referer( 'vc_restaurant_signup', '_vc_restaurant_nonce' );

        $name      = sanitize_text_field( wp_unslash( $_POST['restaurant_name'] ?? '' ) );
        $cnpj_raw  = sanitize_text_field( wp_unslash( $_POST['restaurant_cnpj'] ?? '' ) );
        $whatsapp  = sanitize_text_field( wp_unslash( $_POST['restaurant_whatsapp'] ?? '' ) );
        $site      = esc_url_raw( wp_unslash( $_POST['restaurant_site'] ?? '' ) );
        $open      = wp_kses_post( wp_unslash( $_POST['restaurant_open_hours'] ?? '' ) );
        $address   = sanitize_text_field( wp_unslash( $_POST['restaurant_address'] ?? '' ) );
        $cuisine   = sanitize_title( wp_unslash( $_POST['restaurant_cuisine'] ?? '' ) );
        $location  = sanitize_title( wp_unslash( $_POST['restaurant_location'] ?? '' ) );
        $delivery  = isset( $_POST['restaurant_delivery'] ) ? '1' : '0';

        if ( '' === $name ) {
            $this->redirect_with_args( [ 'vc_restaurant_error' => 'missing_name' ] );
        }

        if ( '' === $cnpj_raw ) {
            $this->redirect_with_args( [ 'vc_restaurant_error' => 'missing_cnpj' ] );
        }

        $cnpj_validation = validate_cnpj( $cnpj_raw );
        if ( is_wp_error( $cnpj_validation ) ) {
            $this->redirect_with_args( [ 'vc_restaurant_error' => $cnpj_validation->get_error_code() ?: 'invalid_cnpj' ] );
        }

        $status = 'pending';
        $post_id = wp_insert_post(
            [
                'post_type'   => 'vc_restaurant',
                'post_status' => $status,
                'post_title'  => $name,
            ]
        );

        if ( is_wp_error( $post_id ) ) {
            $this->redirect_with_args( [ 'vc_restaurant_error' => 'insert_error' ] );
        }

        update_post_meta( $post_id, 'vc_restaurant_cnpj', $cnpj_validation['normalized'] ?? '' );
        update_post_meta( $post_id, 'vc_restaurant_whatsapp', $whatsapp );
        update_post_meta( $post_id, 'vc_restaurant_site', $site );
        update_post_meta( $post_id, 'vc_restaurant_open_hours', $open );
        update_post_meta( $post_id, 'vc_restaurant_delivery', $delivery );
        update_post_meta( $post_id, 'vc_restaurant_address', $address );

        if ( $cuisine && taxonomy_exists( 'vc_cuisine' ) ) {
            wp_set_object_terms( $post_id, [ $cuisine ], 'vc_cuisine', false );
        }

        if ( $location && taxonomy_exists( 'vc_location' ) ) {
            wp_set_object_terms( $post_id, [ $location ], 'vc_location', false );
        }

        do_action( 'vemcomer/restaurant_registered', (int) $post_id );

        $this->redirect_with_args( [ 'vc_restaurant_submitted' => '1' ] );
    }

    public function handle_customer_form(): void {
        check_admin_referer( 'vc_customer_signup', '_vc_customer_nonce' );

        $name     = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
        $email    = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
        $phone    = sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) );
        $password = (string) ( $_POST['customer_password'] ?? '' );
        $confirm  = (string) ( $_POST['customer_password_confirm'] ?? '' );

        if ( '' === $name ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'missing_name' ] );
        }

        if ( ! is_email( $email ) ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'invalid_email' ] );
        }

        if ( email_exists( $email ) ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'email_exists' ] );
        }

        if ( strlen( $password ) < 6 ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'password_short' ] );
        }

        if ( $password !== $confirm ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'password_mismatch' ] );
        }

        $role = get_role( 'customer' ) ? 'customer' : 'subscriber';

        $user_id = wp_insert_user(
            [
                'user_login'   => $email,
                'user_email'   => $email,
                'display_name' => $name,
                'user_pass'    => $password,
                'role'         => $role,
            ]
        );

        if ( is_wp_error( $user_id ) ) {
            $this->redirect_with_args( [ 'vc_customer_error' => 'insert_error' ] );
        }

        if ( $phone ) {
            update_user_meta( $user_id, 'vc_customer_phone', $phone );
        }

        do_action( 'vemcomer/customer_registered', (int) $user_id );

        $this->redirect_with_args( [ 'vc_customer_submitted' => '1' ] );
    }

    private function get_feedback_box( string $prefix ): string {
        $success_key = $prefix . '_submitted';
        $error_key   = $prefix . '_error';

        $success = isset( $_GET[ $success_key ] );
        $error   = isset( $_GET[ $error_key ] ) ? sanitize_key( wp_unslash( $_GET[ $error_key ] ) ) : '';

        if ( $success ) {
            $message = ( 'vc_restaurant' === $prefix ) ? esc_html__( 'Recebemos os dados e entraremos em contato em breve.', 'vemcomer' ) : esc_html__( 'Conta criada! Verifique seu e-mail para fazer login.', 'vemcomer' );
            return '<div class="vc-alert vc-alert--success">' . $message . '</div>';
        }

        if ( $error ) {
            $messages = ( 'vc_restaurant' === $prefix ) ? $this->restaurant_error_messages() : $this->customer_error_messages();
            $text     = $messages[ $error ] ?? esc_html__( 'Ocorreu um erro ao enviar o formulário. Tente novamente.', 'vemcomer' );
            return '<div class="vc-alert vc-alert--error">' . esc_html( $text ) . '</div>';
        }

        return '';
    }

    private function restaurant_error_messages(): array {
        return [
            'missing_name'                        => __( 'Informe o nome do restaurante.', 'vemcomer' ),
            'missing_cnpj'                        => __( 'Informe o CNPJ do restaurante.', 'vemcomer' ),
            'invalid_cnpj'                        => __( 'CNPJ inválido.', 'vemcomer' ),
            'vc_restaurant_cnpj_empty'            => __( 'Informe o CNPJ do restaurante.', 'vemcomer' ),
            'vc_restaurant_cnpj_length'           => __( 'O CNPJ deve conter 14 dígitos.', 'vemcomer' ),
            'vc_restaurant_cnpj_repeated'         => __( 'CNPJ inválido: não pode conter todos os dígitos iguais.', 'vemcomer' ),
            'vc_restaurant_cnpj_dv'               => __( 'CNPJ inválido: dígitos verificadores não conferem.', 'vemcomer' ),
            'vc_restaurant_cnpj_remote_unavailable' => __( 'Validação externa indisponível no momento.', 'vemcomer' ),
            'vc_restaurant_cnpj_remote_error'     => __( 'Não foi possível consultar a ReceitaWS.', 'vemcomer' ),
            'vc_restaurant_cnpj_remote_invalid'   => __( 'Resposta inválida da ReceitaWS.', 'vemcomer' ),
            'vc_restaurant_cnpj_remote_not_found' => __( 'CNPJ não encontrado na base da Receita.', 'vemcomer' ),
            'insert_error'                        => __( 'Não foi possível salvar o restaurante. Tente novamente.', 'vemcomer' ),
        ];
    }

    private function customer_error_messages(): array {
        return [
            'missing_name'      => __( 'Informe seu nome completo.', 'vemcomer' ),
            'invalid_email'     => __( 'Informe um e-mail válido.', 'vemcomer' ),
            'email_exists'      => __( 'Já existe uma conta com este e-mail.', 'vemcomer' ),
            'password_short'    => __( 'A senha precisa ter ao menos 6 caracteres.', 'vemcomer' ),
            'password_mismatch' => __( 'As senhas digitadas não conferem.', 'vemcomer' ),
            'insert_error'      => __( 'Não foi possível criar sua conta. Tente novamente.', 'vemcomer' ),
        ];
    }

    private function redirect_with_args( array $args ): void {
        $url = wp_get_referer();
        if ( ! $url ) {
            $url = home_url( '/' );
        }

        $url = add_query_arg( $args, $url );
        wp_safe_redirect( $url );
        exit;
    }
}
