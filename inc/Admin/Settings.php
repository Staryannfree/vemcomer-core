<?php
/**
 * Settings — Página de Configurações (Settings API)
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {
    public const OPTION_GROUP = 'vemcomer_settings_group';
    public const OPTION_NAME  = 'vemcomer_settings';
    public const PAGE_SLUG    = 'vemcomer-settings';

    public function init(): void {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'ensure_menu' ], 20 );
    }

    /** Garante que a página de Configurações exista como submenu (caso não tenha o pacote 1) */
    public function ensure_menu(): void {
        add_submenu_page( 'vemcomer-root', __( 'Configurações', 'vemcomer' ), __( 'Configurações', 'vemcomer' ), 'manage_options', self::PAGE_SLUG, [ $this, 'render_page' ] );
    }

    public function register_settings(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, [ $this, 'sanitize' ] );

        add_settings_section( 'vc_general', __( 'Geral', 'vemcomer' ), function () {
            echo '<p>' . esc_html__( 'Defina chaves e integrações.', 'vemcomer' ) . '</p>';
        }, self::PAGE_SLUG );

        add_settings_field( 'payment_provider', __( 'Gateway de Pagamento', 'vemcomer' ), [ $this, 'field_payment_provider' ], self::PAGE_SLUG, 'vc_general' );
        add_settings_field( 'payment_secret', __( 'Segredo do Webhook (HMAC)', 'vemcomer' ), [ $this, 'field_payment_secret' ], self::PAGE_SLUG, 'vc_general' );
        add_settings_field( 'delivery_provider', __( 'Serviço de Entrega', 'vemcomer' ), [ $this, 'field_delivery_provider' ], self::PAGE_SLUG, 'vc_general' );
    }

    public function sanitize( $input ): array {
        $out = $this->get();
        $out['payment_provider']  = isset( $input['payment_provider'] ) ? sanitize_text_field( wp_unslash( $input['payment_provider'] ) ) : '';
        $out['payment_secret']    = isset( $input['payment_secret'] ) ? sanitize_text_field( wp_unslash( $input['payment_secret'] ) ) : '';
        $out['delivery_provider'] = isset( $input['delivery_provider'] ) ? sanitize_text_field( wp_unslash( $input['delivery_provider'] ) ) : '';
        return $out;
    }

    public function get(): array {
        $defaults = [
            'payment_provider'  => '',
            'payment_secret'    => '',
            'delivery_provider' => '',
        ];
        return wp_parse_args( (array) get_option( self::OPTION_NAME, [] ), $defaults );
    }

    public function render_page(): void {
        wp_enqueue_style( 'vemcomer-admin' );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Configurações do VemComer', 'vemcomer' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( self::OPTION_GROUP );
        do_settings_sections( self::PAGE_SLUG );
        submit_button();
        echo '</form></div>';
    }

    private function text( string $name, string $value, string $type = 'text', string $placeholder = '' ): void {
        echo '<input type="' . esc_attr( $type ) . '" class="regular-text" name="' . esc_attr( self::OPTION_NAME . "[$name]" ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
    }

    public function field_payment_provider(): void {
        $o = $this->get();
        $this->text( 'payment_provider', (string) $o['payment_provider'], 'text', 'ex.: stripe, pagarme' );
    }
    public function field_payment_secret(): void {
        $o = $this->get();
        $this->text( 'payment_secret', (string) $o['payment_secret'], 'password', 'segredo usado no HMAC' );
    }
    public function field_delivery_provider(): void {
        $o = $this->get();
        $this->text( 'delivery_provider', (string) $o['delivery_provider'], 'text', 'ex.: loggi, lalamove' );
    }
}
