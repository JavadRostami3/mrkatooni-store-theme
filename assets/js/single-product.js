(function($) {
  function setupProductTabs() {
    $('.mk-product-tabs').each(function() {
      var $tabs = $(this);

      $tabs.on('click', '.mk-product-tabs__button', function() {
        var $button = $(this);
        var target = $button.attr('aria-controls');

        if (!target) {
          return;
        }

        $tabs.find('.mk-product-tabs__button')
          .removeClass('is-active')
          .attr('aria-selected', 'false');

        $button
          .addClass('is-active')
          .attr('aria-selected', 'true');

        $tabs.find('.mk-product-tabs__panel')
          .removeClass('is-active')
          .attr('hidden', true);

        $('#' + target)
          .addClass('is-active')
          .removeAttr('hidden');
      });
    });
  }

  function setupVariationSwatches() {
    $('.mk-product-purchase__form table.variations select').each(function() {
      var $select = $(this);

      if ($select.prev('.mk-variation-swatches').length) {
        return;
      }

      var $options = $select.find('option').filter(function() {
        return $(this).val() !== '';
      });

      if (!$options.length) {
        return;
      }

      var $swatches = $('<div class="mk-variation-swatches"></div>');

      $options.each(function() {
        var $option = $(this);
        var $button = $('<button type="button" class="mk-variation-swatch"></button>');

        $button.text($option.text());
        $button.attr('data-value', $option.val());

        if ($option.val() === $select.val()) {
          $button.addClass('is-active');
        }

        $swatches.append($button);
      });

      $select.before($swatches);

      $swatches.on('click', '.mk-variation-swatch', function() {
        var value = $(this).attr('data-value');

        $select.val(value).trigger('change');
      });

      $select.on('change', function() {
        var value = $select.val();

        $swatches.find('.mk-variation-swatch').each(function() {
          $(this).toggleClass('is-active', $(this).attr('data-value') === value);
        });
      });
    });

    $(document).on('click', '.reset_variations', function() {
      $('.mk-variation-swatch').removeClass('is-active');
    });
  }

  function setupQuantityControls() {
    $('form.cart').find('.quantity input.qty').each(function() {
      var $input = $(this);

      if ($input.closest('.mk-qty').length) {
        return;
      }

      $input.wrap('<div class="mk-qty"></div>');
      $input.before('<button type="button" class="mk-qty-btn" data-dir="minus" aria-label="کم کردن تعداد">-</button>');
      $input.after('<button type="button" class="mk-qty-btn" data-dir="plus" aria-label="زیاد کردن تعداد">+</button>');
    });
  }

  function updateQuantity($input, direction) {
    var step = parseFloat($input.attr('step')) || 1;
    var min = parseFloat($input.attr('min')) || 1;
    var maxAttr = $input.attr('max');
    var max = maxAttr ? parseFloat(maxAttr) : Number.POSITIVE_INFINITY;
    var current = parseFloat($input.val()) || min;
    var next = current + (direction === 'plus' ? step : -step);

    if (next < min) {
      next = min;
    }

    if (next > max) {
      next = max;
    }

    $input.val(next).trigger('change');
  }

  $(function() {
    setupProductTabs();
    setupVariationSwatches();
    setupQuantityControls();

    $(document).on('click', '.mk-qty-btn', function() {
      var $input = $(this).siblings('input.qty');

      if ($input.length) {
        updateQuantity($input, $(this).data('dir'));
      }
    });

    $(document.body).on('woocommerce_variation_has_changed', function() {
      setupVariationSwatches();
    });
  });
})(jQuery);
