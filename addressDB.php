<?php
$addressDB = new mysqli('localhost', 'root', 'Abcd1234', 'philippines_database');

if ($addressDB->connect_error) {
    die("Connection failed: " . $addressDB->connect_error);
}
?>