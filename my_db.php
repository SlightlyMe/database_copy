<?php
// Simple PDO connection using environment variables
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_NAME'));
$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Database name variable used by examples
$my_db = getenv('DB_NAME');
