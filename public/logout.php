<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

requirePostRequest();
requireValidCsrfToken();
destroyCurrentSession();
redirectTo('/');
