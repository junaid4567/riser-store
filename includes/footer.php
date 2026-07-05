</main>
<?php $B = BASE_URL; ?>
<footer class="site-footer">
  <div class="footer__grid">
    <div class="footer__brand">
      <a href="<?= $B ?>/index.php" class="logo">
        <span class="logo__mark" aria-hidden="true">R</span>
        <span class="logo__word">RISER</span>
      </a>
      <p>Streetwear caps designed and embroidered in Karachi, Pakistan. Built for the street, dropped at your door — Cash on Delivery, nationwide.</p>
    </div>

    <div class="footer__col">
      <h4>Shop</h4>
      <ul class="footer__links">
        <li><a href="<?= $B ?>/shop.php">All Caps</a></li>
        <li><a href="<?= $B ?>/featured.php">Featured</a></li>
        <li><a href="<?= $B ?>/new-arrivals.php">New Arrivals</a></li>
        <li><a href="<?= $B ?>/cart.php">Your Cart</a></li>
      </ul>
    </div>

    <div class="footer__col">
      <h4>Company</h4>
      <ul class="footer__links">
        <li><a href="<?= $B ?>/about.php">About RISER</a></li>
        <li><a href="<?= $B ?>/contact.php">Contact Us</a></li>
      </ul>
    </div>

    <div class="footer__col">
      <h4>Get in touch</h4>
      <ul class="footer__links">
        <li>Karachi, Pakistan</li>
        <li>+92 300 1234567</li>
        <li>hello@riser.pk</li>
      </ul>
    </div>
  </div>

  <div class="footer__bottom">
    <span>&copy; <?= date('Y') ?> RISER. All rights reserved.</span>
    <span>Cash on Delivery available across Pakistan</span>
  </div>
</footer>

</div><!-- /.site-frame -->

<div class="toast" id="toast"></div>

<script>
window.RISER_BASE = "<?= $B ?>";
window.RISER_CSRF = "<?= e(csrfToken()) ?>";
</script>
<script src="<?= assetUrl('/js/main.js') ?>" defer></script>
</body>
</html>
