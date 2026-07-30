<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!Auth::check()) json_response(['ok' => false, 'message' => 'Your session ended. Sign in and try again.'], 401);
if (!is_post()) json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
verify_csrf();

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) json_response(['ok' => false, 'message' => 'The project request is not valid.'], 422);

$title = trim((string) ($data['title'] ?? ''));
$language = strtolower(trim((string) ($data['language'] ?? 'mwanacode')));
$files = is_array($data['files'] ?? null) ? $data['files'] : [];
$stdin = (string) ($data['stdin'] ?? '');

if (mb_strlen($title) < 2 || mb_strlen($title) > 120) {
    json_response(['ok' => false, 'message' => 'Use a project title between 2 and 120 characters.'], 422);
}
if (!Learning::language($language)) {
    json_response(['ok' => false, 'message' => 'Select one of the supported programming languages.'], 422);
}
if (!$files) {
    json_response(['ok' => false, 'message' => 'The workspace must contain at least one source file.'], 422);
}
$totalCharacters = 0;
foreach ($files as $name => $content) {
    if (!is_string($name) || !is_string($content)) json_response(['ok' => false, 'message' => 'The workspace contains an invalid file.'], 422);
    $totalCharacters += mb_strlen($content);
}
if ($totalCharacters > 180000) {
    json_response(['ok' => false, 'message' => 'The workspace is larger than the 180,000-character limit.'], 422);
}

try {
    $id = Learning::saveProject((int) current_user()['id'], [
        'id' => (int) ($data['id'] ?? 0),
        'title' => $title,
        'language' => $language,
        'files' => $files,
        'stdin' => $stdin,
    ]);
    json_response(['ok' => true, 'id' => $id, 'message' => 'Project saved.']);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'message' => config('app.debug') ? $exception->getMessage() : 'The project could not be saved.'], 500);
}
