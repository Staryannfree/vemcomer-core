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
    'orders_count' => 'vc_restaurant_orders_count',
    'delivery_eta' => 'vc_restaurant_delivery_eta',
    'delivery_fee' => 'vc_restaurant_delivery_fee',
    'delivery_type' => 'vc_restaurant_delivery_type',
    'banners'      => 'vc_restaurant_banners',
    'highlight_tags' => 'vc_restaurant_highlight_tags',
    'menu_filters' => 'vc_restaurant_menu_filters',
    'reservation_enabled' => 'vc_restaurant_reservation_enabled',
    'reservation_link'    => 'vc_restaurant_reservation_link',
    'reservation_phone'   => 'vc_restaurant_reservation_phone',
    'reservation_notes'   => 'vc_restaurant_reservation_notes',
    'payment_methods' => 'vc_restaurant_payment_methods',
    'instagram'      => 'vc_restaurant_instagram',
    'facilities'     => 'vc_restaurant_facilities',
    'observations'   => 'vc_restaurant_observations',
    'faq'            => 'vc_restaurant_faq',
    // Configuração de frete por distância
    'delivery_radius'      => '_vc_delivery_radius',
    'delivery_price_per_km' => '_vc_delivery_price_per_km',
    'delivery_base_price'  => '_vc_delivery_base_price',
    'delivery_free_above'   => '_vc_delivery_free_above',
    'delivery_min_order'    => '_vc_delivery_min_order',
    'delivery_neighborhoods' => '_vc_delivery_neighborhoods', // JSON
    // Campos adicionais de marca e plano
    'logo'          => 'vc_restaurant_logo',
    'cover'         => 'vc_restaurant_cover',
    'featured_badge' => 'vc_restaurant_featured_badge',
    'plan_name'     => 'vc_restaurant_plan_name',
    'plan_limit'    => 'vc_restaurant_plan_limit',
    'plan_used'     => 'vc_restaurant_plan_used',
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

            // Adicionar feriado
            $(document).on("click", ".vc-add-holiday", function(e) {
                e.preventDefault();
                var newHoliday = $("<div class=\"vc-holiday-item\" style=\"display: flex; align-items: center; gap: 10px; margin-bottom: 8px;\">" +
                    "<input type=\"date\" name=\"vc_holidays[]\" value=\"\" class=\"regular-text\" />" +
                    "<button type=\"button\" class=\"button button-small vc-remove-holiday\">Remover</button>" +
                    "</div>");
                $("#vc-holidays-list").append(newHoliday);
            });

            // Remover feriado
            $(document).on("click", ".vc-remove-holiday", function(e) {
                e.preventDefault();
                $(this).closest(".vc-holiday-item").remove();
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
            <th><label for="vc_restaurant_logo"><?php echo esc_html__( 'Logo (URL da imagem)', 'vemcomer' ); ?></label></th>
            <td><input type="url" id="vc_restaurant_logo" name="vc_restaurant_logo" class="regular-text" placeholder="https://.../logo.png" value="<?php echo esc_attr( $values['logo'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_cover"><?php echo esc_html__( 'Capa/hero (URL)', 'vemcomer' ); ?></label></th>
            <td><input type="url" id="vc_restaurant_cover" name="vc_restaurant_cover" class="regular-text" placeholder="https://.../capa.jpg" value="<?php echo esc_attr( $values['cover'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_featured_badge"><?php echo esc_html__( 'Selo Destaque', 'vemcomer' ); ?></label></th>
            <td>
                <label><input type="checkbox" id="vc_restaurant_featured_badge" name="vc_restaurant_featured_badge" value="1" <?php checked( ! empty( $values['featured_badge'] ) ); ?> /> <?php echo esc_html__( 'Destacar restaurante no perfil', 'vemcomer' ); ?></label>
            </td>
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
            <th><label for="vc_restaurant_holidays"><?php echo esc_html__( 'Feriados (datas fechadas)', 'vemcomer' ); ?></label></th>
            <td>
                <?php
                $holidays_json = get_post_meta( $post->ID, '_vc_restaurant_holidays', true );
                $holidays = $holidays_json ? json_decode( $holidays_json, true ) : [];
                $holidays = is_array( $holidays ) ? $holidays : [];
                ?>
                <div id="vc-holidays-manager">
                    <div id="vc-holidays-list">
                        <?php foreach ( $holidays as $holiday_date ) : ?>
                            <div class="vc-holiday-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <input type="date" name="vc_holidays[]" value="<?php echo esc_attr( $holiday_date ); ?>" class="regular-text" />
                                <button type="button" class="button button-small vc-remove-holiday"><?php echo esc_html__( 'Remover', 'vemcomer' ); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-small vc-add-holiday" style="margin-top: 10px;"><?php echo esc_html__( '+ Adicionar feriado', 'vemcomer' ); ?></button>
                </div>
                <p class="description">
                    <?php echo esc_html__( 'Adicione datas em que o restaurante estará fechado (feriados, manutenção, etc.). Formato: YYYY-MM-DD', 'vemcomer' ); ?>
                </p>
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
    // Link do Cardápio Digital (Standalone)
    $restaurant_slug = $post->post_name;
    $standalone_url = home_url( "/restaurante/{$restaurant_slug}/?mode=menu" );
    $cardapio_url = home_url( "/cardapio/{$restaurant_slug}/" );
    ?>
    <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid #158943;">
        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px; font-weight: 600;">
            <?php echo esc_html__( '🔗 Link do Cardápio Digital', 'vemcomer' ); ?>
        </h3>
        <p style="margin-bottom: 12px; color: #666; font-size: 13px;">
            <?php echo esc_html__( 'Compartilhe este link para que seus clientes acessem diretamente seu cardápio sem ver outros restaurantes.', 'vemcomer' ); ?>
        </p>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="text" 
                   id="vc-standalone-link" 
                   value="<?php echo esc_attr( $standalone_url ); ?>" 
                   readonly 
                   style="flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white;"
                   onclick="this.select();">
            <button type="button" 
                    class="button button-secondary" 
                    onclick="document.getElementById('vc-standalone-link').select(); document.execCommand('copy'); alert('<?php echo esc_js( __( 'Link copiado!', 'vemcomer' ) ); ?>');"
                    style="white-space: nowrap;">
                <?php echo esc_html__( 'Copiar Link', 'vemcomer' ); ?>
            </button>
        </div>
        <p style="margin-top: 8px; margin-bottom: 0; color: #999; font-size: 12px;">
            <?php echo esc_html__( 'URL alternativa:', 'vemcomer' ); ?> 
            <code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo esc_html( $cardapio_url ); ?></code>
        </p>
    </div>

    <h3 style="margin-top: 30px; margin-bottom: 10px; "><?php echo esc_html__( 'Experiência do perfil no app/marketplace', 'vemcomer' ); ?></h3>
    <p class="description" style="margin-top: -4px;"><?php echo esc_html__( 'Preencha estes campos para alimentar o layout completo do perfil (banners, métricas, reservas, FAQ, etc.).', 'vemcomer' ); ?></p>
    <table class="form-table">
        <tr>
            <th><label for="vc_restaurant_banners"><?php echo esc_html__( 'Banners (URLs, um por linha)', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_restaurant_banners" name="vc_restaurant_banners" class="large-text code" rows="3" placeholder="https://..."><?php echo esc_textarea( $values['banners'] ?? '' ); ?></textarea>
                <p class="description"><?php echo esc_html__( 'Imagens do topo do perfil. Deixe vazio para ocultar.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_orders_count"><?php echo esc_html__( 'Total de pedidos (número)', 'vemcomer' ); ?></label></th>
            <td><input type="number" id="vc_restaurant_orders_count" name="vc_restaurant_orders_count" class="small-text" min="0" value="<?php echo esc_attr( $values['orders_count'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_delivery_eta"><?php echo esc_html__( 'Tempo médio de entrega', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_delivery_eta" name="vc_restaurant_delivery_eta" class="regular-text" placeholder="Ex: 35-50 min" value="<?php echo esc_attr( $values['delivery_eta'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_delivery_fee"><?php echo esc_html__( 'Taxa de entrega (texto)', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_delivery_fee" name="vc_restaurant_delivery_fee" class="regular-text" placeholder="Ex: R$ 5,00" value="<?php echo esc_attr( $values['delivery_fee'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_delivery_type"><?php echo esc_html__( 'Tipo de entrega', 'vemcomer' ); ?></label></th>
            <td>
                <select id="vc_restaurant_delivery_type" name="vc_restaurant_delivery_type">
                    <?php $delivery_type = $values['delivery_type'] ?? ''; ?>
                    <option value="" <?php selected( $delivery_type, '' ); ?>><?php echo esc_html__( 'Não definido', 'vemcomer' ); ?></option>
                    <option value="own" <?php selected( $delivery_type, 'own' ); ?>><?php echo esc_html__( 'Entrega própria', 'vemcomer' ); ?></option>
                    <option value="marketplace" <?php selected( $delivery_type, 'marketplace' ); ?>><?php echo esc_html__( 'Parceiro/marketplace', 'vemcomer' ); ?></option>
                    <option value="pickup" <?php selected( $delivery_type, 'pickup' ); ?>><?php echo esc_html__( 'Somente retirada', 'vemcomer' ); ?></option>
                </select>
                <p class="description"><?php echo esc_html__( 'Exibe selos como "Entrega Própria" no perfil.', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_highlight_tags"><?php echo esc_html__( 'Destaques (chips)', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_restaurant_highlight_tags" name="vc_restaurant_highlight_tags" class="large-text" rows="3" placeholder="Africana
Vegana
Eventos & Reservas"><?php echo esc_textarea( $values['highlight_tags'] ?? '' ); ?></textarea>
                <p class="description"><?php echo esc_html__( 'Tags exibidas abaixo do nome (uma por linha).', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_plan_name"><?php echo esc_html__( 'Nome do plano', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_plan_name" name="vc_restaurant_plan_name" class="regular-text" placeholder="Vitrine" value="<?php echo esc_attr( $values['plan_name'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_plan_limit"><?php echo esc_html__( 'Limite de itens do plano', 'vemcomer' ); ?></label></th>
            <td><input type="number" min="0" step="1" id="vc_restaurant_plan_limit" name="vc_restaurant_plan_limit" class="small-text" value="<?php echo esc_attr( $values['plan_limit'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_plan_used"><?php echo esc_html__( 'Itens usados no plano', 'vemcomer' ); ?></label></th>
            <td><input type="number" min="0" step="1" id="vc_restaurant_plan_used" name="vc_restaurant_plan_used" class="small-text" value="<?php echo esc_attr( $values['plan_used'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_menu_filters"><?php echo esc_html__( 'Filtros do cardápio', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_restaurant_menu_filters" name="vc_restaurant_menu_filters" class="large-text" rows="2" placeholder="Vegano
Sem glúten
Promo"><?php echo esc_textarea( $values['menu_filters'] ?? '' ); ?></textarea>
                <p class="description"><?php echo esc_html__( 'Opções de filtro exibidas ao lado da busca do cardápio (uma por linha).', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_reservation_enabled"><?php echo esc_html__( 'Reservas de mesa', 'vemcomer' ); ?></label></th>
            <td>
                <label><input type="checkbox" id="vc_restaurant_reservation_enabled" name="vc_restaurant_reservation_enabled" value="1" <?php checked( $values['reservation_enabled'] ?? '', '1' ); ?> /> <?php echo esc_html__( 'Habilitar bloco/modal de reserva', 'vemcomer' ); ?></label>
                <p class="description" style="margin: 6px 0 8px;">
                    <?php echo esc_html__( 'Informe pelo menos um dos campos abaixo para direcionar o usuário.', 'vemcomer' ); ?>
                </p>
                <input type="url" id="vc_restaurant_reservation_link" name="vc_restaurant_reservation_link" class="regular-text" placeholder="https://..." value="<?php echo esc_attr( $values['reservation_link'] ?? '' ); ?>" />
                <p class="description"><?php echo esc_html__( 'Link externo ou formulário de reservas.', 'vemcomer' ); ?></p>
                <input type="text" id="vc_restaurant_reservation_phone" name="vc_restaurant_reservation_phone" class="regular-text" placeholder="62988887777" value="<?php echo esc_attr( $values['reservation_phone'] ?? '' ); ?>" style="margin-top:8px;" />
                <p class="description"><?php echo esc_html__( 'Telefone/WhatsApp para receber reservas.', 'vemcomer' ); ?></p>
                <textarea id="vc_restaurant_reservation_notes" name="vc_restaurant_reservation_notes" class="large-text" rows="2" placeholder="Detalhes opcionais para o cliente (ex: reserva mínima de 4 pessoas)"><?php echo esc_textarea( $values['reservation_notes'] ?? '' ); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_payment_methods"><?php echo esc_html__( 'Formas de pagamento', 'vemcomer' ); ?></label></th>
            <td><textarea id="vc_restaurant_payment_methods" name="vc_restaurant_payment_methods" class="large-text" rows="2" placeholder="Cartão
Pix
Dinheiro
VA/VR"><?php echo esc_textarea( $values['payment_methods'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_instagram"><?php echo esc_html__( 'Instagram', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_instagram" name="vc_restaurant_instagram" class="regular-text" placeholder="@seu_perfil" value="<?php echo esc_attr( $values['instagram'] ?? '' ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_facilities"><?php echo esc_html__( 'Facilidades/etiquetas', 'vemcomer' ); ?></label></th>
            <td><textarea id="vc_restaurant_facilities" name="vc_restaurant_facilities" class="large-text" rows="2" placeholder="Acessibilidade
Wi-Fi grátis
Espaço eventos"><?php echo esc_textarea( $values['facilities'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_observations"><?php echo esc_html__( 'Observações extras', 'vemcomer' ); ?></label></th>
            <td><textarea id="vc_restaurant_observations" name="vc_restaurant_observations" class="large-text" rows="3" placeholder="Informações gerais para clientes."><?php echo esc_textarea( $values['observations'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_faq"><?php echo esc_html__( 'FAQ (Pergunta | Resposta por linha)', 'vemcomer' ); ?></label></th>
            <td>
                <textarea id="vc_restaurant_faq" name="vc_restaurant_faq" class="large-text code" rows="4" placeholder="O restaurante aceita reservas online?|Sim, via botão Reservar.
Como funciona o delivery?|Entregamos em até 7km ou retirada."><?php echo esc_textarea( $values['faq'] ?? '' ); ?></textarea>
                <p class="description"><?php echo esc_html__( 'Use o separador "|" para dividir pergunta e resposta em cada linha.', 'vemcomer' ); ?></p>
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

    // Salvar feriados
    $holidays = isset( $_POST['vc_holidays'] ) && is_array( $_POST['vc_holidays'] ) ? $_POST['vc_holidays'] : [];
    $holidays_clean = [];
    foreach ( $holidays as $holiday ) {
        $holiday = sanitize_text_field( $holiday );
        // Validar formato YYYY-MM-DD
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $holiday ) ) {
            $holidays_clean[] = $holiday;
        }
    }
    $holidays_clean = array_unique( $holidays_clean );
    sort( $holidays_clean );
    if ( ! empty( $holidays_clean ) ) {
        update_post_meta( $post_id, '_vc_restaurant_holidays', wp_json_encode( $holidays_clean ) );
    } else {
        delete_post_meta( $post_id, '_vc_restaurant_holidays' );
    }

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

    // Campos adicionais do layout do perfil
    $orders_count = isset( $_POST['vc_restaurant_orders_count'] ) ? (int) $_POST['vc_restaurant_orders_count'] : 0;
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['orders_count'], max( 0, $orders_count ) );

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_eta'], sanitize_text_field( $_POST['vc_restaurant_delivery_eta'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_fee'], sanitize_text_field( $_POST['vc_restaurant_delivery_fee'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery_type'], sanitize_text_field( $_POST['vc_restaurant_delivery_type'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['logo'], esc_url_raw( $_POST['vc_restaurant_logo'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['cover'], esc_url_raw( $_POST['vc_restaurant_cover'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['featured_badge'], isset( $_POST['vc_restaurant_featured_badge'] ) ? '1' : '0' );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['banners'], sanitize_textarea_field( $_POST['vc_restaurant_banners'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['highlight_tags'], sanitize_textarea_field( $_POST['vc_restaurant_highlight_tags'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['plan_name'], sanitize_text_field( $_POST['vc_restaurant_plan_name'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['plan_limit'], absint( $_POST['vc_restaurant_plan_limit'] ?? 0 ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['plan_used'], absint( $_POST['vc_restaurant_plan_used'] ?? 0 ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['menu_filters'], sanitize_textarea_field( $_POST['vc_restaurant_menu_filters'] ?? '' ) );

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['reservation_enabled'], isset( $_POST['vc_restaurant_reservation_enabled'] ) ? '1' : '0' );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['reservation_link'], esc_url_raw( $_POST['vc_restaurant_reservation_link'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['reservation_phone'], sanitize_text_field( $_POST['vc_restaurant_reservation_phone'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['reservation_notes'], sanitize_textarea_field( $_POST['vc_restaurant_reservation_notes'] ?? '' ) );

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['payment_methods'], sanitize_textarea_field( $_POST['vc_restaurant_payment_methods'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['instagram'], sanitize_text_field( $_POST['vc_restaurant_instagram'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['facilities'], sanitize_textarea_field( $_POST['vc_restaurant_facilities'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['observations'], wp_kses_post( $_POST['vc_restaurant_observations'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['faq'], sanitize_textarea_field( $_POST['vc_restaurant_faq'] ?? '' ) );

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
