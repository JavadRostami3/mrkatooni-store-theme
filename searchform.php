<?php
defined('ABSPATH') || exit;

$aria_label = 'جستجو در سایت';
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php echo esc_html($aria_label); ?></span>
        <input
            type="search"
            class="search-field"
            placeholder="<?php echo esc_attr('جستجو…'); ?>"
            value="<?php echo get_search_query(); ?>"
            name="s"
        />
    </label>
    <button type="submit" class="search-submit"><?php echo esc_html('جستجو'); ?></button>
</form>

