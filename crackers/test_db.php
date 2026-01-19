<?php
require 'inc/db.php';

echo "Database connected successfully!<br>";
echo "Products count: ";

echo $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();
