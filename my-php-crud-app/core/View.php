<?php
declare(strict_types=1);

namespace Core;

/**
 * Minimal view renderer: renders views/<name>.php inside views/layout.php.
 */
final class View
{
    public static function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);

        $flash = pull_flash();
        extract($data, EXTR_SKIP);

        ob_start();
        require BASE_PATH . '/views/' . $view . '.php';
        $content = ob_get_clean();

        $title ??= 'PHP CRUD App';
        require BASE_PATH . '/views/layout.php';
    }

    /** Render a friendly error page. */
    public static function error(int $code, string $message): void
    {
        self::render('errors/error', [
            'title'   => "Error {$code}",
            'code'    => $code,
            'message' => $message,
        ], $code);
    }
}
