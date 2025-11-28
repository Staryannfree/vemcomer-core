<?php
/**
 * Template Name: Marketplace - Painel Lojista Plano Growth Master
 * Description: Placeholder for a dynamic version of templates/marketplace/painel-lojista-plano-growth-master.html.
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
    <p><?php esc_html_e('Placeholder for the Painel Lojista - Plano Growth Master template.', 'vemcomer'); ?></p>
</div>
<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
