<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
logout_user();
flash('You have been logged out.', 'success');
redirect('index.php');
