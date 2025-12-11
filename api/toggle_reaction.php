<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
  echo json_encode(['success' => false, 'message' => 'Not authenticated']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid method']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$entryId = isset($input['entry_id']) ? (int)$input['entry_id'] : 0;
$userId = current_user_id();

if ($entryId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid entry']);
  exit;
}

try {
  $pdo = db();
  
  // Check if already reacted
  $stmt = $pdo->prepare('SELECT reaction_id FROM reactions WHERE entry_id = ? AND user_id = ?');
  $stmt->execute([$entryId, $userId]);
  $existing = $stmt->fetch();
  
  if ($existing) {
    // Remove reaction
    $del = $pdo->prepare('DELETE FROM reactions WHERE reaction_id = ?');
    $del->execute([$existing['reaction_id']]);
    $reacted = false;
  } else {
    // Add reaction
    $ins = $pdo->prepare('INSERT INTO reactions (entry_id, user_id, reaction_type) VALUES (?, ?, ?)');
    $ins->execute([$entryId, $userId, 'like']);
    $reacted = true;
  }
  
  // Get updated count
  $count = $pdo->prepare('SELECT COUNT(*) FROM reactions WHERE entry_id = ?');
  $count->execute([$entryId]);
  $total = $count->fetchColumn();
  
  echo json_encode([
    'success' => true,
    'reacted' => $reacted,
    'count' => $total
  ]);
  
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
