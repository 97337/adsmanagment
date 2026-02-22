<?php
/**
 * Auth Guard - include at top of all admin pages
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
