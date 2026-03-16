<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

require_method('POST');

session_name(SESSION_NAME);
session_start();
session_unset();
session_destroy();

json_ok(['message' => 'Logged out.']);
