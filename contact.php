<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with RISER, a caps store in Karachi, Pakistan. Questions about embroidered caps, orders, or Cash on Delivery — we reply within 24 hours.';
$activeNav = 'contact';

$success = false;
$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if (!csrfVerify($_POST['csrf_token'] ?? '')) $errors['general'] = 'Your session expired. Please try again.';
    if ($old['name'] === '') $errors['name'] = 'Please enter your name.';
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if ($old['message'] === '' || mb_strlen($old['message']) < 10) $errors['message'] = 'Please write a message of at least 10 characters.';

    if (empty($errors)) {
        // In production: send email via mail()/SMTP or store in a `contact_messages` table.
        // Kept simple here since no mail server is guaranteed in all environments.
        $success = true;
        $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / Contact</div>
  <h1>Get In Touch</h1>
</section>

<section class="section">
  <div class="contact-layout">
    <div>
      <div class="contact-info-item">
        <h4>Visit Us</h4>
        <p>RISER Workshop, Shahrah-e-Faisal,<br>Karachi, Sindh, Pakistan</p>
      </div>
      <div class="contact-info-item">
        <h4>Call / WhatsApp</h4>
        <p>+92 300 1234567</p>
      </div>
      <div class="contact-info-item">
        <h4>Email</h4>
        <p>hello@riser.pk</p>
      </div>
      <div class="contact-info-item">
        <h4>Hours</h4>
        <p>Monday – Saturday, 11AM – 8PM PKT</p>
      </div>
    </div>

    <div>
      <?php if ($success): ?>
        <div class="alert alert--success">Thanks for reaching out — we'll get back to you within 24 hours.</div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/contact.php" id="contactForm" novalidate>
        <?= csrfField() ?>
        <div class="form-grid">
          <div class="field <?= isset($errors['name']) ? 'error' : '' ?>">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
            <?php if (isset($errors['name'])): ?><span class="err-msg"><?= e($errors['name']) ?></span><?php endif; ?>
          </div>
          <div class="field <?= isset($errors['email']) ? 'error' : '' ?>">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            <?php if (isset($errors['email'])): ?><span class="err-msg"><?= e($errors['email']) ?></span><?php endif; ?>
          </div>
          <div class="field full">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>">
          </div>
          <div class="field full <?= isset($errors['message']) ? 'error' : '' ?>">
            <label for="message">Message *</label>
            <textarea id="message" name="message" rows="6" required><?= e($old['message']) ?></textarea>
            <?php if (isset($errors['message'])): ?><span class="err-msg"><?= e($errors['message']) ?></span><?php endif; ?>
          </div>
        </div>
        <button type="submit" class="btn mt-40">Send Message</button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
