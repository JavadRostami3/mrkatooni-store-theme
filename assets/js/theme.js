(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var header = document.querySelector('.mk-header');
    if (header) {
      var syncHeaderState = function () {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
      };

      syncHeaderState();
      window.addEventListener('scroll', syncHeaderState, { passive: true });
    }

    var searchForm = document.getElementById('mk-header-search-form');
    var searchShell = searchForm ? searchForm.closest('.mk-search-shell') : null;
    var searchToggle = searchForm ? searchForm.querySelector('.mk-header-search__trigger') : null;
    var searchClose = searchForm ? searchForm.querySelector('.mk-header-search__close') : null;
    var searchInput = document.getElementById('mk-header-search-input');

    if (searchToggle && searchForm) {
      var openSearch = function () {
        searchForm.classList.add('is-open');
        if (searchShell) {
          searchShell.classList.add('is-open');
        }
        searchToggle.setAttribute('aria-expanded', 'true');
        if (searchInput) {
          searchInput.focus();
        }
      };

      var closeSearch = function () {
        searchForm.classList.remove('is-open');
        if (searchShell) {
          searchShell.classList.remove('is-open');
        }
        searchToggle.setAttribute('aria-expanded', 'false');
      };

      searchToggle.addEventListener('click', function () {
        if (searchForm.classList.contains('is-open')) {
          closeSearch();
          return;
        }

        openSearch();
      });

      if (searchClose) {
        searchClose.addEventListener('click', function () {
          closeSearch();
        });
      }

      document.addEventListener('click', function (event) {
        if (!searchForm.contains(event.target) && !searchToggle.contains(event.target)) {
          closeSearch();
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && searchForm.classList.contains('is-open')) {
          closeSearch();
          searchToggle.focus();
        }
      });
    }

    var desktopProductsItem = document.querySelector('.mk-main-menu__has-submenu');
    var desktopProductsToggle = document.querySelector('.mk-main-menu__trigger');
    if (desktopProductsItem && desktopProductsToggle) {
      desktopProductsToggle.addEventListener('click', function () {
        var isOpen = desktopProductsItem.classList.toggle('is-open');
        desktopProductsToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      document.addEventListener('click', function (event) {
        if (!desktopProductsItem.contains(event.target)) {
          desktopProductsItem.classList.remove('is-open');
          desktopProductsToggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    var mobileNav = document.querySelector('.mk-mobile-nav');
    if (mobileNav) {
      var navItems = mobileNav.querySelectorAll('.mk-mobile-nav__item[data-nav-index]');
      var currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
      var resolvedIndex = 0;

      if (currentPath.indexOf('/shop') === 0 || currentPath.indexOf('/product') === 0) {
        resolvedIndex = 1;
      }
      if (currentPath.indexOf('/cart') === 0) {
        resolvedIndex = 2;
      }

      navItems.forEach(function (item) {
        var itemIndex = Number(item.getAttribute('data-nav-index'));
        item.classList.toggle('is-active', itemIndex === resolvedIndex);
      });

      navItems.forEach(function (item) {
        item.addEventListener('click', function () {
          var itemIndex = Number(item.getAttribute('data-nav-index'));
          navItems.forEach(function (other) {
            other.classList.remove('is-active');
          });
          item.classList.add('is-active');
        });
      });
    }

    var mobileCategoriesToggle = document.querySelector('.mk-mobile-nav__categories-toggle');
    var mobileCategories = document.getElementById('mk-mobile-categories');
    if (mobileCategoriesToggle && mobileCategories) {
      mobileCategoriesToggle.addEventListener('click', function (event) {
        event.preventDefault();
        var isOpen = mobileCategories.classList.toggle('is-open');
        mobileCategoriesToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      document.addEventListener('click', function (event) {
        if (!mobileCategories.contains(event.target) && !mobileCategoriesToggle.contains(event.target)) {
          mobileCategories.classList.remove('is-open');
          mobileCategoriesToggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Convert displayed prices to Toman on product and cart pages (frontend-only)
    var convertPricesToToman = function () {
      try {
        var path = window.location.pathname || '';
        if (path.indexOf('/checkout') === 0 || path.indexOf('/my-account') === 0) return;
        
        var priceEls = document.querySelectorAll('.woocommerce-Price-amount bdi');
        priceEls.forEach(function (el) {
          if (el.getAttribute('data-converted') === 'true') return;
          var clone = el.cloneNode(true);
          var sc = clone.querySelector('.woocommerce-Price-currencySymbol');
          if (sc) sc.remove();
          
          var txt = clone.textContent || '';
          if (txt.indexOf('\u062A\u0648\u0645\u0627\u0646') !== -1) return;

          var digits = txt.replace(/[^0-9]/g, '');
          if (!digits) return;
          
          var rial = parseInt(digits, 10);
          var toman = Math.floor(rial / 10);
          var formatted = toman.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
          
          el.innerHTML = formatted + '&nbsp;<span class="woocommerce-Price-currencySymbol">\u062A\u0648\u0645\u0627\u0646</span>';
          el.setAttribute('data-converted', 'true');
        });
      } catch (e) {
        console.error(e);
      }
    };

    // run once and also observe DOM changes to catch dynamic updates (e.g., cart table updates)
    convertPricesToToman();
    try {
      var observer = new MutationObserver(function () {
        convertPricesToToman();
      });
      var main = document.querySelector('main') || document.body;
      observer.observe(main, { childList: true, subtree: true });
    } catch (e) {
      // ignore
    }
  });
})();
