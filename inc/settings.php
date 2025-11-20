<?php
namespace VC\Admin {

if ( ! defined( 'ABSPATH' ) ) { exit; }

use VC\Integration\SMClick;

class Settings {
    public const OPTION_GROUP = 'vemcomer_settings_group';
    public const OPTION_NAME  = 'vemcomer_settings';
    public const PAGE_SLUG    = 'vemcomer-settings';

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_post_vc_smclick_test', [ $this, 'handle_test_webhook' ] );
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
            'smclick_event_urls' => SMClick::default_event_urls(),
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

        add_settings_section(
            'vc_smclick',
            __( 'SMClick', 'vemcomer' ),
            function () {
                echo '<p>' . esc_html__( 'URLs específicas por evento para disparos no SMClick.', 'vemcomer' ) . '</p>';
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
        add_settings_field( 'smclick_event_urls', __( 'SMClick: webhooks por evento', 'vemcomer' ), [ $this, 'field_smclick_events' ], self::PAGE_SLUG, 'vc_smclick' );
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
        $output['smclick_event_urls']  = $this->sanitize_smclick_events( $input['smclick_event_urls'] ?? [] );
        $output['enable_wc_sync']    = ! empty( $input['enable_wc_sync'] ) ? '1' : '';
        $output['email_enabled']     = ! empty( $input['email_enabled'] ) ? '1' : '';
        $output['email_from']        = isset( $input['email_from'] ) ? sanitize_email( $input['email_from'] ) : $current['email_from'];
        $output['debug_logging']     = ! empty( $input['debug_logging'] ) ? '1' : '';

        return $output;
    }

    public function handle_test_webhook(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sem permissão.', 'vemcomer' ) );
        }

        check_admin_referer( 'vc_smclick_test' );

        $event    = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
        $url      = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        $redirect = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );

        if ( '' === $event || '' === $url ) {
            wp_safe_redirect( add_query_arg( [ 'smclick_test' => 'missing' ], $redirect ) );
            exit;
        }

        $payload = SMClick::sample_payload( $event );
        if ( is_wp_error( $payload ) ) {
            wp_safe_redirect( add_query_arg( [ 'smclick_test' => 'invalid', 'smclick_error' => $payload->get_error_message() ], $redirect ) );
            exit;
        }

        $response = wp_remote_post(
            $url,
            [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( add_query_arg( [ 'smclick_test' => $event, 'smclick_error' => $response->get_error_message() ], $redirect ) );
            exit;
        }

        $code = wp_remote_retrieve_response_code( $response );
        wp_safe_redirect( add_query_arg( [ 'smclick_test' => $event, 'smclick_status' => $code ], $redirect ) );
        exit;
    }

    public function get(): array {
        return wp_parse_args( (array) get_option( self::OPTION_NAME, [] ), self::defaults() );
    }

    public function render_page(): void {
        wp_enqueue_style( 'vemcomer-admin' );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Configurações do VemComer', 'vemcomer' ) . '</h1>';
        $test_event  = isset( $_GET['smclick_test'] ) ? sanitize_text_field( wp_unslash( $_GET['smclick_test'] ) ) : '';
        $test_error  = isset( $_GET['smclick_error'] ) ? sanitize_text_field( wp_unslash( $_GET['smclick_error'] ) ) : '';
        $test_status = isset( $_GET['smclick_status'] ) ? (int) $_GET['smclick_status'] : null;

        if ( $test_event ) {
            $message = $test_error
                ? sprintf( __( 'Falha ao testar o webhook %1$s: %2$s', 'vemcomer' ), $test_event, $test_error )
                : sprintf( __( 'Webhook %1$s testado (HTTP %2$s).', 'vemcomer' ), $test_event, $test_status ?? '-' );
            $class   = $test_error ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
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

    public function field_smclick_events(): void {
        $settings     = $this->get();
        $event_urls   = is_array( $settings['smclick_event_urls'] ?? null ) ? $settings['smclick_event_urls'] : [];
        $definitions  = SMClick::event_definitions();
        $test_nonce   = wp_create_nonce( 'vc_smclick_test' );

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__( 'Evento', 'vemcomer' ) . '</th><th>' . esc_html__( 'Webhook e placeholders', 'vemcomer' ) . '</th></tr></thead><tbody>';

        foreach ( $definitions as $event => $def ) {
            $url            = isset( $event_urls[ $event ] ) ? (string) $event_urls[ $event ] : '';
            $placeholder_url = $def['placeholder_url'] ?? '';
            echo '<tr>';
            echo '<th scope="row" style="width: 220px">' . esc_html( $def['label'] ) . '</th>';
            echo '<td>';
            $this->text_input( 'smclick_event_urls[' . $event . ']', $url, [ 'class' => 'regular-text', 'type' => 'url', 'placeholder' => $placeholder_url ] );
            echo ' <button type="button" class="button vc-smclick-test" data-event="' . esc_attr( $event ) . '" data-nonce="' . esc_attr( $test_nonce ) . '">' . esc_html__( 'Testar', 'vemcomer' ) . '</button>';

            if ( ! empty( $def['description'] ) ) {
                echo '<p class="description">' . esc_html( $def['description'] ) . '</p>';
            }

            if ( ! empty( $def['placeholders'] ) ) {
                echo '<p class="description"><strong>' . esc_html__( 'Placeholders:', 'vemcomer' ) . '</strong> ' . esc_html( implode( ', ', $def['placeholders'] ) ) . '</p>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        ?>
        <script>
        (function() {
            const buttons = document.querySelectorAll('.vc-smclick-test');
            if (!buttons.length) {
                return;
            }

            const submitTest = function(eventKey, url, nonce) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '<?php echo esc_js( admin_url( 'admin-post.php' ) ); ?>';
                form.style.display = 'none';

                const fields = {
                    action: 'vc_smclick_test',
                    event: eventKey,
                    url: url,
                    _wpnonce: nonce
                };

                Object.keys(fields).forEach(function (name) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = fields[name];
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            };

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = btn.closest('tr');
                    if (!row) return;
                    const input = row.querySelector('input[type="url"]');
                    const url = input ? input.value : '';
                    if (!url) {
                        window.alert('<?php echo esc_js( __( 'Informe uma URL antes de testar.', 'vemcomer' ) ); ?>');
                        return;
                    }
                    submitTest(btn.dataset.event, url, btn.dataset.nonce);
                });
            });
        })();
        </script>
        <?php
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

    private function sanitize_smclick_events( $values ): array {
        $defaults = SMClick::default_event_urls();
        $output   = $defaults;

        if ( ! is_array( $values ) ) {
            return $output;
        }

        foreach ( $defaults as $event => $placeholder ) {
            if ( isset( $values[ $event ] ) ) {
                $sanitized = esc_url_raw( (string) $values[ $event ] );
                $output[ $event ] = $sanitized;
            }
        }

        return $output;
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
