/* ===========================================================
   RISER — main.js
   Mobile nav, scroll-reveal, AJAX cart actions, toasts, PDP UI
   =========================================================== */

(function () {
  'use strict';

  /* ---------- Animated pill navbar: sliding indicator ---------- */
  const navPill = document.getElementById('navPill');
  const navIndicator = document.getElementById('navIndicator');
  const navPillLinks = navPill ? Array.from(navPill.querySelectorAll('.nav__links a')) : [];

  if (navPill && navIndicator && navPillLinks.length) {
    function highlight(link, animate) {
      navPillLinks.forEach(a => a.classList.remove('is-lit'));
      link.classList.add('is-lit');
      if (!animate) navIndicator.style.transition = 'none';
      navIndicator.style.width = link.offsetWidth + 'px';
      navIndicator.style.transform = 'translateX(' + link.offsetLeft + 'px)';
      if (!animate) {
        void navIndicator.offsetHeight; // force reflow before re-enabling transition
        navIndicator.style.transition = '';
      }
    }

    function restingLink() {
      return navPill.querySelector('.nav__links a.active');
    }

    function rest(animate) {
      const active = restingLink();
      if (active) {
        highlight(active, animate);
      } else {
        navPillLinks.forEach(a => a.classList.remove('is-lit'));
        if (!animate) navIndicator.style.transition = 'none';
        navIndicator.style.width = '0';
        if (!animate) { void navIndicator.offsetHeight; navIndicator.style.transition = ''; }
      }
    }

    // Position instantly on load (no slide-in from the corner), then reveal
    if (navPill.offsetParent !== null) {
      rest(false);
      requestAnimationFrame(() => navPill.classList.add('is-ready'));
    }

    navPillLinks.forEach(link => {
      link.addEventListener('mouseenter', () => highlight(link, true));
      link.addEventListener('focus', () => highlight(link, true));
    });
    navPill.addEventListener('mouseleave', () => rest(true));

    let navResizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(navResizeTimer);
      navResizeTimer = setTimeout(() => {
        if (navPill.offsetParent !== null) {
          rest(false);
          navPill.classList.add('is-ready');
        }
      }, 150);
    });
  }

  /* ---------- Glass header on scroll ---------- */
  const siteHeader = document.querySelector('.site-header');
  if (siteHeader) {
    const toggleHeaderGlass = () => {
      siteHeader.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    toggleHeaderGlass();
    window.addEventListener('scroll', toggleHeaderGlass, { passive: true });
  }

  /* ---------- Mobile full-screen menu ---------- */
  const navToggle = document.getElementById('navToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
  const mobileMenuClose = document.getElementById('mobileMenuClose');

  if (navToggle && mobileMenu && mobileMenuOverlay) {
    function openMobileMenu() {
      mobileMenu.classList.add('open');
      mobileMenuOverlay.classList.add('open');
      mobileMenu.setAttribute('aria-hidden', 'false');
      navToggle.classList.add('active');
      navToggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('menu-open');
    }
    function closeMobileMenu() {
      mobileMenu.classList.remove('open');
      mobileMenuOverlay.classList.remove('open');
      mobileMenu.setAttribute('aria-hidden', 'true');
      navToggle.classList.remove('active');
      navToggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('menu-open');
    }

    navToggle.addEventListener('click', () => {
      mobileMenu.classList.contains('open') ? closeMobileMenu() : openMobileMenu();
    });
    if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMobileMenu);
    mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMobileMenu();
    });
    mobileMenu.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', closeMobileMenu);
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 880) closeMobileMenu();
    });
  }

  /* ---------- Scroll reveal (fade-up) ---------- */
  const revealEls = document.querySelectorAll('.fade-up');
  if (revealEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(el => observer.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  /* ---------- Toast ---------- */
  let toastTimer = null;
  window.showToast = function (message) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
  };

  /* ---------- Update cart badge ---------- */
  function setCartBadge(count, animate) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';

    if (animate && count > 0) {
      const cartLink = badge.closest('.cart-link');
      // restart the animation even if it's already mid-way through from a
      // rapid second add-to-cart click
      badge.classList.remove('just-added');
      if (cartLink) cartLink.classList.remove('just-added');
      void badge.offsetWidth; // force reflow so the animation restarts
      badge.classList.add('just-added');
      if (cartLink) cartLink.classList.add('just-added');
      badge.addEventListener('animationend', () => badge.classList.remove('just-added'), { once: true });
      if (cartLink) {
        cartLink.addEventListener('animationend', () => cartLink.classList.remove('just-added'), { once: true });
      }
    }
  }

  /* ---------- Product card: hover color swatches + quick add ---------- */
  document.querySelectorAll('.card__swatches').forEach(group => {
    const card = group.closest('.card');
    const form = card ? card.querySelector('.card__quick-add') : null;
    const variantInput = form ? form.querySelector('.js-quick-variant') : null;
    const quickBtn = form ? form.querySelector('.card__quick-btn') : null;

    function applySwatch(swatch) {
      group.querySelectorAll('.card__swatch').forEach(s => s.classList.remove('selected'));
      swatch.classList.add('selected');
      if (variantInput) variantInput.value = swatch.dataset.variantId;
      if (form) form.dataset.needsSize = swatch.dataset.multiSize;
      if (quickBtn) {
        const label = quickBtn.querySelector('.btn-label');
        const text = swatch.dataset.multiSize === '1' ? 'Select Size' : 'Add to Cart';
        if (label) label.textContent = text; else quickBtn.textContent = text;
      }
    }

    group.querySelectorAll('.card__swatch').forEach(swatch => {
      swatch.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation(); // don't trigger the card's full-card link
        applySwatch(swatch);
      });
    });
  });

  // Runs before the generic .js-add-cart handler below: if the selected
  // color needs a size pick, send the shopper to the product page instead
  // of guessing which size to add.
  document.querySelectorAll('.card__quick-add').forEach(form => {
    form.addEventListener('submit', function (e) {
      if (form.dataset.needsSize === '1') {
        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.href = form.dataset.productUrl;
      }
    });
  });

  /* ---------- AJAX Add to Cart (grid "quick add" buttons) ---------- */
  document.querySelectorAll('.js-add-cart').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(form);
      if (window.RISER_CSRF) formData.append('csrf_token', window.RISER_CSRF);
      const btn = form.querySelector('button[type="submit"]');
      const label = btn ? btn.querySelector('.btn-label') : null;
      const originalText = label ? label.textContent : (btn ? btn.textContent : '');
      if (btn) {
        if (label) label.textContent = 'Adding...'; else btn.textContent = 'Adding...';
        btn.disabled = true;
      }

      fetch((window.RISER_BASE||'') + '/cart-actions.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            setCartBadge(data.cartCount, true);
            showToast(data.message || 'Added to cart');
          } else {
            showToast(data.message || 'Could not add to cart');
          }
        })
        .catch(() => showToast('Network error — please try again'))
        .finally(() => {
          if (btn) {
            if (label) label.textContent = originalText; else btn.textContent = originalText;
            btn.disabled = false;
          }
        });
    });
  });

  /* ---------- Cart page: quantity update / remove via AJAX ---------- */
  document.querySelectorAll('.js-cart-qty').forEach(input => {
    input.addEventListener('change', function () {
      const cartKey = this.dataset.cartKey;
      const qty = Math.max(0, parseInt(this.value, 10) || 0);
      updateCartLine(cartKey, qty);
    });
  });

  document.querySelectorAll('.js-qty-inc, .js-qty-dec').forEach(btn => {
    btn.addEventListener('click', function () {
      const wrapper = this.closest('.qty-stepper');
      const input = wrapper.querySelector('input');
      let val = parseInt(input.value, 10) || 1;
      val = this.classList.contains('js-qty-inc') ? val + 1 : Math.max(1, val - 1);
      const max = parseInt(input.dataset.max || '99', 10);
      if (val > max) val = max;
      input.value = val;
      if (input.classList.contains('js-cart-qty')) {
        updateCartLine(input.dataset.cartKey, val);
      }
    });
  });

  function updateCartLine(cartKey, qty) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('cart_key', cartKey);
    formData.append('qty', qty);
    if (window.RISER_CSRF) formData.append('csrf_token', window.RISER_CSRF);

    fetch((window.RISER_BASE||'') + '/cart-actions.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          window.location.reload();
        } else {
          showToast(data.message || 'Could not update cart');
        }
      })
      .catch(() => showToast('Network error — please try again'));
  }

  document.querySelectorAll('.js-cart-remove').forEach(btn => {
    btn.addEventListener('click', function () {
      updateCartLine(this.dataset.cartKey, 0);
    });
  });

  /* ---------- Product Detail Page: variant swatches ---------- */
  const pdpForm = document.getElementById('pdpForm');
  if (pdpForm) {
    const sizeSwatches = pdpForm.querySelectorAll('.js-size-swatch');
    const colorSwatches = pdpForm.querySelectorAll('.js-color-swatch');
    const variantInput = pdpForm.querySelector('#selectedVariantId');
    const stockNote = document.getElementById('stockNote');
    const addBtn = document.getElementById('pdpAddBtn');
    const qtyInput = pdpForm.querySelector('.js-pdp-qty');

    function selectSwatch(group, el) {
      group.forEach(s => s.classList.remove('selected'));
      el.classList.add('selected');
    }

    function currentSelection() {
      const sizeEl = pdpForm.querySelector('.js-size-swatch.selected');
      const colorEl = pdpForm.querySelector('.js-color-swatch.selected');
      return {
        size: sizeEl ? sizeEl.dataset.size : null,
        color: colorEl ? colorEl.dataset.color : null
      };
    }

    function findVariant() {
      const { size, color } = currentSelection();
      if (!window.RISER_VARIANTS) return null;
      return window.RISER_VARIANTS.find(v => v.size === size && v.color === color) || null;
    }

    function refreshState() {
      const variant = findVariant();
      if (variant) {
        variantInput.value = variant.id;
        if (variant.stock <= 0) {
          stockNote.textContent = 'Out of stock in this combination';
          stockNote.classList.add('low');
          addBtn.setAttribute('disabled', 'disabled');
        } else if (variant.stock <= 5) {
          stockNote.textContent = `Only ${variant.stock} left in stock`;
          stockNote.classList.add('low');
          addBtn.removeAttribute('disabled');
        } else {
          stockNote.textContent = 'In stock — ready to ship';
          stockNote.classList.remove('low');
          addBtn.removeAttribute('disabled');
        }
        if (qtyInput) qtyInput.dataset.max = variant.stock;
      } else {
        variantInput.value = '';
        stockNote.textContent = 'Select options to check availability';
        stockNote.classList.remove('low');
        addBtn.setAttribute('disabled', 'disabled');
      }
    }

    sizeSwatches.forEach(el => {
      el.addEventListener('click', () => {
        if (el.hasAttribute('disabled')) return;
        selectSwatch(sizeSwatches, el);
        refreshState();
      });
    });
    colorSwatches.forEach(el => {
      el.addEventListener('click', () => {
        if (el.hasAttribute('disabled')) return;
        selectSwatch(colorSwatches, el);
        refreshState();
      });
    });

    refreshState();

    pdpForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!variantInput.value) {
        showToast('Please select size and color');
        return;
      }
      const formData = new FormData(pdpForm);
      if (window.RISER_CSRF) formData.append('csrf_token', window.RISER_CSRF);
      const addBtnLabel = addBtn.querySelector('.btn-label');
      if (addBtnLabel) addBtnLabel.textContent = 'Adding...'; else addBtn.textContent = 'Adding...';
      addBtn.setAttribute('disabled', 'disabled');

      fetch((window.RISER_BASE||'') + '/cart-actions.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            setCartBadge(data.cartCount, true);
            showToast(data.message || 'Added to cart');
          } else {
            showToast(data.message || 'Could not add to cart');
          }
        })
        .catch(() => showToast('Network error — please try again'))
        .finally(() => {
          if (addBtnLabel) addBtnLabel.textContent = 'Add to Cart'; else addBtn.textContent = 'Add to Cart';
          refreshState();
        });
    });
  }

  /* ---------- Live Embroidery Customizer (product page) ----------
     Unique feature: lets a customer type custom embroidery text and
     pick a thread color, with a live canvas preview stitched onto the
     cap mockup before they add it to their cart. */
  const embroCanvas = document.getElementById('embroPreview');
  const embroTextInput = document.getElementById('embroText');
  const embroThreadInputs = document.querySelectorAll('input[name="embroThreadSwatch"]');
  const embroFeeNote = document.getElementById('embroFeeNote');
  const embroHiddenText = document.getElementById('customTextField');
  const embroHiddenColor = document.getElementById('threadColorField');
  const embroCharCount = document.getElementById('embroCharCount');

  if (embroCanvas && embroTextInput) {
    const ctx = embroCanvas.getContext('2d');
    const capImg = new Image();
    capImg.src = embroCanvas.dataset.capImage;

    function currentThread() {
      const checked = document.querySelector('input[name="embroThreadSwatch"]:checked');
      return checked ? checked.value : '#E8432C';
    }

    function drawPreview() {
      const w = embroCanvas.width, h = embroCanvas.height;
      ctx.clearRect(0, 0, w, h);

      const drawText = () => {
        ctx.fillStyle = '#1c1c1c';
        ctx.fillRect(0, 0, w, h);
        if (capImg.complete && capImg.naturalWidth) {
          ctx.drawImage(capImg, 0, 0, w, h);
        }
        const text = (embroTextInput.value || 'YOUR TEXT').toUpperCase().slice(0, 12);
        ctx.save();
        ctx.font = '700 26px Archivo Black, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        // soft shadow = thread "puff" effect
        ctx.shadowColor = 'rgba(0,0,0,0.45)';
        ctx.shadowBlur = 3;
        ctx.shadowOffsetY = 2;
        ctx.fillStyle = currentThread();
        ctx.fillText(text, w / 2, h * 0.58);
        ctx.restore();
      };

      if (capImg.complete && capImg.naturalWidth) {
        drawText();
      } else {
        capImg.onload = drawText;
        capImg.onerror = drawText;
      }
    }

    embroTextInput.addEventListener('input', () => {
      embroTextInput.value = embroTextInput.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 12);
      if (embroCharCount) embroCharCount.textContent = embroTextInput.value.length + '/12';
      if (embroHiddenText) embroHiddenText.value = embroTextInput.value.trim();
      if (embroFeeNote) embroFeeNote.style.display = embroTextInput.value.trim() ? 'block' : 'none';
      drawPreview();
    });

    embroThreadInputs.forEach(input => {
      input.addEventListener('change', () => {
        if (embroHiddenColor) embroHiddenColor.value = input.value;
        drawPreview();
      });
    });

    drawPreview();
  }

  /* ---------- Mobile sticky add-to-cart bar (PDP) ---------- */
  const stickyCta = document.getElementById('mobileStickyCta');
  const stickyCtaBtn = document.getElementById('mobileStickyCtaBtn');
  const mainAddBtn = document.getElementById('pdpAddBtn');

  if (stickyCta && mainAddBtn && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        stickyCta.classList.toggle('show', !entry.isIntersecting && window.innerWidth <= 640);
      });
    }, { threshold: 0 });
    observer.observe(mainAddBtn);

    window.addEventListener('resize', () => {
      if (window.innerWidth > 640) stickyCta.classList.remove('show');
    });

    stickyCtaBtn.addEventListener('click', () => {
      if (mainAddBtn.disabled) {
        showToast('Please select size and color');
        mainAddBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      mainAddBtn.click();
    });
  }

  /* ---------- Contact form: simple client-side validation hint ---------- */
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      const required = contactForm.querySelectorAll('[required]');
      let valid = true;
      required.forEach(field => {
        if (!field.value.trim()) valid = false;
      });
      if (!valid) {
        e.preventDefault();
        showToast('Please fill in all required fields');
      }
    });
  }

})();
