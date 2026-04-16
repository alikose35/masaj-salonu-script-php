<?php

declare(strict_types=1);

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $admin = db()->adminByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
        session_regenerate_id(true);
    }
}
