<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

if (sudah_login()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi form tidak valid. Muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $st = mysqli_prepare($koneksi,
            "SELECT id_user, username, password_hash, nama_lengkap, role
             FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $username);
        mysqli_stmt_execute($st);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['id_user']  = (int)$user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama_lengkap'];
            $_SESSION['role']     = $user['role'];
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}

$judul = 'Masuk';
$menu_aktif = '';
include __DIR__ . '/../includes/layout_atas.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5 col-lg-4">
    <div class="card kartu-kakao">
      <div class="card-body p-4">
        <div class="text-center mb-3">
          <i class="bi bi-house-heart-fill ikon-besar"></i>
          <h4 class="fw-bold mt-2 mb-0">Kakao Comfy</h4>
          <small class="text-secondary">Silakan masuk untuk melanjutkan</small>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-hijau w-100">Masuk</button>
        </form>

        <div class="small text-secondary mt-3">
          Akun demo (password <b>password123</b>):<br>
          <code>penjual1</code> (penjual), <code>pembeli1</code> (pembeli)
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>