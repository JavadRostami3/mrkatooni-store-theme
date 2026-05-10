<?php
/**
 * Product content layout used by WooCommerce legacy-template block.
 */

defined('ABSPATH') || exit;

global $product;

if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}

if (!function_exists('mrkatooni_store_product_attribute_rows')) {
    function mrkatooni_store_product_attribute_rows(WC_Product $product, int $limit = 0): array
    {
        $rows = [];

        foreach ($product->get_attributes() as $attribute) {
            if (!$attribute->get_visible()) {
                continue;
            }

            $label = wc_attribute_label($attribute->get_name(), $product);
            $values = [];

            if ($attribute->is_taxonomy()) {
                $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                $values = is_wp_error($terms) ? [] : $terms;
            } else {
                $values = $attribute->get_options();
            }

            $value = trim(wp_strip_all_tags(implode('، ', array_filter(array_map('wc_clean', $values)))));

            if ('' === $value) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }
}

$attribute_rows = mrkatooni_store_product_attribute_rows($product, 6);
$review_count = $product->get_review_count();
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('mk-single-product'); ?>>
    <div class="mk-product-hero">
        <section class="mk-product-purchase" aria-label="خرید محصول">
            <div class="mk-product-purchase__stock">
                <span class="mk-product-purchase__stock-icon" aria-hidden="true"></span>
                <span><?php echo $product->is_in_stock() ? esc_html__('موجود در انبار', 'mrkatooni-store') : esc_html__('ناموجود', 'mrkatooni-store'); ?></span>
            </div>

            <div class="mk-product-purchase__price-row">
                <span class="mk-product-purchase__price-label"><?php esc_html_e('قیمت کالا', 'mrkatooni-store'); ?></span>
                <div class="mk-product-purchase__price"><?php woocommerce_template_single_price(); ?></div>
            </div>

            <div class="mk-product-purchase__form">
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </section>

        <section class="mk-product-details" aria-label="اطلاعات محصول">
            <?php woocommerce_breadcrumb([
                'delimiter'   => '<span class="mk-breadcrumb__sep">/</span>',
                'wrap_before' => '<nav class="mk-breadcrumb woocommerce-breadcrumb" aria-label="مسیر راهنما">',
                'wrap_after'  => '</nav>',
                'before'      => '',
                'after'       => '',
            ]); ?>

            <?php mrkatooni_store_single_product_brand_label(); ?>
            <?php woocommerce_template_single_title(); ?>

            <?php if (!empty($attribute_rows)) : ?>
                <div class="mk-product-spec-card" aria-label="ویژگی‌های محصول">
                    <table class="mk-product-spec-table">
                        <tbody>
                            <?php foreach ($attribute_rows as $row) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html($row['label']); ?></th>
                                    <td><?php echo esc_html($row['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mk-product-meta-lines">
                <?php echo wc_get_product_category_list($product->get_id(), '، ', '<div><strong>برچسب:</strong> ', '</div>'); ?>
                <?php if ($product->get_sku()) : ?>
                    <div><strong>شناسه کالا</strong> <?php echo esc_html($product->get_sku()); ?></div>
                <?php endif; ?>
                <?php if ($product->get_attribute('pa_country') || $product->get_attribute('country')) : ?>
                    <div><?php echo esc_html($product->get_attribute('pa_country') ?: $product->get_attribute('country')); ?></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="mk-product-gallery" aria-label="گالری محصول">
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </section>
    </div>

    <div class="mk-product-after-summary">
        <section class="mk-product-tabs" aria-label="جزئیات محصول">
            <div class="mk-product-tabs__nav" role="tablist" aria-label="اطلاعات محصول">
                <button class="mk-product-tabs__button is-active" type="button" role="tab" aria-selected="true" aria-controls="mk-tab-description" id="mk-tab-description-button">توضیحات</button>
                <button class="mk-product-tabs__button" type="button" role="tab" aria-selected="false" aria-controls="mk-tab-additional" id="mk-tab-additional-button">توضیحات تکمیلی</button>
                <button class="mk-product-tabs__button" type="button" role="tab" aria-selected="false" aria-controls="mk-tab-reviews" id="mk-tab-reviews-button">نظرات (<?php echo esc_html((string) $review_count); ?>)</button>
            </div>

            <div class="mk-product-tabs__panel is-active" id="mk-tab-description" role="tabpanel" aria-labelledby="mk-tab-description-button">
                <h2><?php printf(esc_html__('کفش خاص %s', 'mrkatooni-store'), esc_html($product->get_name())); ?></h2>
                <div class="mk-product-tabs__content">
                    <?php
                    $description = apply_filters('the_content', get_post_field('post_content', $product->get_id()));
                    $short_description = apply_filters('woocommerce_short_description', $product->get_short_description());
                    echo $description ? wp_kses_post($description) : wp_kses_post(wpautop($short_description));
                    ?>
                </div>
            </div>

            <div class="mk-product-tabs__panel" id="mk-tab-additional" role="tabpanel" aria-labelledby="mk-tab-additional-button" hidden>
                <?php wc_display_product_attributes($product); ?>
            </div>

            <div class="mk-product-tabs__panel" id="mk-tab-reviews" role="tabpanel" aria-labelledby="mk-tab-reviews-button" hidden>
                <?php comments_template(); ?>
            </div>
        </section>

        <?php mrkatooni_store_output_related_products(); ?>
    </div>
</div>

<?php do_action('woocommerce_after_single_product'); ?>
