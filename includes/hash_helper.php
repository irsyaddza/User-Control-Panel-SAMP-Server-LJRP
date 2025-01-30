<?php
function customHash($password) {
    // Hash password menggunakan whirlpool
    $password = hash('whirlpool', $password);
    // Convert ke uppercase
    $password = strtoupper($password);
    return $password;
}
?>