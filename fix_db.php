<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "ravion_pharma", 3306);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

echo "<h3>Database Fix Script</h3>";

// 1. Check and add parent_id to territories
$res = $mysqli->query("SHOW COLUMNS FROM `territories` LIKE 'parent_id'");
if ($res && $res->num_rows === 0) {
    $alter = $mysqli->query("ALTER TABLE territories ADD parent_id INT(11) NULL AFTER type, ADD CONSTRAINT fk_territories_parent FOREIGN KEY (parent_id) REFERENCES territories(id) ON DELETE CASCADE");
    if ($alter) {
        echo "<p style='color:green;'>✓ Successfully added 'parent_id' to territories table.</p>";
    } else {
        echo "<p style='color:red;'>✗ Error adding 'parent_id': " . $mysqli->error . "</p>";
    }
} else {
    echo "<p>ℹ 'parent_id' column already exists in territories.</p>";
}

// 2. Check and insert default product with ID 1
$res = $mysqli->query("SELECT id FROM products WHERE id = 1");
if ($res && $res->num_rows === 0) {
    $insert = $mysqli->query("INSERT INTO products (id, name, description, pts_price, created_at, updated_at) VALUES (1, 'PEDIVION DROPS 15 ML', 'Default product (auto-created by fix_db)', 0, NOW(), NOW())");
    if ($insert) {
        echo "<p style='color:green;'>✓ Successfully inserted default product with ID 1.</p>";
    } else {
        echo "<p style='color:red;'>✗ Error inserting product: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p>ℹ Product with ID 1 already exists.</p>";
}

$mysqli->close();
echo "<p><strong>Finished execution.</strong></p>";
