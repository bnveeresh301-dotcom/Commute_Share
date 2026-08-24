<?php
require_once __DIR__ . '/../config.php';
require_login();
json_response(['ok'=>true,'api_key'=>GOOGLE_MAPS_API_KEY]);
