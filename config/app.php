<?php
// Di localhost aplikasi ada di /kakaocomfy, di InfinityFree di root.
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('BASE_URL', $isLocal ? '/kakaocomfy' : '');
define('APP_NAME', 'Kakao Comfy');