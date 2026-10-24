<?php
declare(strict_types=1);

namespace App\Models;

use Core\BaseModel;

/**
 * Maps to the `users` table.
 *
 * @property int|null    $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $created_at
 * @property string|null $updated_at
 */
final class User extends BaseModel
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['name', 'email', 'phone'];
}
