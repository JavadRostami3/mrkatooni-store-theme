<?php

if (!defined('ABSPATH')) {
    exit;
}

function mrkatooni_store_asset_version(string $relative_path): string
{
    $absolute_path = get_template_directory() . $relative_path;

    if (!file_exists($absolute_path)) {
        return wp_get_theme()->get('Version');
    }

    return (string) filemtime($absolute_path);
}

function mrkatooni_store_setup(): void
{
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 120,
        'width'       => 360,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('woocommerce');

    register_nav_menus([
        'primary' => __('Primary Menu', 'mrkatooni-store'),
        'mobile'  => __('Mobile Menu', 'mrkatooni-store'),
    ]);
}
add_action('after_setup_theme', 'mrkatooni_store_setup');

function mrkatooni_store_enqueue_assets(): void
{
    $hugeicons_css = WP_CONTENT_DIR . '/plugins/hugeicons/css/font/icons.css';
    if (file_exists($hugeicons_css)) {
        wp_enqueue_style(
            'mrkatooni-store-hugeicons',
            content_url('/plugins/hugeicons/css/font/icons.css'),
            [],
            filemtime($hugeicons_css)
        );
    }

    wp_enqueue_style(
        'mrkatooni-store-main',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        mrkatooni_store_asset_version('/assets/css/main.css')
    );

    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style(
            'mrkatooni-store-single-product',
            get_template_directory_uri() . '/assets/css/single-product.css',
            ['mrkatooni-store-main'],
            mrkatooni_store_asset_version('/assets/css/single-product.css')
        );
    }

    wp_enqueue_script(
        'mrkatooni-store-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        mrkatooni_store_asset_version('/assets/js/theme.js'),
        true
    );

    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script(
            'mrkatooni-store-single-product',
            get_template_directory_uri() . '/assets/js/single-product.js',
            ['jquery'],
            mrkatooni_store_asset_version('/assets/js/single-product.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'mrkatooni_store_enqueue_assets');

add_action('wp', 'mrkatooni_store_adjust_single_product_hooks');
function mrkatooni_store_adjust_single_product_hooks(): void
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

    add_action('woocommerce_single_product_summary', 'mrkatooni_store_single_product_brand_label', 4);
    add_action('woocommerce_after_single_product_summary', 'mrkatooni_store_output_product_description', 10);
    add_action('woocommerce_after_single_product_summary', 'mrkatooni_store_output_related_products', 20);
}

function mrkatooni_store_single_product_brand_label(): void
{
    $product = wc_get_product(get_the_ID());

    if (!$product) {
        return;
    }

    $terms = get_the_terms($product->get_id(), 'product_cat');

    if (empty($terms) || is_wp_error($terms)) {
        return;
    }

    echo '<div class="mk-product-brand">' . esc_html($terms[0]->name) . '</div>';
}

function mrkatooni_store_output_product_description(): void
{
    global $product;

    if (!$product) {
        return;
    }

    $description = apply_filters('the_content', get_post_field('post_content', $product->get_id()));
    $short_description = apply_filters('woocommerce_short_description', $product->get_short_description());

    if ('' === trim(wp_strip_all_tags($description)) && '' === trim(wp_strip_all_tags($short_description))) {
        return;
    }
    ?>
    <section class="mk-product-description" aria-labelledby="mk-product-description-title">
        <h2 id="mk-product-description-title">توضیحات محصول</h2>
        <div class="mk-product-description__content">
            <?php echo $description ? wp_kses_post($description) : wp_kses_post(wpautop($short_description)); ?>
        </div>
    </section>
    <?php
}

function mrkatooni_store_variable_add_to_cart_text(string $text, WC_Product $product): string
{
    if ($product->is_type('variable')) {
        return 'انتخاب سایز';
    }

    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'mrkatooni_store_variable_add_to_cart_text', 10, 2);

function mrkatooni_store_render_home_product_button(WC_Product $product): string
{
    $product_id = $product->get_id();
    $title = get_the_title($product_id);
    $permalink = get_permalink($product_id);

    if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
        return sprintf(
            '<a href="%1$s" data-quantity="1" class="button mk-home-product-card__button add_to_cart_button ajax_add_to_cart" data-product_id="%2$d" data-product_sku="%3$s" aria-label="%4$s" rel="nofollow">%5$s</a>',
            esc_url($product->add_to_cart_url()),
            (int) $product_id,
            esc_attr($product->get_sku()),
            esc_attr(sprintf('افزودن "%s" به سبد خرید', $title)),
            esc_html($product->add_to_cart_text())
        );
    }

    $button_text = $product->is_type('variable') ? 'انتخاب سایز' : 'مشاهده محصول';
    $aria_label = $product->is_type('variable')
        ? sprintf('انتخاب گزینه ها برای "%s"', $title)
        : sprintf('مشاهده محصول "%s"', $title);

    return sprintf(
        '<a href="%1$s" class="button mk-home-product-card__button" aria-label="%2$s" rel="nofollow">%3$s</a>',
        esc_url($permalink),
        esc_attr($aria_label),
        esc_html($button_text)
    );
}

