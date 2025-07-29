<?php
// File: index.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>