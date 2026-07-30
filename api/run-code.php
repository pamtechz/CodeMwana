<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!Auth::check()) json_response(['ok' => false, 'message' => 'Your session ended. Sign in and try again.'], 401);
if (!is_post()) json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
verify_csrf();

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) json_response(['ok' => false, 'message' => 'The run request is not valid.'], 422);

$languageSlug = strtolower(trim((string) ($data['language'] ?? '')));
$language = Learning::language($languageSlug);
$files = is_array($data['files'] ?? null) ? $data['files'] : [];
$stdin = (string) ($data['stdin'] ?? '');
$projectId = (int) ($data['project_id'] ?? 0);

if (!$language) json_response(['ok' => false, 'message' => 'Select a supported programming language.'], 422);
if (($language['execution_mode'] ?? '') !== 'remote') {
    json_response(['ok' => false, 'message' => 'This language runs inside the browser preview.'], 422);
}
if (!$files) json_response(['ok' => false, 'message' => 'Add at least one source file before running the project.'], 422);
if ($projectId > 0 && !Learning::project($projectId, (int) current_user()['id'])) {
    json_response(['ok' => false, 'message' => 'The selected project does not belong to this account.'], 403);
}

$normalised = LanguageCatalog::normalizeWorkspace($files, $language);
$totalCharacters = array_sum(array_map(static fn (string $content): int => mb_strlen($content), $normalised));
if ($totalCharacters > 180000) json_response(['ok' => false, 'message' => 'The workspace is too large to run.'], 422);

try {
    $result = CodeRunner::run($language, $normalised, $stdin);
    Learning::logCodeRun((int) current_user()['id'], $projectId ?: null, $languageSlug, $result, $stdin);
    activity('code_executed', ['language' => $languageSlug, 'status' => $result['status'], 'project_id' => $projectId ?: null]);
    json_response(['ok' => true, 'result' => $result]);
} catch (Throwable $exception) {
    $result = [
        'status' => 'failed',
        'stdout' => '',
        'stderr' => $exception->getMessage(),
        'exit_code' => null,
        'execution_time_ms' => null,
        'memory_bytes' => null,
    ];
    Learning::logCodeRun((int) current_user()['id'], $projectId ?: null, $languageSlug, $result, $stdin);
    json_response(['ok' => false, 'message' => $exception->getMessage(), 'result' => $result], CodeRunner::configured() ? 502 : 503);
}
