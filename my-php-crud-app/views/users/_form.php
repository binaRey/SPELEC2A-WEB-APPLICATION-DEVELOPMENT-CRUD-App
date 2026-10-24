<?php
/**
 * Shared form partial.
 * Expects: $action (string), $old (array), $errors (array), $submitLabel (string)
 */
$field = static fn(string $k): string => e($old[$k] ?? '');
?>
<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>

    <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <label for="name">Name</label>
    <input type="text" id="name" name="name" maxlength="100" required
           value="<?= $field('name') ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>">
    <?php if (isset($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" maxlength="150" required
           value="<?= $field('email') ?>" class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
    <?php if (isset($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>

    <label for="phone">Phone <span class="muted">(optional)</span></label>
    <input type="text" id="phone" name="phone" maxlength="20"
           value="<?= $field('phone') ?>" class="<?= isset($errors['phone']) ? 'invalid' : '' ?>">
    <?php if (isset($errors['phone'])): ?><div class="error"><?= e($errors['phone']) ?></div><?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a href="/users" class="btn btn-secondary">Cancel</a>
    </div>
</form>
