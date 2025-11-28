<?php
/**
 * Template Name: Marketplace - Notificacoes
 * Description: Placeholder for a dynamic version of templates/marketplace/notificacoes.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

require_once __DIR__ . '/static-loader.php';

if (! $vc_marketplace_inline) {
    get_header();
}

vc_marketplace_render_static_template('notificacoes.html');

if (! $vc_marketplace_inline) {
    get_footer();
}
