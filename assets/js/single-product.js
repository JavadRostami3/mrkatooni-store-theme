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

  function setupGalleryThumbScroller() {
    $('.mk-product-gallery .flex-control-thumbs').each(function() {
      var $thumbs = $(this);

      if ($thumbs.parent('.mk-gallery-thumbs__viewport').length) {
        return;
      }

      var $items = $thumbs.children('li');

      if ($items.length <= 4) {
        $thumbs.wrap('<div class="mk-gallery-thumbs is-static"><div class="mk-gallery-thumbs__viewport"></div></div>');
        return;
      }

      var $wrapper = $('<div class="mk-gallery-thumbs"></div>');
      var $prev = $('<button type="button" class="mk-gallery-thumbs__button" data-dir="prev" aria-label="تصویر قبلی"><i class="hgi hgi-stroke hgi-arrow-left-01" aria-hidden="true"></i></button>');
      var $next = $('<button type="button" class="mk-gallery-thumbs__button" data-dir="next" aria-label="تصویر بعدی"><i class="hgi hgi-stroke hgi-arrow-right-01" aria-hidden="true"></i></button>');
      var $viewport = $('<div class="mk-gallery-thumbs__viewport"></div>');

      $thumbs.wrap($viewport);
      $viewport = $thumbs.parent();
      $viewport.wrap($wrapper);
      $wrapper = $viewport.parent();
      $wrapper.prepend($prev);
      $wrapper.append($next);

      function getStep() {
        var $firstItem = $thumbs.children('li').first();

        if (!$firstItem.length) {
          return 0;
        }

        return $firstItem.outerWidth(true) * 4;
      }

      function maxScroll() {
        var element = $viewport.get(0);
        return Math.max(0, element.scrollWidth - element.clientWidth);
      }

      function syncButtons() {
        var current = Math.ceil($viewport.scrollLeft());
        var max = maxScroll();

        $prev.prop('disabled', current <= 0);
        $next.prop('disabled', current >= max - 2);
      }

      function scrollByStep(direction) {
        var element = $viewport.get(0);
        var step = getStep();

        if (!element || !step) {
          return;
        }

        element.scrollBy({
          left: direction === 'next' ? step : -step,
          behavior: 'smooth'
        });
      }

      $prev.on('click', function() {
        scrollByStep('prev');
      });

      $next.on('click', function() {
        scrollByStep('next');
      });

      $viewport.on('scroll', syncButtons);
      $(window).on('resize', syncButtons);
      syncButtons();
    });
  }

  function swapGalleryImage(thumb) {
      var $thumb = $(thumb);
      var index = $thumb.closest('li').index();
      var $gallery = $thumb.closest('.mk-product-gallery');
      var $slides = $gallery.find('.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image');
      var $targetSlide = $slides.eq(index);
      var $targetImage = $targetSlide.find('img').first();
      var $mainSlide = $slides.first();
      var $mainImage = $mainSlide.find('img').first();
      var fullSrc = $thumb.attr('data-full') ||
        $thumb.attr('data-large_image') ||
        $targetImage.attr('data-large_image') ||
        $targetImage.attr('data-src') ||
        $targetSlide.find('a').first().attr('href') ||
        $thumb.attr('src');
      var thumbSrcset = $thumb.attr('srcset') || '';
      var targetSrc = $targetImage.attr('src') || $targetImage.attr('data-src') || fullSrc;
      var targetSrcset = $targetImage.attr('srcset') || thumbSrcset;

      if (!$mainImage.length || !targetSrc) {
        return;
      }

      $mainImage.attr({
        src: targetSrc,
        srcset: targetSrcset,
        sizes: $targetImage.attr('sizes') || $thumb.attr('sizes') || $mainImage.attr('sizes') || '',
        alt: $targetImage.attr('alt') || $thumb.attr('alt') || $mainImage.attr('alt') || ''
      });

      $mainSlide.find('a').first().attr('href', $targetSlide.find('a').first().attr('href') || fullSrc || targetSrc);
      $mainSlide.addClass('flex-active-slide').siblings('.woocommerce-product-gallery__image').removeClass('flex-active-slide');
      $gallery.find('.flex-control-thumbs img').removeClass('flex-active');
      $thumb.addClass('flex-active');
  }

  function setupGalleryThumbSwap() {
    if (window.mkGalleryThumbSwapReady) {
      return;
    }

    window.mkGalleryThumbSwapReady = true;

    document.addEventListener('click', function(event) {
      var thumb = event.target.closest('.mk-product-gallery .flex-control-thumbs img');

      if (!thumb) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
      swapGalleryImage(thumb);
    }, true);

    $(document).on('keydown', '.mk-product-gallery .flex-control-thumbs img', function(event) {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }

      event.preventDefault();
      swapGalleryImage(this);
    });
  }

  $(function() {
    setupProductTabs();
    setupVariationSwatches();
    setupQuantityControls();
    setupGalleryThumbScroller();
    setupGalleryThumbSwap();

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
