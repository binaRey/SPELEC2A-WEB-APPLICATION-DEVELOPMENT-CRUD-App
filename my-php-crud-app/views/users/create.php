<?php /** @var array $old @var array $errors */ ?>
<div class="page-header">
    <div>
        <h1>Add User</h1>
        <p class="muted">Create a new user record.</p>
    </div>
</div>
<div class="card card-narrow">
    <?php
    $action = '/users';
    $submitLabel = 'Create User';
    require BASE_PATH . '/views/users/_form.php';
    ?>
</div>
