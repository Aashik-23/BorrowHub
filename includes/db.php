<?php
/**
 * db.php — central PDO database connection for BorrowHub.
 * Every page that needs the database includes this file.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'borrowhub';
$DB_USER = 'root';      // default XAMPP/WAMP username
$DB_PASS = '';          // default XAMPP/WAMP password (blank)

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ]
    );
} catch (PDOException $e) {
    // Never leak DB credentials/details to the browser in production.
    die('Database connection failed. Please make sure MySQL is running and the '
        . '"borrowhub" database has been imported from database.sql. (' . $e->getMessage() . ')');
}
