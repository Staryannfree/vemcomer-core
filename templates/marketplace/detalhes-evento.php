<?php
/**
 * Template Name: Marketplace - Detalhes Evento
 * Description: Placeholder for a dynamic version of templates/marketplace/detalhes-evento.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

if (! $vc_marketplace_inline) {
    get_header();
}
?>
<div class="vc-marketplace-placeholder">
    <p><?php esc_html_e('Placeholder for the Detalhes do Evento template.', 'vemcomer'); ?></p>
</div>
<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
