<?php require 'conn.php'; print_r($conn->query('SHOW COLUMNS FROM rooms')->fetchAll()); ?>
