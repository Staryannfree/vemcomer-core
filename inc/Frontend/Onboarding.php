<?php
/**
 * Sistema de Onboarding para Donos de Restaurantes
 * 
 * Guia os novos usuários através dos primeiros passos:
 * 1. Completar dados do restaurante
 * 2. Adicionar itens ao cardápio
 * 3. Configurar horários e delivery
 * 4. Visualizar página pública
 *
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Utils\Restaurant_Helper;
use WP_Post;
use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Onboarding {
    private const META_KEY_COMPLETED = 'vc_onboarding_completed';
    private const META_KEY_STEPS = 'vc_onboarding_steps';
    private const META_KEY_DISMISSED = 'vc_onboarding_dismissed';

    /**
     * Steps do onboarding
     */
    private function get_steps(): array {
        return [
            'welcome' => [
                'title' => __( 'Bem-vindo ao VemComer!', 'vemcomer' ),
                'description' => __( 'Vamos configurar seu restaurante em poucos passos.', 'vemcomer' ),
                'action' => null,
                'check_complete' => null, // Sempre disponível
            ],
            'complete_profile' => [
                'title' => __( 'Complete seu perfil', 'vemcomer' ),
                'description' => __( 'Adicione informações importantes como horários, telefone e endereço.', 'vemcomer' ),
                'action' => 'edit_restaurant',
                'check_complete' => [ $this, 'check_profile_complete' ],
            ],
            'add_menu_items' => [
                'title' => __( 'Adicione itens ao cardápio', 'vemcomer' ),
                'description' => __( 'Crie pelo menos 3 itens para começar a receber pedidos.', 'vemcomer' ),
                'action' => 'manage_menu',
                'check_complete' => [ $this, 'check_menu_items_count' ],
            ],
            'configure_delivery' => [
                'title' => __( 'Configure delivery', 'vemcomer' ),
                'description' => __( 'Defina se oferece delivery e valores de entrega.', 'vemcomer' ),
                'action' => 'edit_restaurant',
                'check_complete' => [ $this, 'check_delivery_configured' ],
            ],
            'view_public_page' => [
                'title' => __( 'Veja sua página pública', 'vemcomer' ),
                'description' => __( 'Confira como os clientes veem seu restaurante.', 'vemcomer' ),
                'action' => 'view_public',
                'check_complete' => null, // Completo ao clicar
            ],
        ];
    }

    public function init(): void {
        add_action( 'wp_ajax_vc_onboarding_complete_step', [ $this, 'handle_complete_step' ] );
        add_action( 'wp_ajax_vc_onboarding_dismiss', [ $this, 'handle_dismiss' ] );
        add_action( 'wp_ajax_vc_onboarding_reset', [ $this, 'handle_reset' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enfileira assets do onboarding
     */
    public function enqueue_assets(): void {
        // Só enfileira se estiver na página do painel
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'lojista', $user->roles, true ) ) {
            return;
        }

        wp_enqueue_style( 'vemcomer-onboarding' );
        wp_enqueue_script( 'vemcomer-onboarding' );

        // Localiza script com nonce
        wp_localize_script( 'vemcomer-onboarding', 'vemcomerOnboarding', [
            'nonce' => wp_create_nonce( 'vc_onboarding' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /**
     * Verifica se o onboarding deve ser exibido
     * Método público para permitir verificação externa
     */
    public function should_show( WP_User $user, ?WP_Post $restaurant ): bool {
        if ( ! $restaurant ) {
            return false;
        }

        // Se já completou, não mostra
        if ( $this->is_completed( $user->ID ) ) {
            return false;
        }

        // Se foi dispensado, não mostra (mas pode ser resetado)
        if ( $this->is_dismissed( $user->ID ) ) {
            return false;
        }

        return true;
    }

    /**
     * Renderiza o componente de onboarding
     *
     * @param WP_User $user Usuário logado.
     * @param WP_Post $restaurant Restaurante do usuário.
     * @param bool $auto_show Se true, exibe automaticamente. Se false, fica oculto e pode ser aberto via botão.
     * @return string HTML do onboarding.
     */
    public function render( WP_User $user, WP_Post $restaurant, bool $auto_show = true ): string {
        if ( ! $this->should_show( $user, $restaurant ) ) {
            return '';
        }

        $steps = $this->get_steps();
        $completed_steps = $this->get_completed_steps( $user->ID );
        $current_step = $this->get_current_step( $user->ID, $steps, $completed_steps );

        $progress = $this->calculate_progress( $steps, $completed_steps, $restaurant );

        ob_start();
        ?>
        <div class="vc-onboarding <?php echo $auto_show ? '' : 'vc-onboarding--hidden'; ?>" data-user-id="<?php echo esc_attr( (string) $user->ID ); ?>">
            <div class="vc-onboarding__overlay"></div>
            <div class="vc-onboarding__modal">
                <div class="vc-onboarding__header">
                    <h2 class="vc-onboarding__title"><?php echo esc_html( $steps[ $current_step ]['title'] ); ?></h2>
                    <button class="vc-onboarding__close" aria-label="<?php echo esc_attr__( 'Fechar', 'vemcomer' ); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="vc-onboarding__progress">
                    <div class="vc-onboarding__progress-bar" style="width: <?php echo esc_attr( (string) $progress ); ?>%"></div>
                    <span class="vc-onboarding__progress-text">
                        <?php echo esc_html( sprintf( __( '%d%% completo', 'vemcomer' ), $progress ) ); ?>
                    </span>
                </div>

                <div class="vc-onboarding__content">
                    <p class="vc-onboarding__description">
                        <?php echo esc_html( $steps[ $current_step ]['description'] ); ?>
                    </p>

                    <?php if ( 'welcome' === $current_step ) : ?>
                        <div class="vc-onboarding__welcome">
                            <p><?php echo esc_html__( 'Vamos começar configurando seu restaurante para receber pedidos!', 'vemcomer' ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ( $steps[ $current_step ]['action'] ) : ?>
                        <div class="vc-onboarding__actions">
                            <?php echo $this->render_action_button( $steps[ $current_step ]['action'], $restaurant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="vc-onboarding__steps">
                    <ul class="vc-onboarding__steps-list">
                        <?php foreach ( $steps as $step_key => $step ) : ?>
                            <?php
                            $is_completed = in_array( $step_key, $completed_steps, true );
                            $is_current = $step_key === $current_step;
                            ?>
                            <li class="vc-onboarding__step <?php echo $is_completed ? 'vc-onboarding__step--completed' : ''; ?> <?php echo $is_current ? 'vc-onboarding__step--current' : ''; ?>" data-step-key="<?php echo esc_attr( $step_key ); ?>">
                                <span class="vc-onboarding__step-icon">
                                    <?php if ( $is_completed ) : ?>
                                        ✓
                                    <?php elseif ( $is_current ) : ?>
                                        →
                                    <?php else : ?>
                                        ○
                                    <?php endif; ?>
                                </span>
                                <span class="vc-onboarding__step-title"><?php echo esc_html( $step['title'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="vc-onboarding__footer">
                    <button class="vc-onboarding__skip" data-action="dismiss">
                        <?php echo esc_html__( 'Pular por enquanto', 'vemcomer' ); ?>
                    </button>
                    <?php if ( 'welcome' !== $current_step ) : ?>
                        <button class="vc-onboarding__next" data-step="<?php echo esc_attr( $current_step ); ?>" data-action="complete" type="button">
                            <?php echo esc_html__( 'Concluído', 'vemcomer' ); ?>
                        </button>
                    <?php else : ?>
                        <button class="vc-onboarding__next" data-step="<?php echo esc_attr( $current_step ); ?>" data-action="next" type="button">
                            <?php echo esc_html__( 'Começar', 'vemcomer' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Renderiza botão de ação baseado no tipo
     */
    private function render_action_button( string $action, WP_Post $restaurant ): string {
        $panel = new RestaurantPanel();
        
        switch ( $action ) {
            case 'edit_restaurant':
                $edit_url = $panel->edit_url( $restaurant );
                if ( empty( $edit_url ) ) {
                    return '';
                }
                return sprintf(
                    '<a href="%s" class="vc-btn vc-btn--primary" target="_blank">%s</a>',
                    esc_url( $edit_url ),
                    esc_html__( 'Editar restaurante', 'vemcomer' )
                );

            case 'manage_menu':
                $menu_url = $panel->menu_admin_url( $restaurant );
                if ( empty( $menu_url ) ) {
                    return '';
                }
                return sprintf(
                    '<a href="%s" class="vc-btn vc-btn--primary" target="_blank">%s</a>',
                    esc_url( $menu_url ),
                    esc_html__( 'Gerenciar cardápio', 'vemcomer' )
                );

            case 'view_public':
                $public_url = get_permalink( $restaurant );
                if ( ! $public_url ) {
                    return '';
                }
                return sprintf(
                    '<a href="%s" class="vc-btn vc-btn--primary" target="_blank">%s</a>',
                    esc_url( $public_url ),
                    esc_html__( 'Ver página pública', 'vemcomer' )
                );

            default:
                return '';
        }
    }

    /**
     * Calcula o progresso do onboarding
     */
    private function calculate_progress( array $steps, array $completed_steps, WP_Post $restaurant ): int {
        $total = count( $steps );
        $completed = count( $completed_steps );

        // Verifica steps que podem ser completados automaticamente
        foreach ( $steps as $step_key => $step ) {
            if ( in_array( $step_key, $completed_steps, true ) ) {
                continue;
            }

            if ( $step['check_complete'] && is_callable( $step['check_complete'] ) ) {
                if ( call_user_func( $step['check_complete'], $restaurant ) ) {
                    $completed++;
                }
            }
        }

        return (int) round( ( $completed / $total ) * 100 );
    }

    /**
     * Obtém o step atual
     */
    private function get_current_step( int $user_id, array $steps, array $completed_steps ): string {
        foreach ( $steps as $step_key => $step ) {
            if ( ! in_array( $step_key, $completed_steps, true ) ) {
                return $step_key;
            }
        }

        // Se todos estão completos, retorna o último
        return array_key_last( $steps );
    }

    /**
     * Obtém steps completados
     */
    private function get_completed_steps( int $user_id ): array {
        $steps = get_user_meta( $user_id, self::META_KEY_STEPS, true );
        return is_array( $steps ) ? $steps : [];
    }

    /**
     * Verifica se está completo
     */
    private function is_completed( int $user_id ): bool {
        return (bool) get_user_meta( $user_id, self::META_KEY_COMPLETED, true );
    }

    /**
     * Verifica se foi dispensado
     */
    private function is_dismissed( int $user_id ): bool {
        return (bool) get_user_meta( $user_id, self::META_KEY_DISMISSED, true );
    }

    /**
     * Verifica se o perfil está completo
     */
    public function check_profile_complete( WP_Post $restaurant ): bool {
        $required_fields = [
            'vc_restaurant_whatsapp',
            'vc_restaurant_address',
            'vc_restaurant_open_hours',
        ];

        foreach ( $required_fields as $field ) {
            $value = get_post_meta( $restaurant->ID, $field, true );
            if ( empty( $value ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica se há itens no cardápio
     */
    public function check_menu_items_count( WP_Post $restaurant ): bool {
        $count = (int) get_posts([
            'post_type' => 'vc_menu_item',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_vc_restaurant_id',
                    'value' => $restaurant->ID,
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return $count >= 3;
    }

    /**
     * Verifica se delivery está configurado
     */
    public function check_delivery_configured( WP_Post $restaurant ): bool {
        $delivery = get_post_meta( $restaurant->ID, 'vc_restaurant_delivery', true );
        return ! empty( $delivery );
    }

    /**
     * Handler AJAX para completar step
     */
    public function handle_complete_step(): void {
        check_ajax_referer( 'vc_onboarding', 'nonce', false );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Não autenticado', 'vemcomer' ) ] );
        }

        $user_id = get_current_user_id();
        $step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : '';

        if ( empty( $step ) ) {
            wp_send_json_error( [ 'message' => __( 'Step inválido', 'vemcomer' ) ] );
        }

        $completed_steps = $this->get_completed_steps( $user_id );
        if ( ! in_array( $step, $completed_steps, true ) ) {
            $completed_steps[] = $step;
            update_user_meta( $user_id, self::META_KEY_STEPS, $completed_steps );
        }

        $steps = $this->get_steps();
        $user = get_user_by( 'id', $user_id );
        $restaurant = $user ? Restaurant_Helper::get_restaurant_for_user( $user->ID ) : null;
        
        $all_completed = count( $completed_steps ) >= count( $steps );

        if ( $all_completed ) {
            update_user_meta( $user_id, self::META_KEY_COMPLETED, true );
        }

        $progress = $restaurant ? $this->calculate_progress( $steps, $completed_steps, $restaurant ) : 0;

        wp_send_json_success( [
            'completed' => $all_completed,
            'progress' => $progress,
        ] );
    }

    /**
     * Handler AJAX para dispensar onboarding
     */
    public function handle_dismiss(): void {
        check_ajax_referer( 'vc_onboarding', 'nonce', false );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Não autenticado', 'vemcomer' ) ] );
        }

        $user_id = get_current_user_id();
        update_user_meta( $user_id, self::META_KEY_DISMISSED, true );

        wp_send_json_success();
    }

    /**
     * Handler AJAX para resetar onboarding
     */
    public function handle_reset(): void {
        check_ajax_referer( 'vc_onboarding', 'nonce', false );

        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Sem permissão', 'vemcomer' ) ] );
        }

        $user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'ID de usuário inválido', 'vemcomer' ) ] );
        }

        delete_user_meta( $user_id, self::META_KEY_COMPLETED );
        delete_user_meta( $user_id, self::META_KEY_STEPS );
        delete_user_meta( $user_id, self::META_KEY_DISMISSED );

        wp_send_json_success();
    }

}

