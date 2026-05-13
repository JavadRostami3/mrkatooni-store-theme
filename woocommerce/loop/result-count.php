<?php
/**
 * Result Count
 *
 * Override of WooCommerce template to avoid global gettext filters.
 */
defined('ABSPATH') || exit;

$total    = (int) wc_get_loop_prop('total');
$per_page = (int) wc_get_loop_prop('per_page');
$current  = (int) wc_get_loop_prop('current_page');

if ($total <= 0) {
    return;
}

if (1 === $total) {
    $message = 'نمایش تنها محصول';
} elseif ($total <= $per_page || -1 === $per_page) {
    $message = sprintf('نمایش همه %d محصول', $total);
} else {
    $first = ($per_page * $current) - $per_page + 1;
    $last  = min($total, $per_page * $current);
    $message = sprintf('نمایش %d تا %d از %d محصول', $first, $last, $total);
}

?>

<p class="woocommerce-result-count"><?php echo esc_html($message); ?></p>

