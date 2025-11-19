<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class VC_Restaurants_Table extends WP_List_Table {
    private const POST_TYPE = 'vc_restaurant';

    private const META_DELIVERY = [ 'vc_restaurant_delivery', '_vc_has_delivery' ];
    private const META_IS_OPEN  = [ 'vc_restaurant_is_open', '_vc_is_open' ];
    private const META_HOURS    = [ 'vc_restaurant_open_hours', '_vc_open_hours' ];

    private ?WP_Query $query = null;

    private array $post_statuses = [];

    private bool $approval_mode = false;

    public function __construct( array $args = [] ) {
        parent::__construct(
            [
                'singular' => 'vc_restaurant',
                'plural'   => 'vc_restaurants',
                'ajax'     => false,
            ]
        );

        $this->post_statuses = $args['post_statuses'] ?? array_keys( get_post_stati( [ 'internal' => false ] ) );
        $this->approval_mode = (bool) ( $args['approval_mode'] ?? false );
    }

    public function get_columns(): array {
        return [
            'cb'         => '<input type="checkbox" />',
            'title'      => __( 'Restaurante', 'vemcomer' ),
            'status'     => __( 'Status', 'vemcomer' ),
            'location'   => __( 'Localização', 'vemcomer' ),
            'cuisine'    => __( 'Cozinha', 'vemcomer' ),
            'delivery'   => __( 'Delivery', 'vemcomer' ),
            'is_open'    => __( 'Aberto agora', 'vemcomer' ),
            'open_hours' => __( 'Horários', 'vemcomer' ),
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'title'  => [ 'title', true ],
            'status' => [ 'post_status', false ],
        ];
    }

    protected function get_bulk_actions(): array {
        $actions = [];

        if ( $this->approval_mode ) {
            $actions['approve'] = __( 'Aprovar restaurantes', 'vemcomer' );
        }

        $actions['enable_delivery']  = __( 'Ativar delivery', 'vemcomer' );
        $actions['disable_delivery'] = __( 'Desativar delivery', 'vemcomer' );

        return $actions;
    }

    public function column_cb( $item ): string {
        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$d" />', esc_attr( $this->_args['singular'] ), (int) $item->ID );
    }

    public function column_title( $item ): string {
        $title = sprintf( '<strong>%s</strong>', esc_html( get_the_title( $item ) ?: __( '(Sem título)', 'vemcomer' ) ) );
        $actions = [];
        if ( current_user_can( 'edit_post', $item->ID ) ) {
            $actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $item->ID ) ), esc_html__( 'Editar', 'vemcomer' ) );
        }
        if ( current_user_can( 'delete_post', $item->ID ) ) {
            $actions['trash'] = sprintf( '<a href="%s">%s</a>', esc_url( get_delete_post_link( $item->ID ) ), esc_html__( 'Lixeira', 'vemcomer' ) );
        }
        if ( $this->approval_mode && 'publish' !== get_post_status( $item ) && current_user_can( 'publish_post', $item->ID ) ) {
            $page = sanitize_key( $_REQUEST['page'] ?? 'vemcomer-restaurants' );
            $approve_url = wp_nonce_url(
                add_query_arg(
                    [
                        'page'          => $page,
                        'action'        => 'approve',
                        'vc_restaurant' => $item->ID,
                    ]
                ),
                'vc_approve_restaurant'
            );
            $actions['approve'] = sprintf( '<a href="%s">%s</a>', esc_url( $approve_url ), esc_html__( 'Aprovar', 'vemcomer' ) );
        }
        if ( get_post_status( $item ) === 'publish' ) {
            $actions['view'] = sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( get_permalink( $item ) ), esc_html__( 'Ver', 'vemcomer' ) );
        }
        return $title . $this->row_actions( $actions );
    }

    public function column_status( $item ): string {
        $status = get_post_status_object( $item->post_status );
        $label  = $status ? esc_html( $status->label ) : esc_html( ucfirst( $item->post_status ) );

        return sprintf( '%s %s', $this->get_status_icon( $item->post_status ), $label );
    }

    public function column_location( $item ): string {
        return esc_html( $this->get_terms_list( $item->ID, 'vc_location' ) );
    }

    public function column_cuisine( $item ): string {
        return esc_html( $this->get_terms_list( $item->ID, 'vc_cuisine' ) );
    }

    public function column_delivery( $item ): string {
        return $this->format_bool( $this->get_bool_meta( $item->ID, self::META_DELIVERY ) );
    }

    public function column_is_open( $item ): string {
        return $this->format_bool( $this->get_bool_meta( $item->ID, self::META_IS_OPEN ) );
    }

    public function column_open_hours( $item ): string {
        $value = $this->get_meta_value( $item->ID, self::META_HOURS );
        return $value ? esc_html( $value ) : '—';
    }

    public function column_default( $item, $column_name ): string {
        return isset( $item->{$column_name} ) ? esc_html( (string) $item->{$column_name} ) : '—';
    }

    public function prepare_items(): void {
        $this->process_bulk_action();

        $per_page = $this->get_items_per_page( 'vc_restaurants_per_page', 20 );
        $paged    = max( 1, (int) ( $_REQUEST['paged'] ?? 1 ) );

        $args = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => $this->post_statuses,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        $order   = isset( $_REQUEST['order'] ) && 'desc' === strtolower( (string) $_REQUEST['order'] ) ? 'DESC' : 'ASC';
        $orderby = sanitize_key( $_REQUEST['orderby'] ?? 'date' );
        $args['orderby'] = in_array( $orderby, [ 'title', 'post_status', 'date' ], true ) ? $orderby : 'date';
        $args['order']   = $order;

        $tax_query = $this->build_tax_query();
        if ( $tax_query ) {
            $args['tax_query'] = $tax_query;
        }

        $meta_query = $this->build_meta_query();
        if ( $meta_query ) {
            $args['meta_query'] = $meta_query;
        }

        $this->query = new WP_Query( $args );
        $this->items = $this->query->posts;

        $columns               = $this->get_columns();
        $hidden                = [];
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable, 'title' ];

        $this->set_pagination_args(
            [
                'total_items' => (int) $this->query->found_posts,
                'per_page'    => $per_page,
                'total_pages' => (int) $this->query->max_num_pages,
            ]
        );
    }

    protected function extra_tablenav( $which ): void {
        if ( 'top' !== $which ) {
            return;
        }
        echo '<div class="alignleft actions">';
        $this->render_taxonomy_dropdown( 'vc_cuisine', 'vc_cuisine', __( 'Todos os tipos de cozinha', 'vemcomer' ), $this->get_filter_value( 'cuisine' ) );
        $this->render_taxonomy_dropdown( 'vc_location', 'vc_location', __( 'Todas as localizações', 'vemcomer' ), $this->get_filter_value( 'location' ) );
        $this->render_meta_dropdown( 'delivery', __( 'Delivery', 'vemcomer' ), [ '' => __( 'Delivery (todos)', 'vemcomer' ), '1' => __( 'Com delivery', 'vemcomer' ), '0' => __( 'Sem delivery', 'vemcomer' ) ] );
        $this->render_meta_dropdown( 'is_open', __( 'Aberto agora', 'vemcomer' ), [ '' => __( 'Aberto/Fechado (todos)', 'vemcomer' ), '1' => __( 'Apenas abertos', 'vemcomer' ), '0' => __( 'Apenas fechados', 'vemcomer' ) ] );
        submit_button( __( 'Filtrar', 'vemcomer' ), '', 'filter_action', false );
        if ( $this->has_active_filters() ) {
            printf( '<a href="%s" class="button">%s</a>', esc_url( remove_query_arg( [ 'vc_cuisine', 'vc_location', 'vc_delivery', 'vc_is_open', 'orderby', 'order' ] ) ), esc_html__( 'Limpar filtros', 'vemcomer' ) );
        }
        echo '</div>';
    }

    private function build_tax_query(): array {
        $query = [];
        $cuisine  = $this->get_filter_value( 'cuisine' );
        $location = $this->get_filter_value( 'location' );

        if ( $cuisine ) {
            $query[] = [
                'taxonomy' => 'vc_cuisine',
                'field'    => 'term_id',
                'terms'    => [ $cuisine ],
            ];
        }

        if ( $location ) {
            $query[] = [
                'taxonomy' => 'vc_location',
                'field'    => 'term_id',
                'terms'    => [ $location ],
            ];
        }

        return $query;
    }

    private function build_meta_query(): array {
        $meta_query = [];
        $delivery   = $this->get_filter_value( 'delivery' );
        $is_open    = $this->get_filter_value( 'is_open' );

        if ( '' !== $delivery && null !== $delivery ) {
            $meta_query[] = $this->build_boolean_meta_clause( self::META_DELIVERY, $delivery );
        }

        if ( '' !== $is_open && null !== $is_open ) {
            $meta_query[] = $this->build_boolean_meta_clause( self::META_IS_OPEN, $is_open );
        }

        if ( $meta_query ) {
            $meta_query['relation'] = 'AND';
        }

        return $meta_query;
    }

    private function build_boolean_meta_clause( array $keys, string $value ): array {
        $value = '1' === $value ? '1' : '0';
        $clause = [ 'relation' => 'OR' ];
        foreach ( $keys as $key ) {
            $clause[] = [ 'key' => $key, 'value' => $value, 'compare' => '=' ];
        }
        return $clause;
    }

    private function get_terms_list( int $post_id, string $taxonomy ): string {
        $terms = get_the_terms( $post_id, $taxonomy );
        if ( is_wp_error( $terms ) || ! $terms ) {
            return '—';
        }
        return implode( ', ', wp_list_pluck( $terms, 'name' ) );
    }

    private function get_bool_meta( int $post_id, array $keys ): bool {
        foreach ( $keys as $key ) {
            $value = get_post_meta( $post_id, $key, true );
            if ( '' !== $value ) {
                return (bool) (int) $value;
            }
        }
        return false;
    }

    private function get_meta_value( int $post_id, array $keys ): string {
        foreach ( $keys as $key ) {
            $value = get_post_meta( $post_id, $key, true );
            if ( $value ) {
                return (string) $value;
            }
        }
        return '';
    }

    private function get_status_icon( string $post_status ): string {
        if ( 'publish' === $post_status ) {
            return '<span class="dashicons dashicons-yes-alt" style="color:#2ecc71;" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Aprovado', 'vemcomer' ) . '</span>';
        }

        return '<span class="dashicons dashicons-no" style="color:#e74c3c;" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Pendente', 'vemcomer' ) . '</span>';
    }

    private function format_bool( bool $value ): string {
        return $value ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' );
    }

    private function get_filter_value( string $key ) {
        if ( in_array( $key, [ 'cuisine', 'location' ], true ) ) {
            $param = 'vc_' . $key;
            return isset( $_GET[ $param ] ) ? absint( $_GET[ $param ] ) : null;
        }

        if ( 'delivery' === $key ) {
            return isset( $_GET['vc_delivery'] ) && '' !== $_GET['vc_delivery'] ? (string) $_GET['vc_delivery'] : null;
        }

        if ( 'is_open' === $key ) {
            return isset( $_GET['vc_is_open'] ) && '' !== $_GET['vc_is_open'] ? (string) $_GET['vc_is_open'] : null;
        }

        return null;
    }

    private function render_taxonomy_dropdown( string $taxonomy, string $field_name, string $placeholder, ?int $selected ): void {
        wp_dropdown_categories(
            [
                'show_option_all' => $placeholder,
                'taxonomy'        => $taxonomy,
                'name'            => $field_name,
                'orderby'         => 'name',
                'selected'        => $selected,
                'hide_empty'      => false,
            ]
        );
    }

    private function render_meta_dropdown( string $key, string $label, array $options ): void {
        $value = '';
        if ( 'delivery' === $key ) {
            $value = isset( $_GET['vc_delivery'] ) ? (string) $_GET['vc_delivery'] : '';
            $name  = 'vc_delivery';
        } else {
            $value = isset( $_GET['vc_is_open'] ) ? (string) $_GET['vc_is_open'] : '';
            $name  = 'vc_is_open';
        }
        echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
        echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
        foreach ( $options as $option_value => $option_label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option_value ), selected( (string) $option_value, $value, false ), esc_html( $option_label ) );
        }
        echo '</select>';
    }

    private function has_active_filters(): bool {
        return ( ! empty( $_GET['vc_cuisine'] ?? null ) )
            || ( ! empty( $_GET['vc_location'] ?? null ) )
            || ( isset( $_GET['vc_delivery'] ) && '' !== $_GET['vc_delivery'] )
            || ( isset( $_GET['vc_is_open'] ) && '' !== $_GET['vc_is_open'] );
    }

    protected function process_bulk_action(): void {
        $action = $this->current_action();
        if ( ! $action || ! in_array( $action, [ 'enable_delivery', 'disable_delivery', 'approve' ], true ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_vc_restaurants' ) ) {
            return;
        }

        $nonce = isset( $_REQUEST['_wpnonce'] ) ? (string) $_REQUEST['_wpnonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) && ! wp_verify_nonce( $nonce, 'vc_approve_restaurant' ) ) {
            return;
        }

        $ids = isset( $_REQUEST[ $this->_args['singular'] ] ) ? (array) $_REQUEST[ $this->_args['singular'] ] : [];
        $ids = array_map( 'absint', (array) $ids );
        $ids = array_filter( $ids );
        if ( ! $ids ) {
            return;
        }

        if ( 'approve' === $action ) {
            $this->approve_restaurants( $ids );
            return;
        }

        $this->update_delivery_flag( $ids, 'enable_delivery' === $action ? '1' : '0' );
    }

    private function approve_restaurants( array $ids ): void {
        if ( ! current_user_can( 'publish_vc_restaurants' ) ) {
            return;
        }

        $updated = 0;

        foreach ( $ids as $post_id ) {
            if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
                continue;
            }

            if ( 'publish' === get_post_status( $post_id ) ) {
                continue;
            }

            $result = wp_update_post(
                [
                    'ID'          => $post_id,
                    'post_status' => 'publish',
                ],
                true
            );

            if ( ! is_wp_error( $result ) ) {
                $updated++;
            }
        }

        if ( $updated ) {
            add_settings_error( 'vc_restaurants', 'vc_restaurants_notice', __( 'Restaurantes aprovados.', 'vemcomer' ), 'updated' );
        }
    }

    private function update_delivery_flag( array $ids, string $value ): void {
        $updated = 0;

        foreach ( $ids as $post_id ) {
            if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
                continue;
            }
            foreach ( self::META_DELIVERY as $key ) {
                update_post_meta( $post_id, $key, $value );
            }
            $updated++;
        }

        if ( $updated ) {
            $message = '1' === $value
                ? __( 'Delivery ativado para os restaurantes selecionados.', 'vemcomer' )
                : __( 'Delivery desativado para os restaurantes selecionados.', 'vemcomer' );
            add_settings_error( 'vc_restaurants', 'vc_restaurants_notice', $message, 'updated' );
        }
    }
}
