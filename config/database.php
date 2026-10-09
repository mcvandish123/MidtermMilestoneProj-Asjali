<?php
/**
 * The ONE database connection file.
 * Every page gets the connection through includes/bootstrap.php, which requires this file.
 * Change the constants below to match your local MySQL setup.
 */
declare(strict_types=1);

final class Database
{
    private const HOST    = '127.0.0.1';
    private const PORT    = 3306;
    private const NAME    = 'barangay_cooking';
    private const USER    = 'root';
    private const PASS    = '';
    private const CHARSET = 'utf8mb4';

    private static ?PDO $pdo = null;

    /** Returns one shared PDO connection for the whole request. */
    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::HOST,
                self::PORT,
                self::NAME,
                self::CHARSET
            );

            self::$pdo = new PDO($dsn, self::USER, self::PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
            ]);

            // Store and read timestamps in Philippine time.
            self::$pdo->exec("SET time_zone = '+08:00'");
        }

        return self::$pdo;
    }
}