function mrkatooni_store_render_product_card(WC_Product $product): string
{
    $product_id = $product->get_id();
    $title = get_the_title($product_id);
    $permalink = get_permalink($product_id);
    $image = get_the_post_thumbnail($product_id, 'woocommerce_thumbnail', [
        'class' => 'mk-home-product-card__image',
        'alt' => esc_attr($title),
        'loading' => 'lazy',
        'decoding' => 'async',
    ]);

    if (empty($image)) {
        $image = sprintf(
            '<img class="mk-home-product-card__image" src="%s" alt="%s" loading="lazy" decoding="async" />',
            esc_url(function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_thumbnail') : wc_placeholder_img_src()),
            esc_attr($title)
        );
    }

    return sprintf(
        '<article class="mk-home-product-card">'
            . '<a class="mk-home-product-card__media-link" href="%1$s" aria-label="%2$s">'
                . '<div class="mk-home-product-card__media">%3$s</div>'
            . '</a>'
            . '<div class="mk-home-product-card__body">'
                . '<a class="mk-home-product-card__title-link" href="%1$s">'
                    . '<h3 class="mk-home-product-card__title">%2$s</h3>'
                . '</a>'
                . '<div class="mk-home-product-card__price">%4$s</div>'
            . '</div>'
            . '<div class="mk-home-product-card__footer">%5$s</div>'
        . '</article>',
        esc_url($permalink),
        esc_html($title),
        $image,
        wp_kses_post($product->get_price_html()),
        mrkatooni_store_render_home_product_button($product)
    );
}

function mrkatooni_store_render_home_product_card(WC_Product $product): string
{
    return mrkatooni_store_render_product_card($product);
}

function mrkatooni_store_render_new_products_section(): string
{
    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => 6,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    ]);

    if (empty($products)) {
        return '';
    }

    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $items = '';

    foreach ($products as $product) {
        $items .= '<li class="mk-home-products__item">' . mrkatooni_store_render_product_card($product) . '</li>';
    }

    $section = '<section class="mk-home-products" aria-label="محصولات جدید" dir="rtl">'
        . '<div class="mk-home-products__head">'
            . '<div class="mk-home-products__titles">'
                . '<h2 class="mk-home-products__title"><span>محصولات</span> جدید</h2>'
            . '</div>'
            . '<a href="' . esc_url($shop_url) . '" class="mk-home-products__link">'
                . '<span>مشاهده همه</span>'
                . '<i class="mk-home-products__link-icon hgi hgi-stroke hgi-arrow-left-01" aria-hidden="true"></i>'
            . '</a>'
        . '</div>'
        . '<ul class="mk-home-products__list">'
            . $items
        . '</ul>'
    . '</section>';

    $section = preg_replace('#<p>\s*(<a[^>]+class="mk-home-products__link"[^>]*>.*?</a>)\s*</p>#s', '$1', $section);
    $section = preg_replace('#<p>\s*(<a[^>]+class="mk-home-product-card__media-link"[^>]*>.*?</a>)\s*</p>#s', '$1', $section);
    $section = preg_replace('#<p>\s*(<a[^>]+class="mk-home-product-card__title-link"[^>]*>.*?</a>)\s*</p>#s', '$1', $section);
    $section = preg_replace('#<p>\s*</p>#i', '', $section);
    $section = preg_replace('#<br\s*/?>#i', '', $section);

    return (string) $section;
}

