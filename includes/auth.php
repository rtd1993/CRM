<?php
// File: includes/auth.php

session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function check_permission($required_role) {
    $roles = ['guest' => 0, 'employee' => 1, 'admin' => 2, 'developer' => 3];
    return $roles[current_user_role()] >= $roles[$required_role];
}
