<?php
/** @var string $content @var string $title @var array|null $flash */
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
$onList = $path === '/users' || (bool) preg_match('#^/users/\d+/edit$#', $path);
$onAdd  = $path === '/users/create';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= e($title) ?> | PHP CRUD App</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
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

<main id="main" class="container">
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
