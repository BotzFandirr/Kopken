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
$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
