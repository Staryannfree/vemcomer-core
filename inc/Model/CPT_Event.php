<?php
/**
 * CPT_Event — Custom Post Type "Event" (Eventos Gastronômicos)
 * + Capabilities customizadas e concessão por role (grant_caps).
 * + Integração com sistema de planos/subscription.
 *
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Event {
    public const SLUG = 'vc_event';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
        add_action( 'init', [ $this, 'grant_caps' ], 5 );
        
        // Restringir criação baseado em planos
        add_action( 'save_post_' . self::SLUG, [ $this, 'validate_event_limits' ], 5, 3 );
    }

    private function capabilities(): array {
        return [
            'edit_post'              => 'edit_vc_event',
            'read_post'              => 'read_vc_event',
            'delete_post'            => 'delete_vc_event',
            'edit_posts'             => 'edit_vc_events',
            'edit_others_posts'      => 'edit_others_vc_events',
            'publish_posts'          => 'publish_vc_events',
            'read_private_posts'     => 'read_private_vc_events',
            'delete_posts'           => 'delete_vc_events',
            'delete_private_posts'   => 'delete_private_vc_events',
            'delete_published_posts' => 'delete_published_vc_events',
            'delete_others_posts'    => 'delete_others_vc_events',
            'edit_private_posts'     => 'edit_private_vc_events',
            'edit_published_posts'   => 'edit_published_vc_events',
            'create_posts'           => 'create_vc_events',
        ];
    }

    public function register_cpt(): void {
        $labels = [
            'name'               => __( 'Eventos', 'vemcomer' ),
            'singular_name'      => __( 'Evento', 'vemcomer' ),
            'add_new'            => __( 'Adicionar Novo', 'vemcomer' ),
            'add_new_item'       => __( 'Adicionar Novo Evento', 'vemcomer' ),
            'edit_item'          => __( 'Editar Evento', 'vemcomer' ),
            'new_item'           => __( 'Novo Evento', 'vemcomer' ),
            'view_item'          => __( 'Ver Evento', 'vemcomer' ),
            'search_items'       => __( 'Buscar Eventos', 'vemcomer' ),
            'not_found'          => __( 'Nenhum evento encontrado', 'vemcomer' ),
            'not_found_in_trash' => __( 'Nenhum evento encontrado na lixeira', 'vemcomer' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Será adicionado via submenu
            'show_in_rest'        => true,
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
            'has_archive'         => false,
            'rewrite'             => [ 'slug' => 'evento' ],
            'capability_type'     => [ 'vc_event', 'vc_events' ],
            'map_meta_cap'        => true,
            'capabilities'        => $this->capabilities(),
        ];

        register_post_type( self::SLUG, $args );
    }

    public function register_metaboxes(): void {
        add_meta_box(
            'vc_event_meta',
            __( 'Dados do Evento', 'vemcomer' ),
            [ $this, 'metabox' ],
            self::SLUG,
            'normal',
            'high'
        );
    }

    public function metabox( $post ): void {
        wp_nonce_field( 'vc_event_meta_nonce', 'vc_event_meta_nonce' );

        $restaurant_id = (int) get_post_meta( $post->ID, '_vc_event_restaurant_id', true );
        $event_date = get_post_meta( $post->ID, '_vc_event_date', true );
        $event_time = get_post_meta( $post->ID, '_vc_event_time', true );
        $event_end_time = get_post_meta( $post->ID, '_vc_event_end_time', true );
        $event_location = get_post_meta( $post->ID, '_vc_event_location', true );
        $event_price = get_post_meta( $post->ID, '_vc_event_price', true );
        $event_capacity = get_post_meta( $post->ID, '_vc_event_capacity', true );
        $event_is_featured = (bool) get_post_meta( $post->ID, '_vc_event_is_featured', true );
        $event_status = get_post_meta( $post->ID, '_vc_event_status', true ) ?: 'upcoming';

        // Se for restaurante, só pode criar eventos do próprio restaurante
        $current_user_id = get_current_user_id();
        $is_restaurant_owner = false;
        $user_restaurant_id = 0;

        if ( $current_user_id && ! current_user_can( 'manage_options' ) ) {
            // Buscar restaurante do usuário
            $user_restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $current_user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $user_restaurants ) ) {
                $is_restaurant_owner = true;
                $user_restaurant_id = $user_restaurants[0];
            }
        }

        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <p>
                    <label for="vc_event_restaurant_id">
                        <strong><?php esc_html_e( 'Restaurante', 'vemcomer' ); ?></strong>
                    </label><br />
                    <?php if ( $is_restaurant_owner && ! current_user_can( 'manage_options' ) ) : ?>
                        <?php
                        $restaurant = get_post( $user_restaurant_id );
                        if ( $restaurant ) :
                            ?>
                            <input type="hidden" name="_vc_event_restaurant_id" value="<?php echo esc_attr( $user_restaurant_id ); ?>" />
                            <input type="text" value="<?php echo esc_attr( $restaurant->post_title ); ?>" class="widefat" disabled />
                            <p class="description"><?php esc_html_e( 'Evento será vinculado ao seu restaurante.', 'vemcomer' ); ?></p>
                        <?php else : ?>
                            <p class="description" style="color: #dc3232;">
                                <?php esc_html_e( 'Você precisa ter um restaurante cadastrado para criar eventos.', 'vemcomer' ); ?>
                            </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php
                        $restaurants = get_posts( [
                            'post_type'      => 'vc_restaurant',
                            'posts_per_page' => -1,
                            'post_status'    => 'publish',
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                        ] );
                        ?>
                        <select name="_vc_event_restaurant_id" id="vc_event_restaurant_id" class="widefat" required>
                            <option value=""><?php esc_html_e( 'Selecione um restaurante', 'vemcomer' ); ?></option>
                            <?php foreach ( $restaurants as $restaurant ) : ?>
                                <option value="<?php echo esc_attr( $restaurant->ID ); ?>" <?php selected( $restaurant_id, $restaurant->ID ); ?>>
                                    <?php echo esc_html( $restaurant->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </p>

                <p>
                    <label for="vc_event_date">
                        <strong><?php esc_html_e( 'Data do Evento', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="date" 
                        name="_vc_event_date" 
                        id="vc_event_date" 
                        value="<?php echo esc_attr( $event_date ); ?>" 
                        class="widefat" 
                        required 
                    />
                </p>

                <p>
                    <label for="vc_event_time">
                        <strong><?php esc_html_e( 'Horário de Início', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="time" 
                        name="_vc_event_time" 
                        id="vc_event_time" 
                        value="<?php echo esc_attr( $event_time ); ?>" 
                        class="widefat" 
                        required 
                    />
                </p>

                <p>
                    <label for="vc_event_end_time">
                        <strong><?php esc_html_e( 'Horário de Término', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="time" 
                        name="_vc_event_end_time" 
                        id="vc_event_end_time" 
                        value="<?php echo esc_attr( $event_end_time ); ?>" 
                        class="widefat" 
                    />
                </p>

                <p>
                    <label for="vc_event_location">
                        <strong><?php esc_html_e( 'Local do Evento', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="text" 
                        name="_vc_event_location" 
                        id="vc_event_location" 
                        value="<?php echo esc_attr( $event_location ); ?>" 
                        class="widefat" 
                        placeholder="<?php esc_attr_e( 'Ex: Salão principal, Área externa...', 'vemcomer' ); ?>"
                    />
                </p>
            </div>

            <div>
                <p>
                    <label for="vc_event_price">
                        <strong><?php esc_html_e( 'Preço (opcional)', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="text" 
                        name="_vc_event_price" 
                        id="vc_event_price" 
                        value="<?php echo esc_attr( $event_price ); ?>" 
                        class="widefat" 
                        placeholder="<?php esc_attr_e( 'Ex: R$ 50,00 ou Grátis', 'vemcomer' ); ?>"
                    />
                </p>

                <p>
                    <label for="vc_event_capacity">
                        <strong><?php esc_html_e( 'Capacidade (opcional)', 'vemcomer' ); ?></strong>
                    </label><br />
                    <input 
                        type="number" 
                        name="_vc_event_capacity" 
                        id="vc_event_capacity" 
                        value="<?php echo esc_attr( $event_capacity ); ?>" 
                        class="widefat" 
                        min="1"
                        placeholder="<?php esc_attr_e( 'Número de vagas', 'vemcomer' ); ?>"
                    />
                </p>

                <p>
                    <label for="vc_event_status">
                        <strong><?php esc_html_e( 'Status', 'vemcomer' ); ?></strong>
                    </label><br />
                    <select name="_vc_event_status" id="vc_event_status" class="widefat">
                        <option value="upcoming" <?php selected( $event_status, 'upcoming' ); ?>>
                            <?php esc_html_e( 'Próximo', 'vemcomer' ); ?>
                        </option>
                        <option value="ongoing" <?php selected( $event_status, 'ongoing' ); ?>>
                            <?php esc_html_e( 'Em andamento', 'vemcomer' ); ?>
                        </option>
                        <option value="completed" <?php selected( $event_status, 'completed' ); ?>>
                            <?php esc_html_e( 'Concluído', 'vemcomer' ); ?>
                        </option>
                        <option value="cancelled" <?php selected( $event_status, 'cancelled' ); ?>>
                            <?php esc_html_e( 'Cancelado', 'vemcomer' ); ?>
                        </option>
                    </select>
                </p>

                <p>
                    <label>
                        <input 
                            type="checkbox" 
                            name="_vc_event_is_featured" 
                            value="1" 
                            <?php checked( $event_is_featured, true ); ?>
                        />
                        <strong><?php esc_html_e( 'Destaque na Home', 'vemcomer' ); ?></strong>
                    </label>
                    <br />
                    <span class="description">
                        <?php esc_html_e( 'Marque para exibir este evento na seção de eventos da home.', 'vemcomer' ); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php
    }

    public function save_meta( int $post_id ): void {
        // Verificar nonce
        if ( ! isset( $_POST['vc_event_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vc_event_meta_nonce'] ) ), 'vc_event_meta_nonce' ) ) {
            return;
        }

        // Verificar autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Verificar permissões
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            '_vc_event_restaurant_id' => 'absint',
            '_vc_event_date'          => 'sanitize_text_field',
            '_vc_event_time'          => 'sanitize_text_field',
            '_vc_event_end_time'      => 'sanitize_text_field',
            '_vc_event_location'      => 'sanitize_text_field',
            '_vc_event_price'         => 'sanitize_text_field',
            '_vc_event_capacity'      => 'absint',
            '_vc_event_status'        => 'sanitize_text_field',
        ];

        foreach ( $fields as $key => $sanitize ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = $_POST[ $key ];
                if ( 'absint' === $sanitize ) {
                    $value = absint( $value );
                } else {
                    $value = sanitize_text_field( wp_unslash( (string) $value ) );
                }
                update_post_meta( $post_id, $key, $value );
            }
        }

        // Salvar checkbox
        $is_featured = isset( $_POST['_vc_event_is_featured'] ) && '1' === $_POST['_vc_event_is_featured'];
        update_post_meta( $post_id, '_vc_event_is_featured', $is_featured ? '1' : '' );

        // Se restaurante não foi selecionado e usuário é dono de restaurante, usar restaurante do usuário
        $restaurant_id = (int) get_post_meta( $post_id, '_vc_event_restaurant_id', true );
        if ( ! $restaurant_id && ! current_user_can( 'manage_options' ) ) {
            $current_user_id = get_current_user_id();
            $user_restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $current_user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $user_restaurants ) ) {
                update_post_meta( $post_id, '_vc_event_restaurant_id', $user_restaurants[0] );
            }
        }
    }

    /**
     * Valida limites de eventos baseado no plano do restaurante.
     */
    public function validate_event_limits( int $post_id, \WP_Post $post, bool $update ): void {
        // Apenas para novos eventos (não atualizações)
        if ( $update ) {
            return;
        }

        $restaurant_id = (int) get_post_meta( $post_id, '_vc_event_restaurant_id', true );
        if ( ! $restaurant_id ) {
            return;
        }

        // Verificar limite do plano
        $max_events = \VC\Subscription\Plan_Manager::get_max_events( $restaurant_id );
        if ( $max_events <= 0 ) {
            return; // Ilimitado
        }

        // Contar eventos atuais do restaurante
        $current_count = $this->count_restaurant_events( $restaurant_id );

        if ( $current_count >= $max_events ) {
            // Remover o post que acabou de ser criado
            wp_delete_post( $post_id, true );

            // Adicionar notice de erro
            add_action( 'admin_notices', function() use ( $max_events ) {
                ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html( sprintf( __( 'Limite de eventos atingido! Seu plano permite no máximo %d eventos.', 'vemcomer' ), $max_events ) ); ?></p>
                </div>
                <?php
            } );
        }
    }

    /**
     * Conta eventos de um restaurante.
     */
    private function count_restaurant_events( int $restaurant_id ): int {
        $query = new \WP_Query( [
            'post_type'      => self::SLUG,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => '_vc_event_restaurant_id',
                    'value' => (string) $restaurant_id,
                ],
            ],
        ] );

        return $query->found_posts;
    }

    public function admin_columns( array $columns ): array {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['restaurant'] = __( 'Restaurante', 'vemcomer' );
        $new_columns['event_date'] = __( 'Data do Evento', 'vemcomer' );
        $new_columns['event_status'] = __( 'Status', 'vemcomer' );
        $new_columns['featured'] = __( 'Destaque', 'vemcomer' );
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    public function admin_column_values( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'restaurant':
                $restaurant_id = (int) get_post_meta( $post_id, '_vc_event_restaurant_id', true );
                if ( $restaurant_id ) {
                    $restaurant = get_post( $restaurant_id );
                    if ( $restaurant ) {
                        echo '<a href="' . esc_url( get_edit_post_link( $restaurant_id ) ) . '">' . esc_html( $restaurant->post_title ) . '</a>';
                    } else {
                        echo '<span style="color: #999;">' . esc_html__( 'N/A', 'vemcomer' ) . '</span>';
                    }
                } else {
                    echo '<span style="color: #999;">' . esc_html__( 'N/A', 'vemcomer' ) . '</span>';
                }
                break;

            case 'event_date':
                $date = get_post_meta( $post_id, '_vc_event_date', true );
                $time = get_post_meta( $post_id, '_vc_event_time', true );
                if ( $date ) {
                    $date_obj = \DateTime::createFromFormat( 'Y-m-d', $date );
                    if ( $date_obj ) {
                        echo esc_html( $date_obj->format( 'd/m/Y' ) );
                        if ( $time ) {
                            echo '<br /><small>' . esc_html( $time ) . '</small>';
                        }
                    } else {
                        echo esc_html( $date );
                    }
                } else {
                    echo '<span style="color: #999;">' . esc_html__( 'Não definido', 'vemcomer' ) . '</span>';
                }
                break;

            case 'event_status':
                $status = get_post_meta( $post_id, '_vc_event_status', true ) ?: 'upcoming';
                $status_labels = [
                    'upcoming'  => __( 'Próximo', 'vemcomer' ),
                    'ongoing'   => __( 'Em andamento', 'vemcomer' ),
                    'completed' => __( 'Concluído', 'vemcomer' ),
                    'cancelled' => __( 'Cancelado', 'vemcomer' ),
                ];
                $status_colors = [
                    'upcoming'  => '#2271b1',
                    'ongoing'   => '#00a32a',
                    'completed' => '#646970',
                    'cancelled' => '#d63638',
                ];
                $label = $status_labels[ $status ] ?? $status;
                $color = $status_colors[ $status ] ?? '#646970';
                echo '<span style="color: ' . esc_attr( $color ) . '; font-weight: 600;">' . esc_html( $label ) . '</span>';
                break;

            case 'featured':
                $is_featured = (bool) get_post_meta( $post_id, '_vc_event_is_featured', true );
                if ( $is_featured ) {
                    echo '<span style="color: #f59e0b;">★</span> ' . esc_html__( 'Sim', 'vemcomer' );
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
        }
    }

    public function grant_caps(): void {
        $roles = [ 'administrator', 'editor', 'author' ];
        foreach ( $roles as $role ) {
            $role_obj = get_role( $role );
            if ( ! $role_obj ) {
                continue;
            }
            foreach ( $this->capabilities() as $cap ) {
                $role_obj->add_cap( $cap );
            }
        }
    }
}

