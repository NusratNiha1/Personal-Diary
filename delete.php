<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_login();
require_post();
$pdo = get_pdo();

$entryId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Verify ownership
$chk = $pdo->prepare('SELECT entry_id FROM entries WHERE entry_id = ? AND user_id = ?');
$chk->execute([$entryId, current_user_id()]);
if (!$chk->fetchColumn()) {
  flash('Entry not found', 'error');
  redirect('dashboard.php');
}

// Fetch media to delete files from disk
$mstmt = $pdo->prepare('SELECT file_path FROM media WHERE entry_id = ?');
$mstmt->execute([$entryId]);
$files = $mstmt->fetchAll();
foreach ($files as $f) {
  $abs = __DIR__ . DIRECTORY_SEPARATOR . $f['file_path'];
  if (is_file($abs)) { @unlink($abs); }
}

// Delete entry (will cascade delete media rows)
$del = $pdo->prepare('DELETE FROM entries WHERE entry_id = ? AND user_id = ?');
$del->execute([$entryId, current_user_id()]);

flash('Entry deleted.', 'error');
redirect('dashboard.php');
