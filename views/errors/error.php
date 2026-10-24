<?php /** @var int $code @var string $message */ ?>
<div class="card card-narrow empty-state">
    <p class="error-code"><?= (int) $code ?></p>
    <p><?= e($message) ?></p>
    <a class="btn" href="/users">Back to users</a>
</div>
