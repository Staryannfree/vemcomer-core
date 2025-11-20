<?php
namespace VC\Admin {

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {
    public const OPTION_GROUP = 'vemcomer_settings_group';
    public const OPTION_NAME  = 'vemcomer_settings';
    public const PAGE_SLUG    = 'vemcomer-settings';

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public static function defaults(): array {
        return [
            'tiles_url'          => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'default_radius'     => 5,
            'kds_poll'           => 7000,
            'freight_base'       => 9.9,
            'freight_per_km'     => 1.5,
            'freight_free_above' => 150,
            'payment_text_pix'   => __( 'Pagamento via Pix na entrega.', 'vemcomer' ),
            'payment_text_card'  => __( 'Cartão na entrega (maquininha).', 'vemcomer' ),
            'payment_text_cash'  => __( 'Dinheiro (informe se precisa de troco).', 'vemcomer' ),
            'payment_provider'   => 'mercadopago',
            'payment_secret'     => '',
            'payment_mp_access_token' => '',
            'delivery_provider'  => '',
            'smclick_enabled'    => '1',
            'smclick_webhook_url' => 'https://api.smclick.com.br/integration/wordpress/892b64fa-3437-4430-a4bf-2bc9d3f69f1f/',
            'enable_wc_sync'     => '',
            'email_enabled'      => '',
            'email_from'         => '',
            'debug_logging'      => '',
        ];
    }

    public function register_page(): void {
        add_options_page(
            __( 'VemComer', 'vemcomer' ),
            __( 'VemComer', 'vemcomer' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_page' ]
        );
    }

    public function register_settings(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, [ $this, 'sanitize' ] );

        add_settings_section(
            'vc_maps',
            __( 'Mapas & Localização', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'Configure tiles e raio padrão usado nos shortcodes.', 'vemcomer' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_section(
            'vc_operations',
            __( 'Operações (KDS)', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'Intervalo de polling do painel da cozinha.', 'vemcomer' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_section(
            'vc_shipping',
            __( 'Frete', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'Regras padrão usadas como fallback quando um restaurante não define seus próprios valores.', 'vemcomer' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_section(
            'vc_payment',
            __( 'Textos de Pagamento', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'Mensagens exibidas ao cliente no checkout (pagamento offline).', 'vemcomer' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_section(
            'vc_integrations',
            __( 'Integrações e Email', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'Chaves utilizadas pelos webhooks e notificações.', 'vemcomer' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field( 'tiles_url', __( 'Tiles URL', 'vemcomer' ), [ $this, 'field_tiles_url' ], self::PAGE_SLUG, 'vc_maps' );
        add_settings_field( 'default_radius', __( 'Raio padrão (km)', 'vemcomer' ), [ $this, 'field_default_radius' ], self::PAGE_SLUG, 'vc_maps' );
        add_settings_field( 'kds_poll', __( 'KDS poll (ms)', 'vemcomer' ), [ $this, 'field_kds_poll' ], self::PAGE_SLUG, 'vc_operations' );
        add_settings_field( 'freight_base', __( 'Frete base', 'vemcomer' ), [ $this, 'field_freight_base' ], self::PAGE_SLUG, 'vc_shipping' );
        add_settings_field( 'freight_per_km', __( 'Frete por km', 'vemcomer' ), [ $this, 'field_freight_per_km' ], self::PAGE_SLUG, 'vc_shipping' );
        add_settings_field( 'freight_free_above', __( 'Frete grátis acima de (R$)', 'vemcomer' ), [ $this, 'field_freight_free_above' ], self::PAGE_SLUG, 'vc_shipping' );
        add_settings_field( 'payment_texts', __( 'Textos de pagamento', 'vemcomer' ), [ $this, 'field_payment_texts' ], self::PAGE_SLUG, 'vc_payment' );

        add_settings_field( 'payment_provider', __( 'Gateway de pagamento', 'vemcomer' ), [ $this, 'field_payment_provider' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'payment_secret', __( 'Segredo do webhook (HMAC)', 'vemcomer' ), [ $this, 'field_payment_secret' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'payment_mp_access_token', __( 'Token do Mercado Pago', 'vemcomer' ), [ $this, 'field_payment_mp_access_token' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'delivery_provider', __( 'Serviço de entrega', 'vemcomer' ), [ $this, 'field_delivery_provider' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'smclick_enabled', __( 'SMClick: ativar integração', 'vemcomer' ), [ $this, 'field_smclick_enabled' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'smclick_webhook_url', __( 'SMClick: webhook', 'vemcomer' ), [ $this, 'field_smclick_webhook_url' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'enable_wc_sync', __( 'Sincronizar com WooCommerce', 'vemcomer' ), [ $this, 'field_enable_wc_sync' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'email_enabled', __( 'Enviar emails de eventos', 'vemcomer' ), [ $this, 'field_email_enabled' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'email_from', __( 'Remetente (email)', 'vemcomer' ), [ $this, 'field_email_from' ], self::PAGE_SLUG, 'vc_integrations' );
        add_settings_field( 'debug_logging', __( 'Modo debug', 'vemcomer' ), [ $this, 'field_debug_logging' ], self::PAGE_SLUG, 'vc_integrations' );
    }

    public function sanitize( $input ): array {
        $defaults = self::defaults();
        $current  = $this->get();
        $output   = $current;

        $output['tiles_url']      = $this->sanitize_tiles_url( $input['tiles_url'] ?? '' );
        $output['default_radius'] = $this->sanitize_float( $input['default_radius'] ?? '', $defaults['default_radius'], 0.1 );
        $output['kds_poll']       = $this->sanitize_int( $input['kds_poll'] ?? '', $defaults['kds_poll'], 1000 );

        $output['freight_base']       = $this->sanitize_float( $input['freight_base'] ?? '', $defaults['freight_base'], 0 );
        $output['freight_per_km']     = $this->sanitize_float( $input['freight_per_km'] ?? '', $defaults['freight_per_km'], 0 );
        $output['freight_free_above'] = $this->sanitize_float( $input['freight_free_above'] ?? '', $defaults['freight_free_above'], 0 );

        $output['payment_text_pix']  = $this->sanitize_textarea( $input['payment_text_pix'] ?? '', $defaults['payment_text_pix'] );
        $output['payment_text_card'] = $this->sanitize_textarea( $input['payment_text_card'] ?? '', $defaults['payment_text_card'] );
        $output['payment_text_cash'] = $this->sanitize_textarea( $input['payment_text_cash'] ?? '', $defaults['payment_text_cash'] );

        $output['payment_provider']  = isset( $input['payment_provider'] ) ? sanitize_text_field( wp_unslash( $input['payment_provider'] ) ) : $current['payment_provider'];
        $output['payment_secret']    = isset( $input['payment_secret'] ) ? sanitize_text_field( wp_unslash( $input['payment_secret'] ) ) : $current['payment_secret'];
        $output['payment_mp_access_token'] = isset( $input['payment_mp_access_token'] ) ? sanitize_text_field( wp_unslash( $input['payment_mp_access_token'] ) ) : $current['payment_mp_access_token'];
        $output['delivery_provider'] = isset( $input['delivery_provider'] ) ? sanitize_text_field( wp_unslash( $input['delivery_provider'] ) ) : $current['delivery_provider'];
        $output['smclick_enabled']   = ! empty( $input['smclick_enabled'] ) ? '1' : '';
        $output['smclick_webhook_url'] = isset( $input['smclick_webhook_url'] ) ? esc_url_raw( $input['smclick_webhook_url'] ) : $current['smclick_webhook_url'];
        $output['enable_wc_sync']    = ! empty( $input['enable_wc_sync'] ) ? '1' : '';
        $output['email_enabled']     = ! empty( $input['email_enabled'] ) ? '1' : '';
        $output['email_from']        = isset( $input['email_from'] ) ? sanitize_email( $input['email_from'] ) : $current['email_from'];
        $output['debug_logging']     = ! empty( $input['debug_logging'] ) ? '1' : '';

        return $output;
    }

    public function get(): array {
        return wp_parse_args( (array) get_option( self::OPTION_NAME, [] ), self::defaults() );
    }

    public function render_page(): void {
        wp_enqueue_style( 'vemcomer-admin' );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Configurações do VemComer', 'vemcomer' ) . '</h1>';
        echo '<form method="post" action="options.php" novalidate="novalidate">';
        settings_fields( self::OPTION_GROUP );
        do_settings_sections( self::PAGE_SLUG );
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function field_tiles_url(): void {
        $value = (string) $this->get()['tiles_url'];
        $this->text_input( 'tiles_url', $value, [ 'class' => 'regular-text', 'placeholder' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' ] );
        echo '<p class="description">' . esc_html__( 'URL das tiles do Leaflet/Mapbox.', 'vemcomer' ) . '</p>';
    }

    public function field_default_radius(): void {
        $value = (float) $this->get()['default_radius'];
        $this->number_input( 'default_radius', $value, 0.1, [ 'min' => '0.1', 'step' => '0.1' ] );
        echo '<p class="description">' . esc_html__( 'Usado como fallback nos shortcodes de busca.', 'vemcomer' ) . '</p>';
    }

    public function field_kds_poll(): void {
        $value = (int) $this->get()['kds_poll'];
        $this->number_input( 'kds_poll', $value, 1, [ 'min' => '1000', 'step' => '500' ] );
        echo '<p class="description">' . esc_html__( 'Intervalo em milissegundos para atualização do KDS.', 'vemcomer' ) . '</p>';
    }

    public function field_freight_base(): void {
        $value = (float) $this->get()['freight_base'];
        $this->number_input( 'freight_base', $value, 0.01, [ 'min' => '0', 'step' => '0.1' ] );
        echo '<p class="description">' . esc_html__( 'Valor fixo inicial cobrado por entrega.', 'vemcomer' ) . '</p>';
    }

    public function field_freight_per_km(): void {
        $value = (float) $this->get()['freight_per_km'];
        $this->number_input( 'freight_per_km', $value, 0.01, [ 'min' => '0', 'step' => '0.1' ] );
        echo '<p class="description">' . esc_html__( 'Valor adicional aplicado por quilômetro.', 'vemcomer' ) . '</p>';
    }

    public function field_freight_free_above(): void {
        $value = (float) $this->get()['freight_free_above'];
        $this->number_input( 'freight_free_above', $value, 0.01, [ 'min' => '0', 'step' => '1' ] );
        echo '<p class="description">' . esc_html__( 'Pedidos acima deste valor recebem frete grátis.', 'vemcomer' ) . '</p>';
    }

    public function field_payment_texts(): void {
        $values = $this->get();
        $this->textarea_input( 'payment_text_pix', (string) $values['payment_text_pix'], __( 'Pix', 'vemcomer' ) );
        $this->textarea_input( 'payment_text_card', (string) $values['payment_text_card'], __( 'Cartão', 'vemcomer' ) );
        $this->textarea_input( 'payment_text_cash', (string) $values['payment_text_cash'], __( 'Dinheiro', 'vemcomer' ) );
    }

    public function field_payment_provider(): void {
        $this->text_input( 'payment_provider', (string) $this->get()['payment_provider'], [ 'class' => 'regular-text', 'placeholder' => 'ex.: mercadopago, pagarme', 'id' => 'vemcomer-payment-provider' ] );
    }

    public function field_payment_secret(): void {
        $input_id = 'vemcomer-payment-secret';
        $this->text_input( 'payment_secret', (string) $this->get()['payment_secret'], [ 'class' => 'regular-text', 'type' => 'password', 'id' => $input_id, 'autocomplete' => 'off' ] );
        echo '<p class="description">' . esc_html__( 'Usado para validar webhooks (sha256 HMAC). Compartilhe apenas com o serviço intermediário.', 'vemcomer' ) . '</p>';
        echo '<p><button type="button" class="button" id="vemcomer-generate-secret">' . esc_html__( 'Gerar novo segredo', 'vemcomer' ) . '</button></p>';
        static $secret_script_printed = false;
        if ( ! $secret_script_printed ) {
            $secret_script_printed = true;
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var button = document.getElementById('vemcomer-generate-secret');
                var input  = document.getElementById('<?php echo esc_js( $input_id ); ?>');
                if (!button || !input) {
                    return;
                }
                button.addEventListener('click', function () {
                    var random = window.crypto && window.crypto.getRandomValues ?
                        function () {
                            var array = new Uint32Array(8);
                            window.crypto.getRandomValues(array);
                            return Array.from(array).map(function (value) {
                                return value.toString(16).padStart(8, '0');
                            }).join('').slice(0, 64);
                        }() :
                        (Math.random().toString(36).substring(2) + Math.random().toString(36).substring(2) + Date.now().toString(36)).slice(0, 64);
                    input.value = random;
                    input.type = 'text';
                    input.focus();
                });
            });
            </script>
            <?php
        }
    }

    public function field_payment_mp_access_token(): void {
        $this->text_input( 'payment_mp_access_token', (string) $this->get()['payment_mp_access_token'], [ 'class' => 'regular-text', 'type' => 'password', 'placeholder' => 'APP_USR-xxxxxxxxxxxx', 'autocomplete' => 'off' ] );
        echo '<p class="description">' . esc_html__( 'Token de acesso usado pelo SDK oficial do Mercado Pago para validar notificações.', 'vemcomer' ) . '</p>';
    }

    public function field_delivery_provider(): void {
        $this->text_input( 'delivery_provider', (string) $this->get()['delivery_provider'], [ 'class' => 'regular-text', 'placeholder' => 'loggi, lalamove...' ] );
    }

    public function field_smclick_enabled(): void {
        $value = (bool) $this->get()['smclick_enabled'];
        printf(
            '<label><input type="checkbox" name="%1$s[smclick_enabled]" value="1" %2$s /> %3$s</label>',
            esc_attr( self::OPTION_NAME ),
            checked( $value, true, false ),
            esc_html__( 'Ativar disparo para SMClick quando restaurantes são cadastrados/aprovados.', 'vemcomer' )
        );
    }

    public function field_smclick_webhook_url(): void {
        $value = (string) $this->get()['smclick_webhook_url'];
        $this->text_input( 'smclick_webhook_url', $value, [ 'class' => 'regular-text', 'type' => 'url', 'placeholder' => 'https://api.smclick.com.br/...' ] );
        echo '<p class="description">' . esc_html__( 'Endpoint que receberá o payload JSON do evento.', 'vemcomer' ) . '</p>';
    }

    public function field_enable_wc_sync(): void {
        $value = (bool) $this->get()['enable_wc_sync'];
        printf(
            '<label><input type="checkbox" name="%1$s[enable_wc_sync]" value="1" %2$s /> %3$s</label>',
            esc_attr( self::OPTION_NAME ),
            checked( $value, true, false ),
            esc_html__( 'Ativar sincronização com WooCommerce.', 'vemcomer' )
        );
    }

    public function field_email_enabled(): void {
        $value = (bool) $this->get()['email_enabled'];
        printf(
            '<label><input type="checkbox" name="%1$s[email_enabled]" value="1" %2$s /> %3$s</label>',
            esc_attr( self::OPTION_NAME ),
            checked( $value, true, false ),
            esc_html__( 'Enviar emails administrativos sobre eventos.', 'vemcomer' )
        );
    }

    public function field_email_from(): void {
        $value = (string) $this->get()['email_from'];
        printf(
            '<input type="email" class="regular-text" name="%1$s[email_from]" value="%2$s" placeholder="admin@exemplo.com" />',
            esc_attr( self::OPTION_NAME ),
            esc_attr( $value )
        );
    }

    public function field_debug_logging(): void {
        $value = (bool) $this->get()['debug_logging'];
        printf(
            '<label><input type="checkbox" name="%1$s[debug_logging]" value="1" %2$s /> %3$s</label>',
            esc_attr( self::OPTION_NAME ),
            checked( $value, true, false ),
            esc_html__( 'Ativar logs detalhados (VC_DEBUG).', 'vemcomer' )
        );
        echo '<p class="description">' . esc_html__( 'Grava wp-content/uploads/vemcomer-debug.log quando habilitado.', 'vemcomer' ) . '</p>';
    }

    private function text_input( string $name, string $value, array $attrs = [] ): void {
        $attrs = wp_parse_args( $attrs, [ 'type' => 'text' ] );
        $attr_html = $this->build_attr_html( $attrs );
        printf(
            '<input %1$s name="%2$s[%3$s]" value="%4$s" />',
            $attr_html,
            esc_attr( self::OPTION_NAME ),
            esc_attr( $name ),
            esc_attr( $value )
        );
    }

    private function number_input( string $name, float|int $value, float $step, array $attrs = [] ): void {
        $attrs = wp_parse_args( $attrs, [ 'type' => 'number', 'step' => (string) $step ] );
        $this->text_input( $name, (string) $value, $attrs );
    }

    private function textarea_input( string $name, string $value, string $label ): void {
        printf( '<p><label><strong>%s</strong><br />', esc_html( $label ) );
        printf(
            '<textarea name="%1$s[%2$s]" rows="3" class="large-text">%3$s</textarea></label></p>',
            esc_attr( self::OPTION_NAME ),
            esc_attr( $name ),
            esc_textarea( $value )
        );
    }

    private function sanitize_tiles_url( string $value ): string {
        $value = trim( $value );
        if ( '' === $value ) {
            return self::defaults()['tiles_url'];
        }

        $value = esc_url_raw( $value );
        return $value ?: self::defaults()['tiles_url'];
    }

    private function sanitize_float( $value, float $default, float $min = 0 ): float {
        if ( is_string( $value ) ) {
            $value = str_replace( ',', '.', $value );
        }
        $value = is_numeric( $value ) ? (float) $value : $default;
        if ( $value < $min ) {
            $value = $default;
        }
        return round( $value, 2 );
    }

    private function sanitize_int( $value, int $default, int $min = 0 ): int {
        $value = is_numeric( $value ) ? (int) $value : $default;
        if ( $value < $min ) {
            $value = $default;
        }
        return $value;
    }

    private function sanitize_textarea( string $value, string $default ): string {
        $value = trim( wp_unslash( $value ) );
        if ( '' === $value ) {
            return $default;
        }
        return sanitize_textarea_field( $value );
    }

    private function build_attr_html( array $attrs ): string {
        $parts = [];
        foreach ( $attrs as $key => $value ) {
            $parts[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
        }
        return implode( ' ', $parts );
    }
}
}

namespace {
    function vc_get_settings(): array {
        return (array) get_option( \VC\Admin\Settings::OPTION_NAME, [] );
    }

    function vc_get_settings_with_defaults(): array {
        $defaults = \VC\Admin\Settings::defaults();
        return wp_parse_args( vc_get_settings(), $defaults );
    }

    function vc_get_setting( string $key, $default = null ) {
        $settings = vc_get_settings_with_defaults();
        return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
    }
}
