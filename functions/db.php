<?php

$host   = 'localhost';
$dbname = 'redirection_links_parser';
$user   = 'root';
$pass   = '';

try {

    $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $user, $pass);

} catch(PDOException $e) {

    exit("Unable to connect to database. Error: \n" . $e->getMessage());

}