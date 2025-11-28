<?php
/**
 * Template Name: Marketplace - Gestao Cardapio
 * Description: Placeholder for a dynamic version of templates/marketplace/gestao-cardapio.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

require_once __DIR__ . '/static-loader.php';

if (! $vc_marketplace_inline) {
    get_header();
}

vc_marketplace_render_static_template('gestao-cardapio.html');

if (! $vc_marketplace_inline) {
    get_footer();
}