function mrkatooni_store_render_search_results_section(): string
{
    if (!function_exists('wc_get_product')) {
        return '';
    }

    $search_query = trim((string) get_search_query(false));
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

    $header = '<section class="mk-product-search-results" dir="rtl" aria-labelledby="mk-product-search-title">'
        . '<div class="mk-product-search-results__head">'
            . '<div>'
                . '<p class="mk-product-search-results__eyebrow">جستجوی محصولات</p>'
                . '<h1 id="mk-product-search-title" class="mk-product-search-results__title">'
                    . ($search_query !== '' ? 'نتایج جستجو برای «' . esc_html($search_query) . '»' : 'جستجوی محصولات')
                . '</h1>'
            . '</div>'
            . '<a class="mk-product-search-results__shop-link" href="' . esc_url($shop_url) . '">مشاهده فروشگاه</a>'
        . '</div>';

    if ($search_query === '') {
        return $header
            . '<div class="mk-product-search-results__empty">برای پیدا کردن کتونی، بوت یا صندل موردنظرتان از کادر جستجو استفاده کنید.</div>'
            . '</section>';
    }

    $query = new WP_Query([
        'post_type'           => 'product',
        'post_status'         => 'publish',
        's'                   => $search_query,
        'posts_per_page'      => 12,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);

    if (!$query->have_posts()) {
        return $header
            . '<div class="mk-product-search-results__empty">محصولی با این عبارت پیدا نشد. دسته بندی ها یا فروشگاه را بررسی کنید.</div>'
            . '</section>';
    }

    $items = '';
    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product instanceof WC_Product) {
            $items .= '<li class="mk-home-products__item">' . mrkatooni_store_render_product_card($product) . '</li>';
        }
    }
    wp_reset_postdata();

    return $header
        . '<ul class="mk-home-products__list mk-product-search-results__list">' . $items . '</ul>'
        . '</section>';
}

function mrkatooni_store_register_blocks(): void
{
    if (!function_exists('register_block_type')) {
        return;
    }

    register_block_type('mrkatooni/new-products', [
        'render_callback' => 'mrkatooni_store_render_new_products_section',
    ]);

    register_block_type('mrkatooni/search-results', [
        'render_callback' => 'mrkatooni_store_render_search_results_section',
    ]);
}
add_action('init', 'mrkatooni_store_register_blocks');

function mrkatooni_store_limit_frontend_search_to_products(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return;
    }

    $query->set('post_type', 'product');

    $search_term = trim((string) $query->get('s'));
    if (preg_match('/(کتونی|کفش|اسنیکر|shoe|sneaker)/iu', $search_term)) {
        $query->set('mk_relaxed_product_search', true);
    }
}
add_action('pre_get_posts', 'mrkatooni_store_limit_frontend_search_to_products');

function mrkatooni_store_relax_generic_product_search(string $search, WP_Query $query): string
{
    if ($query->get('mk_relaxed_product_search')) {
        return '';
    }

    return $search;
}
add_filter('posts_search', 'mrkatooni_store_relax_generic_product_search', 10, 2);

function mrkatooni_store_translate_default_sorting_label(array $options): array
{
    if (isset($options['menu_order'])) {
        $options['menu_order'] = 'مرتب سازی پیش فرض';
    }

    return $options;
}
add_filter('woocommerce_catalog_orderby', 'mrkatooni_store_translate_default_sorting_label', 10, 1);
add_filter('woocommerce_default_catalog_orderby_options', 'mrkatooni_store_translate_default_sorting_label', 10, 1);

function mrkatooni_store_woocommerce_page_title(string $title): string
{
    if (function_exists('is_shop') && (is_shop() || is_product_category() || is_product_tag() || is_search())) {
        return 'فروشگاه';
    }

    return $title;
}
add_filter('woocommerce_page_title', 'mrkatooni_store_woocommerce_page_title');

function mrkatooni_store_disable_single_product_search_redirect($enabled): bool
{
    // WooCommerce by default redirects search → product page when there is exactly 1 result.
    // This theme uses a dedicated search results template, so we always keep the search results page.
    return false;
}
add_filter('woocommerce_redirect_single_search_result', 'mrkatooni_store_disable_single_product_search_redirect', 10, 1);

