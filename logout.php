<?php
require_once __DIR__ . '/app/bootstrap.php';
if (!is_post()) { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
Auth::logout();
header('Location: ' . url('login.php'));
exit;
