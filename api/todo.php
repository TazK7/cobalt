<?php
/**
 * Toggle / add / delete items in data/lists.json → todo.items.
 *
 *   POST /api/todo.php  (application/json)
 *     { "op": "toggle", "index": 2 }
 *     { "op": "add", "text": "Ship feature X", "priority": "high", "due": "2026-05-01" }
 *     { "op": "delete", "index": 0 }
 *
 * Practice ideas:
 *   - move the dispatch into a class (TodoController::handle)
 *   - add a CSRF token check
 *   - persist per-user state in $_SESSION instead of the shared JSON file
 */
declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method not allowed'], 405);
}

$body = read_json_body();
$op   = $body['op'] ?? '';

try {
    $data = load_json('lists');
    $todos = &$data['todo']['items'];

    switch ($op) {
        case 'toggle':
            $i = (int) ($body['index'] ?? -1);
            if (!isset($todos[$i])) json_response(['error' => 'index out of range'], 400);
            $todos[$i]['checked'] = !($todos[$i]['checked'] ?? false);
            break;

        case 'add':
            $text = trim((string) ($body['text'] ?? ''));
            if ($text === '') json_response(['error' => 'text required'], 400);
            $todos[] = [
                'text'     => $text,
                'checked'  => false,
                'priority' => in_array($body['priority'] ?? '', ['low', 'medium', 'high'], true)
                                ? $body['priority'] : 'medium',
                'due'      => (string) ($body['due'] ?? date('Y-m-d', strtotime('+7 days'))),
            ];
            break;

        case 'delete':
            $i = (int) ($body['index'] ?? -1);
            if (!isset($todos[$i])) json_response(['error' => 'index out of range'], 400);
            array_splice($todos, $i, 1);
            break;

        default:
            json_response(['error' => 'unknown op. use toggle | add | delete'], 400);
    }

    save_json('lists', $data);
    json_response(['ok' => true, 'items' => $todos]);
} catch (Throwable $err) {
    json_response(['error' => $err->getMessage()], 500);
}
