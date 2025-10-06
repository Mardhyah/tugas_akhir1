<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendmail_verify($email, $verify_token, $username, $plain_password)
{
    require __DIR__ . '/../../vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'banksampah747@gmail.com';
        $mail->Password   = 'tyyhcrkpcojftwuh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('banksampah747@gmail.com', 'Bank Sampah');
        $mail->addAddress($email, $username);
        $mail->addReplyTo('no-reply@banksampah.com', 'Information');

        // Email Content
        $mail->isHTML(true);

        $verification_link = "http://localhost/bank_sampah1/views/auth/verify_email.php?token=$verify_token";
        $email_template = '
<div style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #2e7d32;">Selamat Datang di Bank Sampah!</h2>
    <p>Halo <b>' . htmlspecialchars($username) . '</b>,</p>
    <p>Terima kasih telah melakukan pendaftaran akun di <strong>Bank Sampah</strong>.</p>

    <p><b>Detail Akun Anda:</b></p>
    <ul>
        <li>Username: <b>' . htmlspecialchars($username) . '</b></li>
        <li>Password: <b>' . htmlspecialchars($plain_password) . '</b></li>
    </ul>

    <p>Untuk mengaktifkan akunmu, silakan verifikasi email dengan mengklik tombol di bawah ini:</p>
    <p style="margin: 20px 0;">
        <a href="' . $verification_link . '"
           style="background-color: #2e7d32; color: white; padding: 10px 20px; 
                  text-decoration: none; border-radius: 5px; display: inline-block;">
            Verifikasi Email Sekarang
        </a>
    </p>
    <p>Salam hangat,<br><strong>Tim Bank Sampah</strong></p>
</div>
';


        $mail->Subject = 'Verifikasi Email Akun Bank Sampah';
        $mail->Body    = $email_template;
        $mail->AltBody = "Halo $username, akunmu berhasil didaftarkan. Username: $username, Password: $plain_password. Silakan verifikasi email melalui link ini: $verification_link";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email tidak terkirim: {$mail->ErrorInfo}");
        return false;
    }
}


// Ambil data role dari session jika pengguna login
$loggedInRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Tentukan pilihan role yang bisa diakses
$roles = [];
if ($loggedInRole === 'admin') {
    $roles = ['admin', 'nasabah'];
} else {
    $roles = ['nasabah'];
    $autoRole = 'nasabah';
}

// Pindahkan 'nasabah' ke posisi pertama jika ada
if (($key = array_search('nasabah', $roles)) !== false) {
    unset($roles[$key]);
    array_unshift($roles, 'nasabah');
}

// Inisialisasi variabel
$err = '';
$username = $nama = $email = $notelp = $nik = $alamat = $tgl_lahir = $kelamin = '';

// Ambil nomor rekening terakhir dari database
$last_rek_query = "SELECT MAX(CAST(SUBSTRING(no_rek, 5, 4) AS UNSIGNED)) AS last_rek FROM user WHERE no_rek LIKE 'BSLH%'";
$last_rek_result = mysqli_query($koneksi, $last_rek_query);
$last_rek_number = 0;

if ($last_rek_result && mysqli_num_rows($last_rek_result) > 0) {
    $last_rek_row = mysqli_fetch_assoc($last_rek_result);
    if ($last_rek_row['last_rek'] !== null) {
        $last_rek_number = intval($last_rek_row['last_rek']);
    }
}

// Buat nomor rekening baru dengan format "BSLH0001", "BSLH0002"
$new_rek_number = $last_rek_number + 1;
$new_no_rek = "BSLH" . str_pad($new_rek_number, 4, "0", STR_PAD_LEFT);


