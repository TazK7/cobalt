<?php
/**
 * GET /api/data.php?file=lists   → returns data/lists.json
 * GET /api/data.php?file=tables  → returns data/tables.json
 *
 * Practice ideas:
 *   - add a ?section= param to return just one key (e.g. "authors")
 *   - add an If-Modified-Since/ETag response
 *   - require a simple token via the Authorization header
 */
declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'method not allowed'], 405);
}

$file = get_param('file', '', ALLOWED_FILES);
if ($file === '') {
    json_response(['error' => 'missing or invalid "file" parameter', 'allowed' => ALLOWED_FILES], 400);
}

try {
    json_response(load_json($file));
} catch (Throwable $err) {
    json_response(['error' => $err->getMessage()], 500);
}
