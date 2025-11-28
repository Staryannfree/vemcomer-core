<?php
/**
 * Template Name: Marketplace - Popup Planos Disponiveis
 * Description: Placeholder for a dynamic version of templates/marketplace/popup-planos-disponiveis.html.
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
    <p><?php esc_html_e('Placeholder for the Popup de Planos Disponíveis template.', 'vemcomer'); ?></p>
</div>
<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
