<?php
/**
 * Product Search Form
 *
 * Override of WooCommerce template to avoid global gettext filters.
 */
defined('ABSPATH') || exit;

$label = isset($args['label']) ? (string) $args['label'] : 'جستجوی محصولات:';

?>

<form role="search" method="get" class="woocommerce-product-search" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="woocommerce-product-search-field"><?php echo esc_html($label); ?></label>
    <input type="search"
        id="woocommerce-product-search-field"
        class="search-field"
        placeholder="<?php echo esc_attr('جستجو…'); ?>"
        value="<?php echo get_search_query(); ?>"
        name="s"
    />
    <button type="submit" value="<?php echo esc_attr('جستجو'); ?>"><?php echo esc_html('جستجو'); ?></button>
    <input type="hidden" name="post_type" value="product" />
</form>

