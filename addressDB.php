<?php
$addressDB = new mysqli('localhost', 'u349622494_phAddress', 'Grievease_2k25', 'u349622494_phAddress');

if ($addressDB->connect_error) {
    die("Connection failed: " . $addressDB->connect_error);
}
?>