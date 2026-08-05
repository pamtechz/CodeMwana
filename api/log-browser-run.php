<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!Auth::check()) json_response(['ok' => false, 'message' => 'Your session ended. Sign in and try again.'], 401);
if (!is_post()) json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
verify_csrf();

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) json_response(['ok' => false, 'message' => 'The run record is not valid.'], 422);

$language = strtolower(trim((string) ($data['language'] ?? '')));
if (!in_array($language, ['python', 'php'], true)) {
    json_response(['ok' => false, 'message' => 'Only browser Python and PHP runs can use this endpoint.'], 422);
}

$projectId = max(0, (int) ($data['project_id'] ?? 0));
if ($projectId > 0 && !Learning::project($projectId, (int) current_user()['id'])) {
    json_response(['ok' => false, 'message' => 'The selected project does not belong to this account.'], 403);
}

$status = (string) ($data['status'] ?? 'failed');
if (!in_array($status, ['completed', 'failed'], true)) $status = 'failed';

$result = [
    'status' => $status,
    'stdout' => mb_substr((string) ($data['stdout'] ?? ''), 0, 50000),
    'stderr' => mb_substr((string) ($data['stderr'] ?? ''), 0, 50000),
    'exit_code' => isset($data['exit_code']) ? (int) $data['exit_code'] : null,
    'execution_time_ms' => isset($data['execution_time_ms']) ? max(0, min(120000, (int) $data['execution_time_ms'])) : null,
    'memory_bytes' => null,
    'runtime' => $language . '-browser-wasi',
    'version' => 'codapi-0.20.0',
];

$stdin = mb_substr((string) ($data['stdin'] ?? ''), 0, 10000);
Learning::logCodeRun((int) current_user()['id'], $projectId ?: null, $language, $result, $stdin);
activity('code_executed', [
    'language' => $language,
    'status' => $status,
    'project_id' => $projectId ?: null,
    'engine' => 'browser-wasi',
]);

json_response(['ok' => true]);
