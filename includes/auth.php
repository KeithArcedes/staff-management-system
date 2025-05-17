<?php
require_once 'db.php';

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function getProfilePath() {
    if (isAdmin()) {
        return APP_URL . '/admin/profile.php';
    } else {
        return APP_URL . '/staff/profile.php';
    }
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../staff/dashboard.php");
        exit();
    }
}

function redirectIfNotStaff() {
    redirectIfNotLoggedIn();
    if (!isStaff()) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}