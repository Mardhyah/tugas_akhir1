<?php
$current_page = $_GET['page'] ?? '';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
include_once __DIR__ . '/../../fungsi.php';
require_once __DIR__ . '/../../crypto/core/crypto_helper.php';

checkSession();

$username = $_SESSION['username'];

$fromDate = $_GET['fromDate'] ?? '';
$toDate = $_GET['toDate'] ?? '';
$urutan = $_GET['urutan'] ?? 'terbaru'; // terbaru atau terlama
$jenisTransaksi = $_GET['jenisTransaksi'] ?? '';

// Query dasar
$query = "
    SELECT 
        t.date,
        t.jenis_transaksi,
        ss.jumlah_rp AS setor_rp,
        ss.jumlah_kg AS setor_kg,
        js.jumlah_rp AS jual_rp,
        js.jumlah_kg AS jual_kg
    FROM transaksi t
    LEFT JOIN setor_sampah ss ON t.id = ss.id_transaksi
    LEFT JOIN jual_sampah js ON t.id = js.id_transaksi
    WHERE 1=1
";

// Filter jenis transaksi
if (!empty($jenisTransaksi)) {
    $query .= " AND t.jenis_transaksi = '$jenisTransaksi'";
}

// Filter tanggal
if (!empty($fromDate)) {
    $query .= " AND t.date >= '$fromDate'";
}
if (!empty($toDate)) {
    $query .= " AND t.date <= '$toDate'";
}

// Urutan berdasarkan tanggal asli
$query .= ($urutan == 'terlama') ? " ORDER BY t.date ASC" : " ORDER BY t.date DESC";

$transaksi_result = mysqli_query($koneksi, $query);
if (!$transaksi_result) {
    die('Query failed: ' . mysqli_error($koneksi));
}

function isEncryptedFormat($data)
{
    if (empty($data)) return false;

    if (preg_match('/^[A-Za-z0-9\/\+=]+$/', $data) && strlen($data) > 24) {
        return true;
    }

    return false;
}

// Rekap manual di PHP per bulan, dari terbaru ke terlama
$rekap = [];
while ($row = mysqli_fetch_assoc($transaksi_result)) {
    $bulan = date('F Y', strtotime($row['date'])); // format bulan dari tanggal asli

    if (!isset($rekap[$bulan])) {
        $rekap[$bulan] = [
            'total_setor' => 0,
            'total_jual' => 0,
            'total_kg_setor' => 0,
            'total_kg_jual' => 0
        ];
    }

    if ($row['jenis_transaksi'] == 'setor_sampah') {
        $rp = isEncryptedFormat($row['setor_rp']) ? safeDecrypt($row['setor_rp']) : $row['setor_rp'];
        $rekap[$bulan]['total_setor'] += (float) $rp;
        $rekap[$bulan]['total_kg_setor'] += (float) $row['setor_kg'];
    } elseif ($row['jenis_transaksi'] == 'jual_sampah') {
        $rp = isEncryptedFormat($row['jual_rp']) ? safeDecrypt($row['jual_rp']) : $row['jual_rp'];
        $rekap[$bulan]['total_jual'] += (float) $rp;
        $rekap[$bulan]['total_kg_jual'] += (float) $row['jual_kg'];
    }
}


?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->

    <link rel="stylesheet" href="../../assets/css/style.css">

    <title>BankSampah</title>
</head>


