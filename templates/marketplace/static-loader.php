<?php
if (!function_exists('vc_marketplace_render_static_template')) {
    /**
     * Render a static marketplace HTML mock inside a WP template.
     *
     * @param string $html_filename Relative filename under the active theme templates/marketplace.
     */
    function vc_marketplace_render_static_template($html_filename)
    {
        $html_path = trailingslashit(get_template_directory()) . 'templates/marketplace/' . ltrim($html_filename, '/');

        if (!file_exists($html_path)) {
            echo '<div class="vc-marketplace-missing">' . esc_html__('Static template not found.', 'vemcomer') . '</div>';
            return;
        }

        $html = file_get_contents($html_path);

        if (preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $html, $linkMatches)) {
            foreach ($linkMatches[0] as $linkTag) {
                echo $linkTag;
            }
        }

        if (preg_match_all('/<style[^>]*>.*?<\\/style>/is', $html, $styleMatches)) {
            echo implode("\n", $styleMatches[0]);
        }

        if (preg_match('/<body[^>]*>(.*)<\\/body>/is', $html, $bodyMatch)) {
            echo $bodyMatch[1];
            return;
        }

        echo $html;
    }
}
