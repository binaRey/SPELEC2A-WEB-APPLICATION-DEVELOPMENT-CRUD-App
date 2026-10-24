<?php /** @var App\Models\User[] $users */ ?>
<div class="page-header">
    <div>
        <h1>Users</h1>
        <p class="muted"><?= count($users) ?> <?= count($users) === 1 ? 'user' : 'users' ?> registered</p>
    </div>
    <a class="btn" href="/users/create">+ Add User</a>
</div>

<?php if (empty($users)): ?>
    <div class="card empty-state">
        <p>No users yet.</p>
        <a class="btn" href="/users/create">Add the first user</a>
    </div>
<?php else: ?>
    <div class="card table-card">
        <table class="data-table">
            <thead>
                <tr><th>User</th><th>Phone</th><th>Created</th><th><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td data-label="User">
                        <div class="user-cell">
                            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $user->name, 0, 1))) ?></span>
                            <div>
                                <div class="user-name"><?= e($user->name) ?></div>
                                <div class="muted small"><?= e($user->email) ?></div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Phone"><?= e($user->phone ?: '-') ?></td>
                    <td data-label="Created"><?= e(date('M j, Y', strtotime((string) $user->created_at))) ?></td>
                    <td class="actions-cell">
                        <div class="actions">
                            <a class="btn btn-sm btn-ghost" href="/users/<?= (int) $user->id ?>/edit">Edit</a>
                            <form method="post" action="/users/<?= (int) $user->id ?>/delete"
                                  data-confirm data-name="<?= e($user->name) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<dialog id="confirm-dialog" class="dialog" aria-labelledby="confirm-title">
    <h2 id="confirm-title">Delete user?</h2>
    <p>You are about to delete <strong id="confirm-name"></strong>. This cannot be undone.</p>
    <div class="dialog-actions">
        <button type="button" class="btn btn-secondary" data-dialog-cancel>Cancel</button>
        <button type="button" class="btn btn-danger" data-dialog-confirm>Delete</button>
    </div>
</dialog>
