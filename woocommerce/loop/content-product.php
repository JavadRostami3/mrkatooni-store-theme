<?php
/**
 * Shared product card used by classic WooCommerce loops.
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

?>

<li <?php wc_product_class('', $product); ?>>
    <?php echo mrkatooni_store_render_product_card($product); ?>
</li>