function mrkatooni_store_disable_canonical_redirect_on_search($redirect_url, $requested_url)
{
    if (is_search()) {
        return false;
    }

    return $redirect_url;
}
add_filter('redirect_canonical', 'mrkatooni_store_disable_canonical_redirect_on_search', 10, 2);

function mrkatooni_store_woocommerce_breadcrumb(array $crumbs): array
{
    foreach ($crumbs as &$crumb) {
        if (isset($crumb[0]) && 'Shop' === $crumb[0]) {
            $crumb[0] = 'فروشگاه';
        }
    }
    unset($crumb);

    return $crumbs;
}
add_filter('woocommerce_get_breadcrumb', 'mrkatooni_store_woocommerce_breadcrumb');

function mrkatooni_store_attribute_label(string $label, string $name): string
{
    if ('pa_size' === $name || 'size' === $name || strtolower($label) === 'size') {
        return 'سایز';
    }

    return $label;
}
add_filter('woocommerce_attribute_label', 'mrkatooni_store_attribute_label', 10, 2);

function mrkatooni_store_output_meta_description(): void
{
    if (is_admin()) {
        return;
    }

    if (is_search()) {
        $description = 'نتایج جستجوی محصولات مسترکتونی؛ کتونی مردانه، کتونی زنانه، بوت، نیم بوت، صندل و کراکس.';
    } elseif (function_exists('is_shop') && (is_shop() || is_product_category())) {
        $description = 'خرید کفش و کتونی از مسترکتونی با دسته بندی های مردانه، زنانه، بوت، نیم بوت، صندل و کراکس.';
    } else {
        $description = 'مسترکتونی فروشگاه تخصصی کفش، کتونی، بوت، نیم بوت، صندل و کراکس با تجربه خرید سریع و راحت.';
    }

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
}
add_action('wp_head', 'mrkatooni_store_output_meta_description', 1);

function mrkatooni_store_robots_txt(string $output, bool $public): string
{
    $home = home_url('/');

    return "User-agent: *\n"
        . "Disallow: /wp-admin/\n"
        . "Allow: /wp-admin/admin-ajax.php\n\n"
        . 'Sitemap: ' . esc_url_raw($home . 'wp-sitemap.xml') . "\n";
}
add_filter('robots_txt', 'mrkatooni_store_robots_txt', 10, 2);

function mrkatooni_store_get_test_product_ids(): array
{
    $keywords = ['sample', 'Sample', 'test', 'Test', 'نمونه'];
    $ids = [];

    foreach ($keywords as $keyword) {
        $query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'private'],
            's'              => $keyword,
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ]);

        if (!empty($query->posts)) {
            $ids = array_merge($ids, $query->posts);
        }
    }

    $ids = array_values(array_unique(array_filter($ids)));
    sort($ids);

    return $ids;
}

