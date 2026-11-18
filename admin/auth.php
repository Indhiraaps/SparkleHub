<?php
/**
 * Include at the top of every admin page.
 * Sends visitors who are not logged in to the login page,
 * and blocks admin actions submitted from other websites.
 */
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../inc/db.php';

// Any form submission, or a link that deletes something, must come from this site.
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete'])) {
    require_same_origin();
}
