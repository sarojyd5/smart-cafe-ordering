<?php

function escape($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfLoggedIn()
{
    if (isset($_SESSION['customer_id'])) {
        header("Location: index.php");
        exit();
    }
}

function requireCustomerLogin()
{
    if (!isset($_SESSION['customer_id'])) {
        header("Location: ../login.php");
        exit();
    }
}