<?php
/**
 * Template Name: Marketplace - Meus Favoritos
 * Description: Placeholder for a dynamic version of templates/marketplace/meus-favoritos.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

require_once __DIR__ . '/static-loader.php';

if (! $vc_marketplace_inline) {
    get_header();
}

vc_marketplace_render_static_template('meus-favoritos.html');

if (! $vc_marketplace_inline) {
    get_footer();
}