function mrkatooni_store_prepare_size_attribute(): array
{
    $sizes = ['40', '41', '42', '43', '44'];
    $taxonomy = wc_attribute_taxonomy_name('size');
    $attribute_id = 0;

    foreach (wc_get_attribute_taxonomies() as $tax) {
        if ('size' === $tax->attribute_name) {
            $attribute_id = (int) $tax->attribute_id;
            break;
        }
    }

    if (0 === $attribute_id) {
        $attribute_id = (int) wc_create_attribute([
            'name' => 'سایز',
            'slug' => 'size',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);

        delete_transient('wc_attribute_taxonomies');
        wc_register_attribute_taxonomies();
    }

    $term_ids = [];
    $term_slugs = [];
    foreach ($sizes as $size) {
        $term = term_exists($size, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($size, $taxonomy);
        }

        if (is_wp_error($term)) {
            continue;
        }

        $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
        $term_obj = get_term($term_id, $taxonomy);
        if (!$term_obj || is_wp_error($term_obj)) {
            continue;
        }

        $term_ids[] = $term_id;
        $term_slugs[$size] = $term_obj->slug;
    }

    return [
        'id' => $attribute_id,
        'taxonomy' => $taxonomy,
        'sizes' => $sizes,
        'term_ids' => $term_ids,
        'term_slugs' => $term_slugs,
    ];
}

function mrkatooni_store_seed_test_products(): void
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!function_exists('wc_get_attribute_taxonomies')) {
        return;
    }

    if (get_option('mk_test_products_seeded')) {
        return;
    }

    $product_ids = mrkatooni_store_get_test_product_ids();
    if (empty($product_ids)) {
        return;
    }

    $size_data = mrkatooni_store_prepare_size_attribute();
    if (empty($size_data['term_ids'])) {
        return;
    }

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            continue;
        }

        if (!$product->is_type('variable')) {
            wp_set_object_terms($product_id, 'variable', 'product_type');
            $product = new WC_Product_Variable($product_id);
        }

        $price = $product->get_regular_price();
        if ('' === $price) {
            $price = $product->get_price();
        }
        if ('' === $price) {
            $price = '0';
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id($size_data['id']);
        $attribute->set_name($size_data['taxonomy']);
        $attribute->set_options($size_data['term_ids']);
        $attribute->set_visible(true);
        $attribute->set_variation(true);

        $product->set_attributes([$size_data['taxonomy'] => $attribute]);
        $product->save();

        $children = $product->get_children();
        foreach ($children as $child_id) {
            wp_delete_post($child_id, true);
        }

        $out_of_stock_size = $size_data['sizes'][array_rand($size_data['sizes'])];
        foreach ($size_data['sizes'] as $size) {
            if (empty($size_data['term_slugs'][$size])) {
                continue;
            }

            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_attributes([
                $size_data['taxonomy'] => $size_data['term_slugs'][$size],
            ]);
            $variation->set_regular_price($price);
            $variation->set_price($price);
            $variation->set_manage_stock(false);
            $variation->set_stock_status($size === $out_of_stock_size ? 'outofstock' : 'instock');
            $variation->save();
        }

        WC_Product_Variable::sync($product_id);
        wc_delete_product_transients($product_id);
    }

    update_option('mk_test_products_seeded', current_time('mysql'), true);
}
add_action('admin_init', 'mrkatooni_store_seed_test_products');

function mrkatooni_store_repair_sample_variations(): void
{
    if (
        !is_admin()
        || !current_user_can('manage_options')
        || !isset($_GET['mk_repair_sample_products'])
        || !function_exists('wc_get_products')
        || get_option('mk_sample_variations_repaired_20260509')
    ) {
        return;
    }

    $product_ids = mrkatooni_store_get_test_product_ids();
    if (empty($product_ids)) {
        update_option('mk_sample_variations_repaired_20260509', current_time('mysql'), true);
        return;
    }

    $size_data = mrkatooni_store_prepare_size_attribute();
    if (empty($size_data['term_ids'])) {
        return;
    }

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            continue;
        }

        $name = $product->get_name();
        if (!preg_match('/Sample Shoe\s+(\d+)/i', $name, $matches)) {
            continue;
        }

        if (!$product->is_type('variable')) {
            wp_set_object_terms($product_id, 'variable', 'product_type');
            $product = new WC_Product_Variable($product_id);
        }

        $base_price = 1240000 + ((int) $matches[1] * 10000);

        $attribute = new WC_Product_Attribute();
        $attribute->set_id($size_data['id']);
        $attribute->set_name($size_data['taxonomy']);
        $attribute->set_options($size_data['term_ids']);
        $attribute->set_visible(true);
        $attribute->set_variation(true);

        $product->set_attributes([$size_data['taxonomy'] => $attribute]);
        $product->save();

        foreach ($product->get_children() as $child_id) {
            wp_delete_post($child_id, true);
        }

        foreach (array_values($size_data['sizes']) as $index => $size) {
            if (empty($size_data['term_slugs'][$size])) {
                continue;
            }

            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_attributes([
                $size_data['taxonomy'] => $size_data['term_slugs'][$size],
            ]);
            $variation->set_regular_price((string) ($base_price + ($index * 1000)));
            $variation->set_price((string) ($base_price + ($index * 1000)));
            $variation->set_manage_stock(false);
            $variation->set_stock_status('instock');
            $variation->save();
        }

        WC_Product_Variable::sync($product_id);
        wc_delete_product_transients($product_id);
    }

    update_option('mk_sample_variations_repaired_20260509', current_time('mysql'), true);
}
add_action('admin_init', 'mrkatooni_store_repair_sample_variations');

add_filter('woocommerce_product_related_products_heading', 'mrkatooni_store_related_products_heading');
function mrkatooni_store_related_products_heading(): string
{
    return 'محصولات پیشنهادی مشابه';
}

