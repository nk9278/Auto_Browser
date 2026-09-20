<?php

require_once __DIR__ . '/../app/Auth.php';

Auth::logout();
header('Location: /login.php');
exit;