<body>

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <?php include_once __DIR__ . '/../layouts/breadcrumb.php'; ?>

        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <span>Halaman</span>
                    <h1>Rekap Transaksi</h1>
                </div>
            </div>


            <div id="wrapper">

                <!-- Ini Main-Content -->
                <div class="main--content">
                    <div class="main--content--monitoring">
                        <div class="header--wrapper">

                            <button type="button" class="inputbtn" id="openModalBtn">
                                Filter Transaksi
                            </button>

                        </div>

                        <!-- Filter Modal -->
                        <!-- Modal Manual -->
                        <div id="filterTransaksiModal" class="custom-modal-rekap">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Transaksi</h5>
                                    <button type="button" class="close" id="closeModalBtn">&times;</button>
                                </div>
                                <form method="GET" action="">
                                    <input type="hidden" name="page" value="rekap_transaksi">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="fromDate">From</label>
                                            <input type="date" class="form-control" id="fromDate" name="fromDate" value="<?php echo $fromDate ?? ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="toDate">To</label>
                                            <input type="date" class="form-control" id="toDate" name="toDate" value="<?php echo $toDate ?? ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="urutan">Urutan</label>
                                            <select class="form-control" id="urutan" name="urutan">
                                                <option value="terbaru" <?php if (!empty($urutan) && $urutan == 'terbaru') echo 'selected'; ?>>Terbaru</option>
                                                <option value="terlama" <?php if (!empty($urutan) && $urutan == 'terlama') echo 'selected'; ?>>Terlama</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="jenisTransaksi">Jenis transaksi</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jenisTransaksi" id="setorSampah" value="setor_sampah" <?php if (!empty($jenisTransaksi) && $jenisTransaksi == 'setor_sampah') echo 'checked'; ?>>
                                                <label class="form-check-label" for="setorSampah">Setor sampah</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jenisTransaksi" id="jualSampah" value="jual_sampah" <?php if (!empty($jenisTransaksi) && $jenisTransaksi == 'jual_sampah') echo 'checked'; ?>>

                                                <label class="form-check-label" for="jualSampah">Jual sampah</label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jenisTransaksi" id="semuaJenis" value="" <?php if (empty($jenisTransaksi)) echo 'checked'; ?>>
                                                <label class="form-check-label" for="semuaJenis">Semua jenis</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn-close" onclick="closeModal()">Close</button>
                                        <button type="submit" class="btn-ok">OK</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Start of Transaction Table Section -->
                        <!-- Start of Transaction Table Section -->
                        <div class="tabular--wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Jenis</th>
                                        <th>Total (Rp)</th>
                                        <th>Total (Kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($rekap)): ?>
                                        <?php foreach ($rekap as $bulan => $data): ?>
                                            <?php
                                            $rowspan = 0;
                                            if ($data['total_setor'] > 0) $rowspan++;
                                            if ($data['total_jual'] > 0) $rowspan++;
                                            if ($rowspan == 0) continue;
                                            $firstRow = true;
                                            ?>
                                            <?php if ($data['total_setor'] > 0): ?>
                                                <tr>
                                                    <?php if ($firstRow): ?>
                                                        <td rowspan="<?= $rowspan ?>"><?= $bulan ?></td>
                                                        <?php $firstRow = false; ?>
                                                    <?php endif; ?>
                                                    <td>Setor</td>
                                                    <td><?= number_format($data['total_setor'], 0, ',', '.') ?></td>
                                                    <td><?= number_format($data['total_kg_setor'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php if ($data['total_jual'] > 0): ?>
                                                <tr>
                                                    <td>Jual</td>
                                                    <td><?= number_format($data['total_jual'], 0, ',', '.') ?></td>
                                                    <td><?= number_format($data['total_kg_jual'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No transactions found for the selected filters.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- End of Transaction Table Section -->

                        <!-- End of Transaction Table Section -->

                    </div>
                </div>
                <!-- Batas Akhir Main-Content -->
            </div>
            <!-- Bootstrap 4 atau 5 CSS di <head> -->




        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->


    <script src="script.js"></script>
    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
    <script>
        const modal = document.getElementById('filterTransaksiModal');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        openBtn.onclick = () => modal.style.display = 'block';
        closeBtn.onclick = () => modal.style.display = 'none';
        cancelBtn.onclick = () => modal.style.display = 'none';

        // Tutup modal jika klik di luar kotak modal
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    </script>

</body>

</html> <?php
        $current_page = $_GET['page'] ?? '';
        ?>