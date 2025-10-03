<?php
/**
 * Events — Dispara emails em eventos de pedido
 * @package VemComerCore
 */

namespace VC\Email;

use VC\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Events {
    public function init(): void {
        add_action( 'vemcomer/order_paid', [ $this, 'email_order_paid' ], 10, 1 );
        add_action( 'vemcomer/order_status_changed', [ $this, 'email_status_changed' ], 10, 3 );
    }

    private function can_send(): bool {
        $o = ( new Settings() )->get();
        return ! empty( $o['email_enabled'] );
    }

    private function send( string $to, string $subject, string $html ): void {
        $callback = static function () {
            return 'text/html';
        };
        add_filter( 'wp_mail_content_type', $callback );
        wp_mail( $to, $subject, $html );
        remove_filter( 'wp_mail_content_type', $callback );
    }

    public function email_order_paid( int $order_id ): void {
        if ( ! $this->can_send() ) { return; }
        $to = get_option( 'admin_email' );
        $title = sprintf( __( 'Pedido #%d pago', 'vemcomer' ), $order_id );
        $body  = Template::wrap( $title, '<p>' . esc_html__( 'Recebemos a confirmação de pagamento.', 'vemcomer' ) . '</p>' );
        $this->send( $to, $title, $body );
    }

    public function email_status_changed( int $order_id, string $new, string $old ): void {
        if ( ! $this->can_send() ) { return; }
        $to = get_option( 'admin_email' );
        $title = sprintf( __( 'Pedido #%d mudou para %s', 'vemcomer' ), $order_id, $new );
        $body  = Template::wrap( $title, '<p>' . esc_html__( 'Status atualizado no painel.', 'vemcomer' ) . '</p>' );
        $this->send( $to, $title, $body );
    }
}
