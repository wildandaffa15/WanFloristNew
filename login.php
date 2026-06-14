<?php
/**
 * login.php
 *
 * Halaman login admin WanFlorist.
 * Berdiri sendiri — tidak menggunakan navbar atau footer publik.
 *
 * Alur:
 *   GET  → Tampilkan form, generate CSRF token.
 *   POST → Validasi CSRF, rate limiting per IP, autentikasi via DB,
 *           set session, redirect ke admin/index.php.
 *
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.7
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ */
/* 0. Bootstrap — session, helpers                                      */
/* ------------------------------------------------------------------ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

/* Jika sudah login, langsung redirect ke dashboard */
if (isset($_SESSION['id_pengguna'])) {
    header('Location: admin/index.php', true, 302);
    exit;
}

/* ------------------------------------------------------------------ */
/* 1. Inisialisasi variabel tampilan                                     */
/* ------------------------------------------------------------------ */

$error_message  = '';   // Pesan error yang ditampilkan di form
$blocked        = false; // Flag: apakah IP sedang diblokir?
$username_value = '';   // Nilai field username untuk repopulate form

/* ------------------------------------------------------------------ */
/* 2. Rate limiting — cek status blokir untuk IP saat ini              */
/* ------------------------------------------------------------------ */

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Struktur session rate limit:
// $_SESSION['login_attempts'][$ip] = ['count' => int, 'first_try' => int]
$attempts_data = $_SESSION['login_attempts'][$ip] ?? null;

if (
    $attempts_data !== null
    && $attempts_data['count'] >= 5
    && (time() - $attempts_data['first_try']) < 900 // 15 menit = 900 detik
) {
    $blocked       = true;
    $sisa_detik    = 900 - (time() - $attempts_data['first_try']);
    $sisa_menit    = (int) ceil($sisa_detik / 60);
    $error_message = 'Terlalu banyak percobaan login. Akses dari IP Anda diblokir sementara. '
                   . 'Coba lagi dalam ±' . $sisa_menit . ' menit.';
}

