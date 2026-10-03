<?php
$mysqli = new mysqli("localhost", "root", "", "ravion_pharma");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$sql = "ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL AFTER description;";
if ($mysqli->query($sql) === TRUE) {
    echo "Table products altered successfully\n";
} else {
    echo "Error altering table: " . $mysqli->error . "\n";
}
$mysqli->close();
