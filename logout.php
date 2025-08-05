<?php
require_once 'db.php';

// Destroy session
session_destroy();

// Redirect to home page using JavaScript
echo "<script>window.location.href = 'index.php';</script>";
exit();
?>
