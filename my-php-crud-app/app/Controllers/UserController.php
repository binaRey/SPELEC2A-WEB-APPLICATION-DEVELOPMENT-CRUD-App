<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Core\View;
use PDOException;

/**
 * Handles all CRUD actions for users.
 */
final class UserController
{
    /** READ: list all users. */
    public function index(): void
    {
        View::render('users/index', [
            'title' => 'Users',
            'users' => User::all('id', 'DESC'),
        ]);
    }

    /** CREATE: show form. */
    public function create(): void
    {
        View::render('users/create', [
            'title'  => 'Add User',
            'old'    => [],
            'errors' => [],
        ]);
    }

    /** CREATE: handle submission. */
    public function store(): void
    {
        $this->guardCsrf();

        $input  = $this->sanitize($_POST);
        $errors = $this->validate($input);

        if ($errors === []) {
            try {
                User::create($input);
                flash('success', 'User created successfully.');
                redirect('/users');
            } catch (PDOException $e) {
                $errors = $this->handleDbError($e);
            }
        }

        View::render('users/create', [
            'title'  => 'Add User',
            'old'    => $input,
            'errors' => $errors,
        ], 422);
    }

    /** UPDATE: show form. */
    public function edit(int $id): void
    {
        $user = User::find($id);
        if (!$user) {
            View::error(404, 'User not found.');
            return;
        }

        View::render('users/edit', [
            'title'  => 'Edit User',
            'user'   => $user,
            'old'    => $user->toArray(),
            'errors' => [],
        ]);
    }

    /** UPDATE: handle submission. */
    public function update(int $id): void
    {
        $this->guardCsrf();

        $user = User::find($id);
        if (!$user) {
            View::error(404, 'User not found.');
            return;
        }

        $input  = $this->sanitize($_POST);
        $errors = $this->validate($input, $id);

        if ($errors === []) {
            try {
                $user->update($input);
                flash('success', 'User updated successfully.');
                redirect('/users');
            } catch (PDOException $e) {
                $errors = $this->handleDbError($e);
            }
        }

        View::render('users/edit', [
            'title'  => 'Edit User',
            'user'   => $user,
            'old'    => $input,
            'errors' => $errors,
        ], 422);
    }

    /** DELETE */
    public function destroy(int $id): void
    {
        $this->guardCsrf();

        $user = User::find($id);
        if (!$user) {
            View::error(404, 'User not found.');
            return;
        }

        $user->delete();
        flash('success', 'User deleted.');
        redirect('/users');
    }

    /* ------------------------------ helpers ------------------------------ */

    /** Reject requests without a valid CSRF token. */
    private function guardCsrf(): void
    {
        if (!csrf_valid($_POST['_csrf'] ?? null)) {
            View::error(419, 'Your session expired or the form token is invalid. Please go back and try again.');
            exit;
        }
    }

    /** Whitelist and normalise incoming fields. */
    private function sanitize(array $post): array
    {
        return [
            'name'  => trim((string) ($post['name'] ?? '')),
            'email' => strtolower(trim((string) ($post['email'] ?? ''))),
            'phone' => trim((string) ($post['phone'] ?? '')),
        ];
    }

    /** @return array<string,string> field => message */
    private function validate(array $in, ?int $ignoreId = null): array
    {
        $errors = [];

        $nameLen = mb_strlen($in['name']);
        if ($nameLen < 2 || $nameLen > 100) {
            $errors['name'] = 'Name is required (2-100 characters).';
        }

        if ($in['email'] === '' || !filter_var($in['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($in['email']) > 150) {
            $errors['email'] = 'A valid email address is required (max 150 characters).';
        } else {
            $existing = User::firstWhere('email', '=', $in['email']);
            if ($existing && (int) $existing->id !== $ignoreId) {
                $errors['email'] = 'This email is already registered.';
            }
        }

        if ($in['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $in['phone'])) {
            $errors['phone'] = 'Phone must be 7-20 characters: digits, +, -, spaces, parentheses.';
        }

        return $errors;
    }

    /** Convert DB exceptions to user-friendly errors (and log the real cause). */
    private function handleDbError(PDOException $e): array
    {
        // 23000 = integrity constraint violation (e.g., duplicate email race condition)
        if ($e->getCode() === '23000') {
            return ['email' => 'This email is already registered.'];
        }

        error_log('DB error: ' . $e->getMessage());
        return ['form' => 'A database error occurred. Please try again.'];
    }
}
