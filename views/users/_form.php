<?php
/**
 * Shared form partial.
 * Expects: $action (string), $old (array), $errors (array), $submitLabel (string)
 */
$value       = static fn(string $k): string => e($old[$k] ?? '');
$fieldErrors = array_diff_key($errors, ['form' => true]);

/** aria attributes for a field: marks it invalid and links it to its message/hint. */
$aria = static function (string $k, string $extra = '') use ($errors): string {
    $ids = array_filter([isset($errors[$k]) ? "{$k}-error" : '', $extra]);
    return (isset($errors[$k]) ? ' aria-invalid="true"' : '')
        . ($ids ? ' aria-describedby="' . e(implode(' ', $ids)) . '"' : '');
};
?>
<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>

    <?php if ($fieldErrors): ?>
        <div class="alert alert-error error-summary" id="error-summary" role="alert" tabindex="-1">
            <div>
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($fieldErrors as $field => $message): ?>
                        <li><a href="#<?= e($field) ?>"><?= e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <label for="name">Name <span class="req" aria-hidden="true">*</span></label>
    <input type="text" id="name" name="name" maxlength="100" required autocomplete="name"
           value="<?= $value('name') ?>"<?= $aria('name') ?>>
    <?php if (isset($errors['name'])): ?><div class="error" id="name-error"><?= e($errors['name']) ?></div><?php endif; ?>

    <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
    <input type="email" id="email" name="email" maxlength="150" required autocomplete="email" inputmode="email"
           value="<?= $value('email') ?>"<?= $aria('email') ?>>
    <?php if (isset($errors['email'])): ?><div class="error" id="email-error"><?= e($errors['email']) ?></div><?php endif; ?>

    <label for="phone">Phone <span class="muted">(optional)</span></label>
    <input type="tel" id="phone" name="phone" maxlength="20" autocomplete="tel" inputmode="tel"
           value="<?= $value('phone') ?>"<?= $aria('phone', 'phone-hint') ?>>
    <div class="hint" id="phone-hint">Digits, +, -, spaces and parentheses (7-20 characters).</div>
    <?php if (isset($errors['phone'])): ?><div class="error" id="phone-error"><?= e($errors['phone']) ?></div><?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a href="/users" class="btn btn-secondary">Cancel</a>
    </div>
</form>
