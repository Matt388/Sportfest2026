<?php
require_once 'config.php';
require_once 'auth.php';

session_destroy();
session_start();
flash('Sie wurden abgemeldet.', 'info');
redirect('login.php');
?>
