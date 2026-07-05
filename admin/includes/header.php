<?php
/** Expects $pageTitle and $activeAdminNav to be set before include. */
$activeAdminNav = $activeAdminNav ?? '';
$B = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Admin') ?> | RISER Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= assetUrl('/css/style.css') ?>">
<link rel="stylesheet" href="<?= assetUrl('/admin/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="adminSidebar">
    <span></span><span></span><span></span>
  </button>
  <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

  <aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= $B ?>/admin/dashboard.php" class="logo">RI<span style="color:var(--riser-red)">S</span>ER</a>
    <nav class="admin-nav">
      <a href="<?= $B ?>/admin/dashboard.php" class="<?= $activeAdminNav==='dashboard' ? 'active':'' ?>" style="--i:0">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none"><path d="M3 10.5 10 4l7 6.5M5 9v7h10V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Dashboard
      </a>
      <a href="<?= $B ?>/admin/products.php" class="<?= $activeAdminNav==='products' ? 'active':'' ?>" style="--i:1">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none"><path d="M3 6.5 10 3l7 3.5-7 3.5-7-3.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 6.5V13l7 3.5 7-3.5V6.5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Products
      </a>
      <a href="<?= $B ?>/admin/orders.php" class="<?= $activeAdminNav==='orders' ? 'active':'' ?>" style="--i:2">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none"><path d="M4 4h12l-1 11.5a1 1 0 0 1-1 .9H6a1 1 0 0 1-1-.9L4 4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M7 4a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.6"/></svg>
        Orders
      </a>
      <div class="divider" style="--i:3"></div>
      <a href="<?= $B ?>/index.php" target="_blank" style="--i:4">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none"><path d="M8 4H4v12h12v-4M11 3h6v6M9 11l8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        View Store
      </a>
      <a href="<?= $B ?>/admin/logout.php" style="--i:5">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none"><path d="M8 4H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M13 14l4-4-4-4M17 10H8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Log Out
      </a>
    </nav>
  </aside>
  <main class="admin-main">
