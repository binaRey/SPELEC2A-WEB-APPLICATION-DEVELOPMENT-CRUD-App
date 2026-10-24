<?php /** @var App\Models\User $user @var array $old @var array $errors */ ?>
<div class="page-header">
    <div>
        <h1>Edit User</h1>
        <p class="muted">Editing user #<?= (int) $user->id ?></p>
    </div>
</div>
<div class="card card-narrow">
    <?php
    $action = '/users/' . (int) $user->id . '/update';
    $submitLabel = 'Save Changes';
    require BASE_PATH . '/views/users/_form.php';
    ?>
</div>
