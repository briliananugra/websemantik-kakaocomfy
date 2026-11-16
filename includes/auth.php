<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';

function sudah_login() {
    return !empty($_SESSION['id_user']);
}

function wajib_login() {
    if (!sudah_login()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// contoh: wajib_role(['penjual'])
function wajib_role(array $boleh) {
    wajib_login();
    if (!in_array($_SESSION['role'], $boleh, true)) {
        http_response_code(403);
        exit('Akses ditolak: role Anda tidak berwenang membuka halaman ini.');
    }
}