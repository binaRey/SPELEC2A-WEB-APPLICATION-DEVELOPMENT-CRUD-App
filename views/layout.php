<?php
/** @var string $content @var string $title @var string $description @var array|null $flash */
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
$onList = $path === '/users' || (bool) preg_match('#^/users/\d+/edit$#', $path);
$fullTitle = $title . ' | PHP CRUD App';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <!-- Private admin-style tool: keep it out of search engines. Remove this line for a public site. -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#2563eb">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232563eb'/%3E%3Ctext x='16' y='23' font-size='19' font-weight='700' text-anchor='middle' fill='white' font-family='Arial,sans-serif'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(asset('assets/style.css')) ?>">
    <script src="<?= e(asset('assets/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="container nav-bar">
        <a class="brand" href="/users"><span class="brand-mark" aria-hidden="true">P</span>PHP CRUD App</a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="Main">
            <a href="/users" class="<?= $onList ? 'active' : '' ?>"<?= $onList ? ' aria-current="page"' : '' ?>>Users</a>
            <a href="/users/create" class="btn btn-sm">+ Add User</a>
        </nav>
    </div>
</header>

<main id="main" class="container" tabindex="-1">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="alert-close" aria-label="Dismiss message">&times;</button>
        </div>
    <?php endif; ?>

    <?= $content /* already-rendered view output (values escaped inside views) */ ?>
</main>

<footer class="site-footer">
    <div class="container">
        <span>&copy; <?= date('Y') ?> PHP CRUD App</span>
        <span class="muted">Core PHP &middot; Custom ORM &middot; PSR-4</span>
    </div>
</footer>
</body>
</html>
