<?php
/**
 * modules/auth/logout.php
 * Proses logout - hapus session dan redirect ke login
 */

session_start();
session_destroy();

header('Location: /procurement/modules/auth/login.php');
exit;