add_filter('woocommerce_output_related_products_args', 'mrkatooni_store_related_products_args');
function mrkatooni_store_related_products_args(array $args): array
{
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;

    return $args;
}

function mrkatooni_store_output_related_products(): void
{
    global $product;

    if (!$product) {
        return;
    }

    $product_ids = wc_get_related_products($product->get_id(), 4);

    if (count($product_ids) < 4) {
        $fallback = wc_get_products([
            'status'  => 'publish',
            'limit'   => 4,
            'exclude' => [$product->get_id()],
            'orderby' => 'rand',
            'return'  => 'ids',
        ]);

        $product_ids = array_values(array_unique(array_merge($product_ids, $fallback)));
        $product_ids = array_slice($product_ids, 0, 4);
    }

    if (empty($product_ids)) {
        return;
    }

    $related_products = wc_get_products([
        'include' => $product_ids,
        'limit'   => 4,
        'orderby' => 'include',
    ]);

    if (empty($related_products)) {
        return;
    }

    $previous_product = $GLOBALS['product'] ?? null;
    ?>
    <section class="related products">
        <h2>محصولات پیشنهادی مشابه</h2>
        <?php woocommerce_product_loop_start(); ?>
            <?php foreach ($related_products as $related_product) : ?>
                <?php
                $GLOBALS['product'] = $related_product;
                setup_postdata(get_post($related_product->get_id()));
                wc_get_template_part('content', 'product');
                ?>
            <?php endforeach; ?>
        <?php woocommerce_product_loop_end(); ?>
    </section>
    <?php
    $GLOBALS['product'] = $previous_product;
    wp_reset_postdata();
}

function mrkatooni_store_set_default_logo(): void
{
    if (get_theme_mod('custom_logo')) {
        return;
    }

    $logo_path = get_template_directory() . '/assets/images/logo.png';
    if (!file_exists($logo_path)) {
        return;
    }

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['error'])) {
        return;
    }

    $filename = wp_unique_filename($upload_dir['path'], basename($logo_path));
    $target = trailingslashit($upload_dir['path']) . $filename;

    if (!copy($logo_path, $target)) {
        return;
    }

    $filetype = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
        'post_status'    => 'inherit',
    ], $target);

    if (is_wp_error($attachment_id)) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $target);
    wp_update_attachment_metadata($attachment_id, $metadata);

    set_theme_mod('custom_logo', (int) $attachment_id);
}
add_action('after_switch_theme', 'mrkatooni_store_set_default_logo');

// Show prices in Toman (divide by 10) for product and cart displays, keep checkout in Rial.
function mrk_format_price_to_toman($price_html, $product = null)
{
    if (is_admin() || is_checkout()) {
        return $price_html;
    }

    $format_toman = static function ($rial): string {
        $toman = (int) floor((float) $rial / 10);

        return number_format_i18n($toman) . ' تومان';
    };

    if ($product instanceof WC_Product) {
        if ($product->is_type('variable')) {
            $min_price = $product->get_variation_price('min', true);
            $max_price = $product->get_variation_price('max', true);

            if ('' === $min_price) {
                return $price_html;
            }

            $price_text = ((float) $min_price === (float) $max_price)
                ? $format_toman($min_price)
                : $format_toman($min_price) . ' تا ' . $format_toman($max_price);
        } else {
            $price = $product->get_price();

            if ('' === $price) {
                return $price_html;
            }

            $price_text = $format_toman($price);
        }

        return '<span class="woocommerce-Price-amount amount"><bdi>' . esc_html($price_text) . '</bdi></span>';
    }

    return preg_replace_callback('/[0-9][0-9,\.\s]*/', static function ($matches) use ($format_toman) {
        $digits = preg_replace('/[^0-9]/', '', $matches[0]);

        return '' === $digits ? $matches[0] : $format_toman($digits);
    }, $price_html);
}

add_filter( 'woocommerce_get_price_html', 'mrk_format_price_to_toman', 100, 2 );
add_filter( 'woocommerce_cart_item_price', 'mrk_format_price_to_toman', 100 );
add_filter( 'woocommerce_cart_item_subtotal', 'mrk_format_price_to_toman', 100 );


