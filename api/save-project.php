<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (!Auth::check()) json_response(['ok' => false, 'message' => 'Please sign in again.'], 401);
if (!is_post()) json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
verify_csrf();
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
if (!is_array($data)) json_response(['ok' => false, 'message' => 'Invalid request.'], 422);
$title = trim((string) ($data['title'] ?? ''));
$code = (string) ($data['code'] ?? '');
if ($title === '' || mb_strlen($title) > 100) json_response(['ok' => false, 'message' => 'Use a project title of 1 to 100 characters.'], 422);
if (mb_strlen($code) > 20000) json_response(['ok' => false, 'message' => 'The project is too large.'], 422);
try {
    $id = Learning::saveProject((int) current_user()['id'], ['id' => (int) ($data['id'] ?? 0), 'title' => $title, 'code' => $code]);
    json_response(['ok' => true, 'id' => $id, 'message' => 'Project saved.']);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'message' => config('app.debug') ? $exception->getMessage() : 'The project could not be saved.'], 500);
}
