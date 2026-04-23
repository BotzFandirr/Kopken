<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  echo json_encode(['ok' => true]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['raw']) || !is_array($data['raw'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Invalid JSON format']);
  exit;
}

$target = __DIR__ . DIRECTORY_SEPARATOR . 'products.json';
$existing = [];
if (file_exists($target)) {
  $prev = json_decode((string) file_get_contents($target), true);
  if (is_array($prev)) {
    $existing = $prev;
  }
}

if (!isset($existing['raw']) || !is_array($existing['raw'])) {
  $existing['raw'] = [];
}

$catMap = [];
foreach ($existing['raw'] as $idx => $cat) {
  if (isset($cat['gc'])) {
    $catMap[$cat['gc']] = $idx;
  }
}

foreach ($data['raw'] as $newCat) {
  if (!isset($newCat['gc'])) continue;
  $gc = $newCat['gc'];
  if (!isset($newCat['items']) || !is_array($newCat['items'])) {
    $newCat['items'] = [];
  }

  if (!array_key_exists($gc, $catMap)) {
    $existing['raw'][] = $newCat;
    $catMap[$gc] = count($existing['raw']) - 1;
    continue;
  }

  $existingCat = &$existing['raw'][$catMap[$gc]];
  if (isset($newCat['gn'])) $existingCat['gn'] = $newCat['gn'];
  if (!isset($existingCat['items']) || !is_array($existingCat['items'])) {
    $existingCat['items'] = [];
  }

  $itemMap = [];
  foreach ($existingCat['items'] as $i => $it) {
    $key = isset($it['n']) ? strtolower(trim((string) $it['n'])) : '';
    if ($key !== '') $itemMap[$key] = $i;
  }

  foreach ($newCat['items'] as $itNew) {
    $key = isset($itNew['n']) ? strtolower(trim((string) $itNew['n'])) : '';
    if ($key === '') continue;
    if (array_key_exists($key, $itemMap)) {
      $existingCat['items'][$itemMap[$key]] = array_merge($existingCat['items'][$itemMap[$key]], $itNew);
    } else {
      $existingCat['items'][] = $itNew;
    }
  }
  unset($existingCat);
}

$existing['icons'] = array_merge(
  (isset($existing['icons']) && is_array($existing['icons'])) ? $existing['icons'] : [],
  (isset($data['icons']) && is_array($data['icons'])) ? $data['icons'] : []
);

$modal = array_values(array_unique(array_merge(
  (isset($existing['modalGn']) && is_array($existing['modalGn'])) ? $existing['modalGn'] : [],
  (isset($data['modalGn']) && is_array($data['modalGn'])) ? $data['modalGn'] : []
)));
$existing['modalGn'] = $modal;

$encoded = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Encode failed']);
  exit;
}

$result = @file_put_contents($target, $encoded . PHP_EOL, LOCK_EX);
if ($result === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Write failed (check file permission)']);
  exit;
}

echo json_encode(['ok' => true, 'message' => 'products.json updated']);
