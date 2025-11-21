<?php
/**
 * Metaboxes e salvamento seguro para Restaurantes
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Chaves de meta
const VC_META_RESTAURANT_FIELDS = [
    'cnpj'        => 'vc_restaurant_cnpj',
    'whatsapp'    => 'vc_restaurant_whatsapp',
    'site'        => 'vc_restaurant_site',
    'open_hours'  => 'vc_restaurant_open_hours',
    'delivery'    => 'vc_restaurant_delivery', // bool
    'address'     => 'vc_restaurant_address',
    'lat'         => 'vc_restaurant_lat',
    'lng'         => 'vc_restaurant_lng',
    'access_url'  => 'vc_restaurant_access_url', // token único para acesso ao painel
    // Configuração de frete por distância
    'delivery_radius'      => '_vc_delivery_radius',
    'delivery_price_per_km' => '_vc_delivery_price_per_km',
    'delivery_base_price'  => '_vc_delivery_base_price',
    'delivery_free_above'   => '_vc_delivery_free_above',
    'delivery_min_order'    => '_vc_delivery_min_order',
    'delivery_neighborhoods' => '_vc_delivery_neighborhoods', // JSON
];

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'vc_restaurant_info',
        __( 'Informações do restaurante', 'vemcomer' ),
        'vc_render_restaurant_metabox',
        'vc_restaurant',
        'normal',
        'high'
    );
});

add_action( 'admin_enqueue_scripts', function( $hook ) {
    global $post_type;
    if ( 'vc_restaurant' !== $post_type || ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    wp_add_inline_script( 'jquery', '
        jQuery(document).ready(function($) {
            // Toggle períodos ao habilitar/desabilitar dia
            $(document).on("change", ".vc-schedule-enabled", function() {
                var day = $(this).data("day");
                var periods = $(".vc-schedule-periods[data-day=\"" + day + "\"]");
                if ($(this).is(":checked")) {
                    periods.show();
                } else {
                    periods.hide();
                }
            });

            // Adicionar período
            $(document).on("click", ".vc-add-period", function(e) {
                e.preventDefault();
                var day = $(this).data("day");
                var periodsContainer = $(".vc-schedule-periods[data-day=\"" + day + "\"]");
                var periodCount = periodsContainer.find(".vc-schedule-period").length;
                var newPeriod = $("<div class=\"vc-schedule-period\" style=\"display: flex; align-items: center; gap: 10px; margin-bottom: 8px;\">" +
                    "<input type=\"time\" name=\"vc_schedule[" + day + "][periods][" + periodCount + "][open]\" value=\"09:00\" class=\"small-text\" />" +
                    "<span>—</span>" +
                    "<input type=\"time\" name=\"vc_schedule[" + day + "][periods][" + periodCount + "][close]\" value=\"22:00\" class=\"small-text\" />" +
                    "<button type=\"button\" class=\"button button-small vc-remove-period\">Remover</button>" +
                    "</div>");
                $(this).before(newPeriod);
                periodsContainer.find(".vc-remove-period").show();
            });

            // Remover período
            $(document).on("click", ".vc-remove-period", function(e) {
                e.preventDefault();
                var periodsContainer = $(this).closest(".vc-schedule-periods");
                $(this).closest(".vc-schedule-period").remove();
                var periodCount = periodsContainer.find(".vc-schedule-period").length;
                if (periodCount <= 1) {
                    periodsContainer.find(".vc-remove-period").hide();
                }
            });
        });
    ' );
});

function vc_render_restaurant_metabox( $post ) {
    wp_nonce_field( 'vc_restaurant_meta_nonce', 'vc_restaurant_meta_nonce_field' );

    $values = [];
    foreach ( VC_META_RESTAURANT_FIELDS as $key => $meta_key ) {
        $values[ $key ] = get_post_meta( $post->ID, $meta_key, true );
    }
    ?>
    <table class="form-table">
        <tr>
            <th><label for="vc_restaurant_cnpj"><?php echo esc_html__( 'CNPJ', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_cnpj" name="vc_restaurant_cnpj" class="regular-text" value="<?php echo esc_attr( $values['cnpj'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_whatsapp"><?php echo esc_html__( 'WhatsApp', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_whatsapp" name="vc_restaurant_whatsapp" class="regular-text" placeholder="61981872528" value="<?php echo esc_attr( $values['whatsapp'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_site"><?php echo esc_html__( 'Site', 'vemcomer' ); ?></label></th>
            <td><input type="url" id="vc_restaurant_site" name="vc_restaurant_site" class="regular-text" value="<?php echo esc_attr( $values['site'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label><?php echo esc_html__( 'Horário de funcionamento', 'vemcomer' ); ?></label></th>
            <td>
                <div id="vc-schedule-manager">
                    <?php
                    $schedule_json = get_post_meta( $post->ID, '_vc_restaurant_schedule', true );
                    $schedule = $schedule_json ? json_decode( $schedule_json, true ) : null;
                    if ( ! is_array( $schedule ) ) {
                        $schedule = [];
                    }

                    $days = [
                        'monday'    => __( 'Segunda-feira', 'vemcomer' ),
                        'tuesday'   => __( 'Terça-feira', 'vemcomer' ),
                        'wednesday' => __( 'Quarta-feira', 'vemcomer' ),
                        'thursday'  => __( 'Quinta-feira', 'vemcomer' ),
                        'friday'    => __( 'Sexta-feira', 'vemcomer' ),
                        'saturday'  => __( 'Sábado', 'vemcomer' ),
                        'sunday'    => __( 'Domingo', 'vemcomer' ),
                    ];

                    foreach ( $days as $day_key => $day_label ) :
                        $day_data = $schedule[ $day_key ] ?? [ 'enabled' => false, 'periods' => [ [ 'open' => '09:00', 'close' => '22:00' ] ] ];
                        $enabled = isset( $day_data['enabled'] ) ? (bool) $day_data['enabled'] : false;
                        $periods = isset( $day_data['periods'] ) && is_array( $day_data['periods'] ) ? $day_data['periods'] : [ [ 'open' => '09:00', 'close' => '22:00' ] ];
                        ?>
                        <div class="vc-schedule-day" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <label style="display: flex; align-items: center; margin-bottom: 10px;">
                                <input type="checkbox" name="vc_schedule[<?php echo esc_attr( $day_key ); ?>][enabled]" value="1" <?php checked( $enabled, true ); ?> class="vc-schedule-enabled" data-day="<?php echo esc_attr( $day_key ); ?>" />
                                <strong style="margin-left: 8px;"><?php echo esc_html( $day_label ); ?></strong>
                            </label>
                            <div class="vc-schedule-periods" data-day="<?php echo esc_attr( $day_key ); ?>" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                                <?php foreach ( $periods as $period_index => $period ) : ?>
                                    <div class="vc-schedule-period" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <input type="time" name="vc_schedule[<?php echo esc_attr( $day_key ); ?>][periods][<?php echo esc_attr( $period_index ); ?>][open]" value="<?php echo esc_attr( $period['open'] ?? '09:00' ); ?>" class="small-text" />
                                        <span>—</span>
                                        <input type="time" name="vc_schedule[<?php echo esc_attr( $day_key ); ?>][periods][<?php echo esc_attr( $period_index ); ?>][close]" value="<?php echo esc_attr( $period['close'] ?? '22:00' ); ?>" class="small-text" />
                                        <button type="button" class="button button-small vc-remove-period" style="<?php echo count( $periods ) > 1 ? '' : 'display: none;'; ?>"><?php echo esc_html__( 'Remover', 'vemcomer' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                                <button type="button" class="button button-small vc-add-period" data-day="<?php echo esc_attr( $day_key ); ?>"><?php echo esc_html__( '+ Adicionar período', 'vemcomer' ); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description">
                    <?php echo esc_html__( 'Configure os horários de funcionamento para cada dia da semana. Você pode adicionar múltiplos períodos por dia (ex: 09:00-14:00 e 18:00-22:00).', 'vemcomer' ); ?>
                </p>
                <input type="hidden" id="vc_restaurant_schedule_json" name="vc_restaurant_schedule_json" value="<?php echo esc_attr( $schedule_json ); ?>" />
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_open_hours"><?php echo esc_html__( 'Horário de funcionamento (texto livre - legado)', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_restaurant_open_hours" name="vc_restaurant_open_hours" class="large-text" rows="3" placeholder="<?php echo esc_attr__( 'Ex: Seg-Dom 11:00–23:00', 'vemcomer' ); ?>"><?php echo esc_textarea( $values['open_hours'] ); ?></textarea>
                <p class="description"><?php echo esc_html__( 'Campo legado para compatibilidade. Use a configuração estruturada acima para melhor controle.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_delivery"><?php echo esc_html__( 'Entrega (delivery)', 'vemcomer' ); ?></label></th>
            <td><label><input type="checkbox" id="vc_restaurant_delivery" name="vc_restaurant_delivery" value="1" <?php checked( $values['delivery'], '1' ); ?> /> <?php echo esc_html__( 'Oferece delivery', 'vemcomer' ); ?></label></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_address"><?php echo esc_html__( 'Endereço', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_address" name="vc_restaurant_address" class="regular-text" value="<?php echo esc_attr( $values['address'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_lat"><?php echo esc_html__( 'Latitude', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_lat" name="vc_restaurant_lat" class="regular-text" value="<?php echo esc_attr( $values['lat'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_lng"><?php echo esc_html__( 'Longitude', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_lng" name="vc_restaurant_lng" class="regular-text" value="<?php echo esc_attr( $values['lng'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_access_url"><?php echo esc_html__( 'Token de acesso (access_url)', 'vemcomer' ); ?></label></th>
            <td>
                <?php
                $access_url = $values['access_url'];
                if ( $access_url ) {
                    $validation_url = home_url( '/validar-acesso/?token=' . esc_attr( $access_url ) );
                    echo '<code style="display: block; margin-bottom: 5px;">' . esc_html( $access_url ) . '</code>';
                    echo '<a href="' . esc_url( $validation_url ) . '" target="_blank" class="button button-small">' . esc_html__( 'Ver página de validação', 'vemcomer' ) . '</a>';
                } else {
                    echo '<span class="description">' . esc_html__( 'Token será gerado automaticamente quando o restaurante for aprovado.', 'vemcomer' ) . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <h3 style="margin-top: 30px; margin-bottom: 10px;"><?php echo esc_html__( 'Configuração de Frete por Distância', 'vemcomer' ); ?></h3>
    <p class="description"><?php echo esc_html__( 'Configure o cálculo de frete baseado em distância e bairros.', 'vemcomer' ); ?></p>
    <table class="form-table">
        <tr>
            <th><label for="vc_delivery_radius"><?php echo esc_html__( 'Raio máximo de entrega (km)', 'vemcomer' ); ?></label></th>
            <td>
                <input type="number" id="vc_delivery_radius" name="vc_delivery_radius" class="small-text" step="0.1" min="0" value="<?php echo esc_attr( $values['delivery_radius'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Distância máxima em quilômetros para entrega. Deixe vazio para ilimitado.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_delivery_base_price"><?php echo esc_html__( 'Taxa base de entrega (R$)', 'vemcomer' ); ?></label></th>
            <td>
                <input type="text" id="vc_delivery_base_price" name="vc_delivery_base_price" class="small-text" placeholder="5.00" value="<?php echo esc_attr( $values['delivery_base_price'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Valor fixo adicionado ao cálculo de frete.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_delivery_price_per_km"><?php echo esc_html__( 'Preço por quilômetro (R$)', 'vemcomer' ); ?></label></th>
            <td>
                <input type="text" id="vc_delivery_price_per_km" name="vc_delivery_price_per_km" class="small-text" placeholder="2.50" value="<?php echo esc_attr( $values['delivery_price_per_km'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Valor cobrado por cada quilômetro de distância.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_delivery_free_above"><?php echo esc_html__( 'Frete grátis acima de (R$)', 'vemcomer' ); ?></label></th>
            <td>
                <input type="text" id="vc_delivery_free_above" name="vc_delivery_free_above" class="small-text" placeholder="50.00" value="<?php echo esc_attr( $values['delivery_free_above'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Valor mínimo do pedido para frete grátis. Deixe vazio para não oferecer.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_delivery_min_order"><?php echo esc_html__( 'Pedido mínimo (R$)', 'vemcomer' ); ?></label></th>
            <td>
                <input type="text" id="vc_delivery_min_order" name="vc_delivery_min_order" class="small-text" placeholder="20.00" value="<?php echo esc_attr( $values['delivery_min_order'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Valor mínimo do pedido para permitir entrega.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_delivery_neighborhoods"><?php echo esc_html__( 'Preços por Bairro (JSON)', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_delivery_neighborhoods" name="vc_delivery_neighborhoods" class="large-text code" rows="6" placeholder='{"Centro": {"price": 5.00, "free_above": 50.00}, "Jardim": {"price": 8.00, "free_above": 80.00}}'><?php echo esc_textarea( $values['delivery_neighborhoods'] ?? '' ); ?></textarea>
                <p class="description">
                    <?php echo esc_html__( 'Configure preços especiais por bairro em formato JSON. Exemplo:', 'vemcomer' ); ?>
                    <br />
                    <code>{"Centro": {"price": 5.00, "free_above": 50.00}, "Jardim": {"price": 8.00, "free_above": 80.00}}</code>
                    <br />
                    <?php echo esc_html__( 'Se um bairro estiver configurado, o preço do bairro terá prioridade sobre o cálculo por distância.', 'vemcomer' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_vc_restaurant', function( $post_id ) {
    // Verificações de segurança
    if ( ! isset( $_POST['vc_restaurant_meta_nonce_field'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['vc_restaurant_meta_nonce_field'], 'vc_restaurant_meta_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $errors = new WP_Error();

    $cnpj_input  = sanitize_text_field( $_POST['vc_restaurant_cnpj'] ?? '' );
    $cnpj_digits = preg_replace( '/\D+/', '', $cnpj_input );

    if ( '' === $cnpj_digits ) {
        $errors->add( 'vc_restaurant_cnpj_empty', __( 'Informe o CNPJ do restaurante.', 'vemcomer' ) );
    } else {
        $use_remote   = (bool) apply_filters( 'vc_restaurant_validate_cnpj_remote', false, $post_id );
        $validation   = \VC\Utils\validate_cnpj( $cnpj_digits, $use_remote );

        if ( is_wp_error( $validation ) ) {
            foreach ( $validation->get_error_messages() as $message ) {
                $errors->add( $validation->get_error_code(), $message );
            }
        } else {
            update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['cnpj'], $validation['normalized'] );
        }
    }

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['whatsapp'], sanitize_text_field( $_POST['vc_restaurant_whatsapp'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['site'], esc_url_raw( $_POST['vc_restaurant_site'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['open_hours'], wp_kses_post( $_POST['vc_restaurant_open_hours'] ?? '' ) );

    // Salvar horários estruturados (JSON)
    $schedule_data = isset( $_POST['vc_schedule'] ) && is_array( $_POST['vc_schedule'] ) ? $_POST['vc_schedule'] : [];
    $schedule_clean = [];
    foreach ( $schedule_data as $day => $day_config ) {
        $day = sanitize_key( $day );
        if ( ! in_array( $day, [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ], true ) ) {
            continue;
        }
        $enabled = isset( $day_config['enabled'] ) && '1' === $day_config['enabled'];
        $periods = [];
        if ( $enabled && isset( $day_config['periods'] ) && is_array( $day_config['periods'] ) ) {
            foreach ( $day_config['periods'] as $period ) {
                $open  = isset( $period['open'] ) ? sanitize_text_field( $period['open'] ) : '09:00';
                $close = isset( $period['close'] ) ? sanitize_text_field( $period['close'] ) : '22:00';
                // Validar formato HH:MM
                if ( preg_match( '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $open ) && preg_match( '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $close ) ) {
                    $periods[] = [
                        'open'  => $open,
                        'close' => $close,
                    ];
                }
            }
        }
        // Se habilitado mas sem períodos, adicionar período padrão
        if ( $enabled && empty( $periods ) ) {
            $periods[] = [ 'open' => '09:00', 'close' => '22:00' ];
        }
        $schedule_clean[ $day ] = [
            'enabled' => $enabled,
            'periods' => $periods,
        ];
    }
    update_post_meta( $post_id, '_vc_restaurant_schedule', wp_json_encode( $schedule_clean ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery'], isset( $_POST['vc_restaurant_delivery'] ) ? '1' : '0' );
    $lat = sanitize_text_field( $_POST['vc_restaurant_lat'] ?? '' );
    $lng = sanitize_text_field( $_POST['vc_restaurant_lng'] ?? '' );

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['address'], sanitize_text_field( $_POST['vc_restaurant_address'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['lat'], $lat );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['lng'], $lng );

    // Salvar configurações de frete por distância
    $radius = isset( $_POST['vc_delivery_radius'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_delivery_radius'] ) ) : '';
    if ( $radius !== '' ) {
        $radius_float = (float) str_replace( ',', '.', $radius );
        update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_radius'], $radius_float > 0 ? (string) $radius_float : '' );
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_radius'] );
    }

    $base_price = isset( $_POST['vc_delivery_base_price'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_delivery_base_price'] ) ) : '';
    if ( $base_price !== '' ) {
        $base_price_float = (float) str_replace( ',', '.', $base_price );
        update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_base_price'], $base_price_float >= 0 ? (string) $base_price_float : '0' );
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_base_price'] );
    }

    $price_per_km = isset( $_POST['vc_delivery_price_per_km'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_delivery_price_per_km'] ) ) : '';
    if ( $price_per_km !== '' ) {
        $price_per_km_float = (float) str_replace( ',', '.', $price_per_km );
        update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_price_per_km'], $price_per_km_float >= 0 ? (string) $price_per_km_float : '0' );
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_price_per_km'] );
    }

    $free_above = isset( $_POST['vc_delivery_free_above'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_delivery_free_above'] ) ) : '';
    if ( $free_above !== '' ) {
        $free_above_float = (float) str_replace( ',', '.', $free_above );
        update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_free_above'], $free_above_float > 0 ? (string) $free_above_float : '' );
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_free_above'] );
    }

    $min_order = isset( $_POST['vc_delivery_min_order'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_delivery_min_order'] ) ) : '';
    if ( $min_order !== '' ) {
        $min_order_float = (float) str_replace( ',', '.', $min_order );
        update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_min_order'], $min_order_float > 0 ? (string) $min_order_float : '' );
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_min_order'] );
    }

    // Salvar preços por bairro (JSON)
    $neighborhoods = isset( $_POST['vc_delivery_neighborhoods'] ) ? wp_unslash( $_POST['vc_delivery_neighborhoods'] ) : '';
    if ( $neighborhoods !== '' ) {
        // Validar JSON
        $neighborhoods_decoded = json_decode( $neighborhoods, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $neighborhoods_decoded ) ) {
            // Sanitizar dados do JSON
            $sanitized = [];
            foreach ( $neighborhoods_decoded as $neighborhood => $config ) {
                $neighborhood_clean = sanitize_text_field( $neighborhood );
                if ( isset( $config['price'] ) && isset( $config['free_above'] ) ) {
                    $sanitized[ $neighborhood_clean ] = [
                        'price'     => max( 0.0, (float) $config['price'] ),
                        'free_above' => max( 0.0, (float) $config['free_above'] ),
                    ];
                }
            }
            update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_neighborhoods'], wp_json_encode( $sanitized ) );
        } else {
            // JSON inválido, não salvar
            $errors->add( 'vc_delivery_neighborhoods_invalid', __( 'JSON de preços por bairro inválido. Verifique a sintaxe.', 'vemcomer' ) );
        }
    } else {
        delete_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_neighborhoods'] );
    }

    if ( $errors->has_errors() ) {
        vc_restaurant_store_errors( $errors );
    }
});

function vc_restaurant_store_errors( WP_Error $errors ): void {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    set_transient( 'vc_restaurant_meta_errors_' . $user_id, $errors->get_error_messages(), MINUTE_IN_SECONDS );
}

add_action( 'admin_notices', function() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    $messages = get_transient( 'vc_restaurant_meta_errors_' . $user_id );
    if ( empty( $messages ) ) {
        return;
    }

    delete_transient( 'vc_restaurant_meta_errors_' . $user_id );

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ( isset( $screen->post_type ) && 'vc_restaurant' !== $screen->post_type ) ) {
        return;
    }

    echo '<div class="notice notice-error">';
    foreach ( $messages as $message ) {
        echo '<p>' . esc_html( $message ) . '</p>';
    }
    echo '</div>';
});
