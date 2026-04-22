<?php
declare(strict_types=1);

/**
 * Shared helpers for the Cobalt2 dashboard.
 *
 * This file is designed for practice: it deliberately uses a mix of common
 * PHP patterns — type declarations, exceptions, superglobal filtering,
 * JSON + file I/O with locking, HTML escaping, date formatting, partial
 * includes — so you can tinker with each piece in isolation.
 */

const DATA_DIR      = __DIR__ . '/../data';
const PARTIALS_DIR  = __DIR__ . '/../includes/partials';
const ALLOWED_FILES = ['lists', 'store-analytics', 'tables'];


/* ---------- output escaping ---------- */

/** Short alias for htmlspecialchars. Use this in every `<?= ?>` that echoes user data. */
function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


/* ---------- JSON data loader ---------- */

/**
 * Load data/{name}.json and return it as an associative array.
 * Throws if the file is missing, unreadable, or contains invalid JSON.
 */
function load_json(string $name): array {
    if (!in_array($name, ALLOWED_FILES, true)) {
        throw new InvalidArgumentException("unknown dataset: {$name}");
    }
    $path = DATA_DIR . "/{$name}.json";
    $raw  = @file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("cannot read {$path}");
    }
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Write data back to data/{name}.json atomically with an exclusive lock.
 * The lock prevents two concurrent POSTs from clobbering each other.
 */
function save_json(string $name, array $data): void {
    if (!in_array($name, ALLOWED_FILES, true)) {
        throw new InvalidArgumentException("unknown dataset: {$name}");
    }
    $path = DATA_DIR . "/{$name}.json";
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $json, LOCK_EX) === false) {
        throw new RuntimeException("cannot write {$path}");
    }
}


/* ---------- formatting ---------- */

function format_currency(int|float $amount, string $currency = 'USD'): string {
    $symbol = match ($currency) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $currency . ' ',
    };
    return $symbol . number_format((float) $amount, 2);
}

function format_number(int|float $n): string {
    return number_format((float) $n);
}

function format_date(string $iso, string $fmt = 'M j, Y'): string {
    return (new DateTimeImmutable($iso))->format($fmt);
}

/**
 * "in 2 days" / "3 hours ago" — tiny relative-time helper.
 */
function format_relative(string $iso, ?DateTimeImmutable $now = null): string {
    $now ??= new DateTimeImmutable();
    $then = new DateTimeImmutable($iso);
    $diff = $now->diff($then);

    $future = $then > $now;
    $unit = match (true) {
        $diff->y > 0 => [$diff->y, 'year'],
        $diff->m > 0 => [$diff->m, 'month'],
        $diff->d > 0 => [$diff->d, 'day'],
        $diff->h > 0 => [$diff->h, 'hour'],
        $diff->i > 0 => [$diff->i, 'minute'],
        default      => [0,        'second'],
    };
    [$n, $label] = $unit;
    if ($n === 0) return 'just now';
    $plural = $n === 1 ? $label : $label . 's';
    return $future ? "in {$n} {$plural}" : "{$n} {$plural} ago";
}


/* ---------- small view helpers ---------- */

/** Map a semantic accent ("success", "danger", ...) to a Bootstrap class prefix. */
function accent_class(string $accent, string $prefix = 'text-'): string {
    $valid = ['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark'];
    return $prefix . (in_array($accent, $valid, true) ? $accent : 'primary');
}

/**
 * Include a partial from includes/partials/{name}.php with $vars extracted
 * into local scope. Returns the rendered HTML instead of echoing.
 */
function partial(string $name, array $vars = []): string {
    $file = PARTIALS_DIR . "/{$name}.php";
    if (!is_file($file)) {
        throw new RuntimeException("missing partial: {$name}");
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}


/* ---------- request helpers ---------- */

/** Read a GET param with a default + optional allowlist. */
function get_param(string $key, string $default = '', array $allowed = []): string {
    $v = (string) filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
    if ($v === '') return $default;
    if ($allowed !== [] && !in_array($v, $allowed, true)) return $default;
    return $v;
}

/** Decode a JSON request body (for fetch() POSTs). */
function read_json_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    try {
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    } catch (JsonException) {
        return [];
    }
}

/** Send a JSON response and exit. */
function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}