$username = $nama = $email = $notelp = $nik = $nip = $alamat = $tgl_lahir = $kelamin = "";
$message = "";
// Proses form registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form (supaya bisa dipakai lagi untuk isi ulang form)
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $notelp = isset($_POST['notelp']) ? trim($_POST['notelp']) : '';
    $nik = isset($_POST['nik']) ? trim($_POST['nik']) : '';
    $nip = isset($_POST['nip']) ? trim($_POST['nip']) : '';
    $status_gol = isset($_POST['status_gol']) ? $_POST['status_gol'] : '';
    $gol = ($status_gol === 'CPNS' && isset($_POST['gol'])) ? $_POST['gol'] : '';
    $bidang = isset($_POST['bidang']) ? $_POST['bidang'] : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    $tgl_lahir = isset($_POST['tgl_lahir']) ? trim($_POST['tgl_lahir']) : '';
    $kelamin = isset($_POST['kelamin']) ? $_POST['kelamin'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'nasabah';
    $status = 1;
    $verify_status = 'pending';


    // Jika pengguna tidak login, role otomatis adalah 'nasabah'
    if (!isset($loggedInRole)) {
        $role = 'nasabah';
    }

    // ✅ Validasi input
    if (
        empty($username) || empty($password) || empty($confirm_password) ||
        empty($nama) || empty($email) || empty($notelp) ||
        empty($nik) || empty($alamat) || empty($tgl_lahir) || empty($kelamin)
    ) {
        $_SESSION['message'] = "Semua field wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $_SESSION['message'] = "Password dan Konfirmasi Password tidak sama!";
    } elseif (strlen($password) < 6) {
        $_SESSION['message'] = "Password minimal 6 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Format email tidak valid!";
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $_SESSION['message'] = "NIK harus 16 digit angka!";
    } else {
        // ✅ Cek apakah username sudah digunakan
        $check_query = "SELECT username FROM user WHERE username = ?";
        $check_stmt = mysqli_prepare($koneksi, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $_SESSION['message'] = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // ✅ Cek apakah email sudah digunakan
            $check_email_query = "SELECT email FROM user WHERE email = ?";
            $check_email_stmt = mysqli_prepare($koneksi, $check_email_query);
            mysqli_stmt_bind_param($check_email_stmt, "s", $email);
            mysqli_stmt_execute($check_email_stmt);
            mysqli_stmt_store_result($check_email_stmt);

            if (mysqli_stmt_num_rows($check_email_stmt) > 0) {
                $_SESSION['message'] = "Email sudah digunakan. Silakan gunakan email lain.";
            } else {
                // ✅ Data aman → lakukan insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verify_token = md5(uniqid($username, true));

                $insert_query = "INSERT INTO user 
            (username, password, nama, role, email, notelp, nik, alamat, tgl_lahir, kelamin, no_rek, status, gol, bidang, nip, verify_status, verify_token) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $insert_stmt = mysqli_prepare($koneksi, $insert_query);
                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "sssssssssssssssss",
                    $username,
                    $hashed_password,
                    $nama,
                    $role,
                    $email,
                    $notelp,
                    $nik,
                    $alamat,
                    $tgl_lahir,
                    $kelamin,
                    $new_no_rek,
                    $status,
                    $gol,
                    $bidang,
                    $nip,
                    $verify_status,
                    $verify_token
                );

                if (mysqli_stmt_execute($insert_stmt)) {
                    // Kirim email verifikasi
                    sendmail_verify($email, $verify_token, $username, $password);

                    $_SESSION['message'] = "Register berhasil, silakan cek email untuk verifikasi.";
                    header("location: index.php?page=register_nasabah");
                    exit;
                } else {
                    $_SESSION['message'] = "Gagal register";
                    header("location: index.php?page=register_nasabah");
                    exit;
                }

                mysqli_stmt_close($insert_stmt);
            }

            mysqli_stmt_close($check_email_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register Nasabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



</head>
<style>
    * {
        box-sizing: border-box;
    }

    body,
    html {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        height: 100%;
        overflow-y: auto;
        background-color: #f2f2f2;
    }

    /* container-regis UTAMA */
    .container-regis {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }

    /* FORM SECTION */
    .signin-section {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        padding: 40px;
        position: relative;
        background-color: #ffffff;
    }

    .signin-section h2 {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
        width: 100%;
    }

    /* FORM WRAPPER */
    .form-container-regis {
        width: 100%;
        max-width: 600px;
        overflow-y: auto;
        max-height: calc(100vh - 140px);
        padding-right: 10px;
        padding: 20px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    /* FORM */
    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
    }

    label {
        margin-bottom: 6px;
        font-weight: 500;
    }

    input,
    select,
    textarea {
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
        background-color: #f9f9f9;
    }

    textarea {
        resize: vertical;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
    }

    .inputbtn {
        padding: 12px 24px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-cancel {
        background-color: #ccc;
        color: black;
    }

    .btn-cancel:hover {
        background-color: #aaa;
    }

    .inputbtn[type="submit"],
    .inputbtn:not(.btn-cancel) {
        background-color: #25745A;
        color: white;
    }

    .inputbtn[type="submit"]:hover,
    .inputbtn:not(.btn-cancel):hover {
        background-color: #1e5e49;
    }

    /* INFO SECTION */
    .info-section {
        flex: 1;
        background: #25745A;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        padding: 60px 40px;
        text-align: center;
    }

    .info-section h2 {
        font-size: 36px;
        margin-bottom: 20px;
    }

    .info-section p {
        max-width: 500px;
        font-size: 20px;
        line-height: 1.6;
    }

    /* Responsif Mobile */
    /* Responsif Mobile dan Tablet */
    @media (max-width: 900px) {

        html,
        body {
            height: auto;
            overflow-y: auto;
        }

        .container-regis {
            flex-direction: column;
            height: auto;
            overflow-y: auto;
        }

        .signin-section,
        .info-section {
            width: 100%;
            min-height: auto;
            padding: 30px 20px;
            align-items: center;
            justify-content: center;
        }

        .info-section {
            order: -1;
            /* Pindahkan info-section ke atas di mobile */
            padding: 40px 20px;
        }

        .info-section h2 {
            font-size: 28px;
        }

        .info-section p {
            font-size: 16px;
        }

        .signin-section h2 {
            font-size: 28px;
        }

        .form-container-regis {
            max-height: none;
            overflow-y: visible;
            padding-right: 0;
        }

        .inputbtn,
        .btn-signup {
            width: 100%;
            text-align: center;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }
    }


    .btn-signup {
        margin-top: 40px;
        padding: 14px 28px;
        background-color: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        border-radius: 35px;
        font-size: 18px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-signup:hover {
        background-color: rgba(255, 255, 255, 0.3);
    }
</style>

<body>
    <div class="container-regis">
        <div class="signin-section">

            <h2>Form Pendaftaran Nasabah</h2>
            <div class="form-container-regis">

                <?php if (isset($_SESSION['message'])): ?>
                    <?php
                    $isSuccess = stripos($_SESSION['message'], 'berhasil') !== false;
                    ?>
                    <div class="alert alert-<?php echo $isSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <!-- Tambah validasi password lewat JS -->
                <form method="POST" action="" class="form-scrollable" onsubmit="return validatePassword()">
                    <div class="form-group">
                        <div class="form-field">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Username" required
                                value="<?= htmlspecialchars($username ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>Nama</label>
                            <input type="text" name="nama" placeholder="Nama Lengkap" required
                                value="<?= htmlspecialchars($nama ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>Password</label>
                            <input type="password" name="password" id="password" placeholder="Password" required />
                        </div>
                        <div class="form-field">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Ulangi Password" required />
                        </div>
                        <div class="form-field">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" required
                                value="<?= htmlspecialchars($tgl_lahir ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="email@example.com" required
                                value="<?= htmlspecialchars($email ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>Jenis Kelamin</label>
                            <select name="kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-Laki" <?= (isset($kelamin) && $kelamin === 'Laki-Laki') ? 'selected' : '' ?>>Laki-Laki</option>
                                <option value="Perempuan" <?= (isset($kelamin) && $kelamin === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Bidang</label>
                            <input type="text" name="bidang" placeholder="Bidang"
                                value="<?= htmlspecialchars($bidang ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>NIK</label>
                            <input type="text" name="nik" placeholder="NIK (16 digit)" maxlength="16"
                                pattern="\d{16}" title="NIK harus terdiri dari 16 digit angka" required
                                value="<?= htmlspecialchars($nik ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>No. Telp</label>
                            <input type="text" name="notelp" placeholder="08..." required
                                value="<?= htmlspecialchars($notelp ?? '') ?>" />
                        </div>
                        <div class="form-field">
                            <label>Status Kepegawaian</label>
                            <select name="status_gol" id="status_gol" onchange="toggleGolongan()" required>
                                <option value="">Pilih Status</option>
                                <option value="PNS" <?= (isset($status_gol) && $status_gol === 'PNS') ? 'selected' : '' ?>>PNS</option>
                                <option value="Honorer" <?= (isset($status_gol) && $status_gol === 'Honorer') ? 'selected' : '' ?>>Non-PNS</option>
                            </select>
                        </div>

                        <div class="form-group" id="golongan_nip_wrapper" style="display: <?= (isset($status_gol) && $status_gol === 'PNS') ? 'block' : 'none' ?>;">
                            <div class="form-field">
                                <label>Golongan</label>
                                <select name="gol">
                                    <option value="">Pilih Golongan</option>
                                    <option value="I/A" <?= (isset($gol) && $gol === 'I/A') ? 'selected' : '' ?>>I/A</option>
                                    <option value="I/B" <?= (isset($gol) && $gol === 'I/B') ? 'selected' : '' ?>>I/B</option>
                                    <option value="II/A" <?= (isset($gol) && $gol === 'II/A') ? 'selected' : '' ?>>II/A</option>
                                    <option value="II/B" <?= (isset($gol) && $gol === 'II/B') ? 'selected' : '' ?>>II/B</option>
                                    <option value="III/A" <?= (isset($gol) && $gol === 'III/A') ? 'selected' : '' ?>>III/A</option>
                                    <option value="III/B" <?= (isset($gol) && $gol === 'III/B') ? 'selected' : '' ?>>III/B</option>
                                    <option value="IV/A" <?= (isset($gol) && $gol === 'IV/A') ? 'selected' : '' ?>>IV/A</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>NIP</label>
                                <input type="text" name="nip" placeholder="NIP"
                                    value="<?= htmlspecialchars($nip ?? '') ?>" />
                            </div>
                        </div>

                        <div class="form-field">
                            <label>Alamat</label>
                            <textarea name="alamat" placeholder="Alamat lengkap..." rows="3" required><?= htmlspecialchars($alamat ?? '') ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="inputbtn">Daftar</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        <div class="info-section">
            <h2>Selamat Datang!</h2>
            <p>Silakan isi data lengkap Anda untuk mendaftar sebagai nasabah. Kami akan menjaga kerahasiaan data Anda.</p>
            <button class="btn-signup" onclick="window.location='index.php?page=login'">Sudah punya akun? Login</button>
        </div>
    </div>

    <script>
        function toggleGolongan() {
            const status = document.getElementById("status_gol").value;
            const wrapper = document.getElementById("golongan_nip_wrapper");
            wrapper.style.display = status === "PNS" ? "flex" : "none";
        }
        window.addEventListener('DOMContentLoaded', toggleGolongan);

        // function validatePassword() {
        //     const password = document.getElementById("password").value;
        //     const confirmPassword = document.getElementById("confirm_password").value;
        //     if (password !== confirmPassword) {
        //         alert("Password dan Konfirmasi Password tidak sama!");
        //         return false; // cegah submit form
        //     }
        //     return true;
        // }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>