<?php
// auth/signup.php — opens login.php on the Sign Up tab
session_start();
$_SESSION['auth_tab'] = 'register';
header('Location: login.php');
exit;