/* ------------------------------------------------------------------ */
/* 3. Handler POST — hanya diproses jika tidak diblokir                */
/* ------------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$blocked) {

    /* 3.1 Validasi CSRF */
    $csrf_input = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_input)) {
        http_response_code(403);
        $error_message = 'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {

        /* 3.2 Ambil dan sanitasi input */
        $username_input  = trim($_POST['username'] ?? '');
        $password_input  = $_POST['password'] ?? '';
        $username_value  = $username_input; // repopulate field

        if ($username_input === '' || $password_input === '') {
            $error_message = 'Username dan password tidak boleh kosong.';
        } else {

            /* 3.3 Query pengguna dari database (PDO prepared statement) */
            $pdo  = get_pdo();
            $stmt = $pdo->prepare(
                "SELECT id_pengguna, username, password, role, is_active
                 FROM pengguna
                 WHERE username = :username
                 LIMIT 1"
            );
            $stmt->execute([':username' => $username_input]);
            $pengguna = $stmt->fetch();

            /* 3.4 Verifikasi password */
            $login_ok = false;
            if (
                $pengguna !== false
                && (bool) $pengguna['is_active'] === true
                && password_verify($password_input, $pengguna['password'])
            ) {
                $login_ok = true;
            }

            if (!$login_ok) {
                /* -------- LOGIN GAGAL: increment counter -------- */
                if (!isset($_SESSION['login_attempts'][$ip])) {
                    $_SESSION['login_attempts'][$ip] = [
                        'count'     => 0,
                        'first_try' => time(),
                    ];
                }
                $_SESSION['login_attempts'][$ip]['count']++;

                $sisa_percobaan = 5 - $_SESSION['login_attempts'][$ip]['count'];
                if ($sisa_percobaan > 0) {
                    $error_message = 'Username atau password salah.';
                } else {
                    // Baru saja mencapai batas — set blocked untuk tampilan
                    $blocked       = true;
                    $error_message = 'Terlalu banyak percobaan login. Akses dari IP Anda diblokir sementara selama 15 menit.';
                }
            } else {
                /* -------- LOGIN BERHASIL -------- */

                // Cegah session fixation
                session_regenerate_id(true);

                // Set session pengguna
                $_SESSION['id_pengguna'] = $pengguna['id_pengguna'];
                $_SESSION['username']    = $pengguna['username'];
                $_SESSION['role']        = $pengguna['role'];

                // Reset counter login attempts untuk IP ini
                unset($_SESSION['login_attempts'][$ip]);

                // Redirect ke dashboard admin (PRG pattern)
                header('Location: admin/index.php', true, 302);
                exit;
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* 4. Generate CSRF token untuk form (GET atau setelah POST gagal)      */
/* ------------------------------------------------------------------ */

$csrf_token = generate_csrf();

/* ------------------------------------------------------------------ */
/* 5. Persiapan variabel template                                        */
/* ------------------------------------------------------------------ */

$page_title = 'Login Admin';
$css_extra  = '/assets/css/admin.css';

?>
<!DOCTYPE html>
<html lang="id">
<?php require_once __DIR__ . '/components/head.php'; ?>
<body class="login-body">

<div class="login-wrapper">

    <!-- ============================================================
         Panel Kiri — Branding (hanya tampil di desktop)
         ============================================================ -->
    <div class="login-brand-panel" aria-hidden="true">
        <div class="login-brand-overlay"></div>
        <div class="login-brand-content">
            <div class="login-brand-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="52" height="52" fill="none" aria-hidden="true">
                    <path d="M24 4C18 4 13 9 13 15c0 4.5 2.5 8.4 6.2 10.5C16.5 27.5 14 31.4 14 36c0 4.4 3.6 8 8 8h4c4.4 0 8-3.6 8-8 0-4.6-2.5-8.5-6.2-10.5C31.5 23.4 34 19.5 34 15c0-6-5-11-10-11z" fill="#E9D5FF" opacity=".9"/>
                    <circle cx="24" cy="14" r="5" fill="#dfb7ff"/>
                    <circle cx="15" cy="22" r="4" fill="#dfb7ff" opacity=".7"/>
                    <circle cx="33" cy="22" r="4" fill="#dfb7ff" opacity=".7"/>
                </svg>
            </div>
            <h1 class="login-brand-title">WanFlorist</h1>
            <div class="login-brand-divider"></div>
            <p class="login-brand-tagline">
                Sistem Manajemen Butik Bunga.<br>
                Kelola pesanan, inventaris, dan pelanggan<br>dengan elegan dan efisien.
            </p>
        </div>
    </div>

    <!-- ============================================================
         Panel Kanan — Form Login
         ============================================================ -->
    <div class="login-form-panel">

        <!-- Logo mobile (hanya tampil di layar kecil) -->
        <div class="login-mobile-logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="28" height="28" fill="none" aria-hidden="true">
                <path d="M24 4C18 4 13 9 13 15c0 4.5 2.5 8.4 6.2 10.5C16.5 27.5 14 31.4 14 36c0 4.4 3.6 8 8 8h4c4.4 0 8-3.6 8-8 0-4.6-2.5-8.5-6.2-10.5C31.5 23.4 34 19.5 34 15c0-6-5-11-10-11z" fill="#6B21A8"/>
            </svg>
            <span>WanFlorist</span>
        </div>

        <div class="login-form-inner">

            <!-- Heading -->
            <div class="login-heading">
                <h2>Masuk ke Panel Admin</h2>
                <p>Silakan masukkan kredensial Anda untuk melanjutkan.</p>
            </div>

            <!-- Alert error / blokir -->
            <?php if ($error_message !== ''): ?>
            <div class="alert <?= $blocked ? 'alert-warning' : 'alert-danger' ?>" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" style="flex-shrink:0;margin-top:1px">
                    <?php if ($blocked): ?>
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    <?php else: ?>
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    <?php endif; ?>
                </svg>
                <?= e($error_message) ?>
            </div>
            <?php endif; ?>

            <!-- Form login -->
            <form method="POST" action="/login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <!-- Field Username -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="login-input-wrapper">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control login-input-field<?= ($error_message !== '' && !$blocked) ? ' input-error' : '' ?>"
                            placeholder="Masukkan username"
                            value="<?= e($username_value) ?>"
                            autocomplete="username"
                            required
                            <?= $blocked ? 'disabled' : '' ?>
                        >
                    </div>
                </div>

                <!-- Field Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="login-input-wrapper">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control login-input-field login-input-field--password<?= ($error_message !== '' && !$blocked) ? ' input-error' : '' ?>"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                            <?= $blocked ? 'disabled' : '' ?>
                        >
                        <button
                            type="button"
                            class="login-toggle-password"
                            id="togglePasswordBtn"
                            aria-label="Tampilkan atau sembunyikan password"
                            title="Tampilkan / sembunyikan password"
                        >
                            <!-- Ikon "mata tertutup" — ditampilkan saat password tersembunyi -->
                            <svg id="iconPasswordHide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z"/>
                                <path d="M10.748 13.93l2.523 2.524a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z"/>
                            </svg>
                            <!-- Ikon "mata terbuka" — ditampilkan saat password terlihat -->
                            <svg id="iconPasswordShow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="display:none">
                                <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Tombol submit -->
                <div class="form-group" style="margin-top: 28px; margin-bottom: 0;">
                    <button
                        type="submit"
                        class="btn btn-primary btn-block btn-lg"
                        <?= $blocked ? 'disabled' : '' ?>
                    >
                        Masuk
                    </button>
                </div>
            </form>

            <!-- Catatan keamanan -->
            <div class="login-security-note">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.589 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.563 2 12.162 2 7c0-.538.035-1.069.104-1.589a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.749z" clip-rule="evenodd"/>
                </svg>
                Akses sistem diamankan dengan enkripsi
            </div>

        </div><!-- /.login-form-inner -->
    </div><!-- /.login-form-panel -->

</div><!-- /.login-wrapper -->

<script>
/**
 * Toggle visibilitas password field.
 * Menggunakan addEventListener — tidak ada onclick="" inline.
 * Requirements: 17.4, 17.5
 */
document.addEventListener('DOMContentLoaded', function () {
    var btn         = document.getElementById('togglePasswordBtn');
    var passField   = document.getElementById('password');
    var iconHide    = document.getElementById('iconPasswordHide');
    var iconShow    = document.getElementById('iconPasswordShow');

    if (!btn || !passField) return;

    btn.addEventListener('click', function () {
        var isPassword = passField.type === 'password';
        passField.type = isPassword ? 'text' : 'password';
        iconHide.style.display = isPassword ? 'none'  : '';
        iconShow.style.display = isPassword ? ''      : 'none';
        btn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
    });
});
</script>

</body>
</html>
