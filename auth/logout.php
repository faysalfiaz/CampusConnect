<?php
require_once __DIR__ . '/../includes/auth.php';
if (is_post()) { csrf_check(); if (user()) log_activity('logout'); logout_user(); }
redirect('index.php');
