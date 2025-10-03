<?php
/**
 * Template — Helpers de email HTML simples
 * @package VemComerCore
 */

namespace VC\Email;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Template {
    public static function wrap( string $title, string $body ): string {
        $styles = 'font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden';
        $header = '<div style="background:#111;color:#fff;padding:14px 18px"><strong>VemComer</strong></div>';
        $h1 = '<h2 style="margin:16px 0 8px">' . esc_html( $title ) . '</h2>';
        $inner = '<div style="padding:16px">' . $h1 . '<div>' . wp_kses_post( $body ) . '</div></div>';
        $footer = '<div style="background:#fafafa;color:#777;padding:10px 16px;font-size:12px">&copy; ' . wp_date('Y') . ' VemComer</div>';
        return '<div style="' . esc_attr( $styles ) . '">' . $header . $inner . $footer . '</div>';
    }
}
