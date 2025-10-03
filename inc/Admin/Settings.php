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

        add_settings_field( 'enable_wc_sync', __( 'Sincronizar com WooCommerce', 'vemcomer' ), [ $this, 'field_enable_wc_sync' ], self::PAGE_SLUG, 'vc_general' );
        add_settings_field( 'email_enabled', __( 'Enviar emails de eventos', 'vemcomer' ), [ $this, 'field_email_enabled' ], self::PAGE_SLUG, 'vc_general' );
        add_settings_field( 'email_from', __( 'Remetente (email)', 'vemcomer' ), [ $this, 'field_email_from' ], self::PAGE_SLUG, 'vc_general' );
    }

    public function sanitize( $input ): array {
        $out = $this->get();
        $out['payment_provider']  = isset( $input['payment_provider'] ) ? sanitize_text_field( wp_unslash( $input['payment_provider'] ) ) : '';
        $out['payment_secret']    = isset( $input['payment_secret'] ) ? sanitize_text_field( wp_unslash( $input['payment_secret'] ) ) : '';
        $out['delivery_provider'] = isset( $input['delivery_provider'] ) ? sanitize_text_field( wp_unslash( $input['delivery_provider'] ) ) : '';
        $out['enable_wc_sync']    = ! empty( $input['enable_wc_sync'] ) ? '1' : '';
        $out['email_enabled']     = ! empty( $input['email_enabled'] ) ? '1' : '';
        $out['email_from']        = isset( $input['email_from'] ) ? sanitize_email( $input['email_from'] ) : '';
        return $out;
    }

    public function get(): array {
        $defaults = [
            'payment_provider'  => '',
            'payment_secret'    => '',
            'delivery_provider' => '',
            'enable_wc_sync'    => '',
            'email_enabled'     => '',
            'email_from'        => '',
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
    public function field_enable_wc_sync(): void {
        $o = $this->get();
        echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_NAME . '[enable_wc_sync]' ) . '" value="1" ' . checked( (bool) $o['enable_wc_sync'], true, false ) . ' /> ' . esc_html__( 'Ativar sincronização com WooCommerce', 'vemcomer' ) . '</label>';
    }
    public function field_email_enabled(): void {
        $o = $this->get();
        echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_NAME . '[email_enabled]' ) . '" value="1" ' . checked( (bool) $o['email_enabled'], true, false ) . ' /> ' . esc_html__( 'Ativar emails (admin)', 'vemcomer' ) . '</label>';
    }
    public function field_email_from(): void {
        $o = $this->get();
        echo '<input type="email" class="regular-text" name="' . esc_attr( self::OPTION_NAME . '[email_from]' ) . '" value="' . esc_attr( (string) $o['email_from'] ) . '" placeholder="admin@exemplo.com" />';
        add_filter( 'wp_mail_from', static function ( $from ) use ( $o ) {
            return ! empty( $o['email_from'] ) ? $o['email_from'] : $from;
        } );
    }
}
