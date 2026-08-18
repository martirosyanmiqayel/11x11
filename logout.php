<?php
/** logout.php — выход. */
require_once __DIR__ . '/includes/auth.php';
logout();
redirect('login.php');
