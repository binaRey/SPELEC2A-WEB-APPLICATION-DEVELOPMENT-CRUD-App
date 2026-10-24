<?php
/**
 * @var App\Models\User[] $users
 * @var array $pagination  items,total,per_page,current_page,last_page
 * @var string $search @var string $sort @var string $dir
 */
$total = $pagination['total'];
$cur   = $pagination['current_page'];
$last  = $pagination['last_page'];

/** Build a /users URL that keeps the current search/sort, overriding some params. */
$url = static function (array $override) use ($search, $sort, $dir): string {
    $params = array_merge(['q' => $search, 'sort' => $sort, 'dir' => $dir], $override);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '/users?' . http_build_query($params);
};

/** Header link for a sortable column. */
$sortLink = static function (string $column, string $label) use ($sort, $dir, $url): string {
    $active  = $sort === $column;
    $nextDir = ($active && $dir === 'asc') ? 'desc' : 'asc';
    $arrow   = $active ? ($dir === 'asc' ? ' &#9650;' : ' &#9660;') : '';
    $aria    = $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none';

    return '<a class="sort-link' . ($active ? ' active' : '') . '" href="'
        . e($url(['sort' => $column, 'dir' => $nextDir, 'page' => 1])) . '" aria-sort="' . $aria . '">'
        . e($label) . $arrow . '</a>';
};
?>
<div class="page-header">
    <div>
        <h1>Users</h1>
        <p class="muted">
            <?php if ($search !== ''): ?>
                <?= $total ?> <?= $total === 1 ? 'result' : 'results' ?> for &ldquo;<?= e($search) ?>&rdquo;
            <?php else: ?>
                <?= $total ?> <?= $total === 1 ? 'user' : 'users' ?> registered
            <?php endif; ?>
        </p>
    </div>
    <a class="btn" href="/users/create">+ Add User</a>
</div>

<form class="toolbar" method="get" action="/users" role="search">
    <label class="sr-only" for="q">Search users</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" maxlength="100"
           placeholder="Search name, email or phone">
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
    <button type="submit" class="btn">Search</button>
    <?php if ($search !== ''): ?>
        <a class="btn btn-secondary" href="<?= e($url(['q' => '', 'page' => 1])) ?>">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($users)): ?>
    <div class="card empty-state">
        <?php if ($search !== ''): ?>
            <p>No users match &ldquo;<?= e($search) ?>&rdquo;.</p>
            <a class="btn" href="/users">Show all users</a>
        <?php else: ?>
            <p>No users yet.</p>
            <a class="btn" href="/users/create">Add the first user</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card table-card">
        <table class="data-table">
            <caption class="sr-only">List of users, sortable by name and created date</caption>
            <thead>
                <tr>
                    <th scope="col"><?= $sortLink('name', 'User') ?></th>
                    <th scope="col">Phone</th>
                    <th scope="col"><?= $sortLink('created_at', 'Created') ?></th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
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

    <?php if ($last > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($cur > 1): ?>
                <a href="<?= e($url(['page' => $cur - 1])) ?>" rel="prev">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled" aria-hidden="true">&laquo; Prev</span>
            <?php endif; ?>

            <?php for ($p = max(1, $cur - 2); $p <= min($last, $cur + 2); $p++): ?>
                <?php if ($p === $cur): ?>
                    <span class="current" aria-current="page"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= e($url(['page' => $p])) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($cur < $last): ?>
                <a href="<?= e($url(['page' => $cur + 1])) ?>" rel="next">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled" aria-hidden="true">Next &raquo;</span>
            <?php endif; ?>
        </nav>
        <p class="muted small pagination-note">Page <?= $cur ?> of <?= $last ?></p>
    <?php endif; ?>
<?php endif; ?>

<dialog id="confirm-dialog" class="dialog" aria-labelledby="confirm-title">
    <h2 id="confirm-title">Delete user?</h2>
    <p>You are about to delete <strong id="confirm-name"></strong>. This cannot be undone.</p>
    <div class="dialog-actions">
        <button type="button" class="btn btn-secondary" data-dialog-cancel>Cancel</button>
        <button type="button" class="btn btn-danger" data-dialog-confirm>Delete</button>
    </div>
</dialog>
