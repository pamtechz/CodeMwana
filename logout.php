<?php
require_once __DIR__ . '/app/bootstrap.php';
Auth::logout();
header('Location: ' . url('index.php'));
exit;
