<?php
// ملف للتحقق من صلاحيات المستخدم
// auth_check.php

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * التحقق من تسجيل دخول المستخدم
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * التحقق من أن المستخدم مسؤول (admin)
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * التحقق من أن المستخدم عادي (user)
 */
function isUser() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

/**
 * الحصول على دور المستخدم الحالي
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * إجبار المستخدم على تسجيل الدخول
 */
function requireLogin($redirectUrl = '/login.html') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * السماح للمسؤولين فقط
 */
function requireAdmin($redirectUrl = '/dashboard.html') {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * السماح للمستخدمين العاديين فقط
 */
function requireUser($redirectUrl = '/admin-dashboard.html') {
    requireLogin();
    if (!isUser()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * الحصول على بيانات المستخدم الحالي
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'fullname' => $_SESSION['user_fullname'] ?? null,
        'username' => $_SESSION['user_username'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}
?>
