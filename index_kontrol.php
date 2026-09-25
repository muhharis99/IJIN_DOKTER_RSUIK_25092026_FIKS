<?php
$host = '192.168.0.33';
$user = 'admin';
$password = 'admin3dp';
$database = 'rme';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Tanggal hari ini
$today = date('Y-m-d');

$query = "
        SELECT rme.surat_kontrol.no_reg, rsiklaten.pasien.telp 
        FROM rme.surat_kontrol 
        INNER JOIN rsiklaten.pasien 
        ON rme.surat_kontrol.no_reg = rsiklaten.pasien.no_reg 
        WHERE rme.surat_kontrol.tgl_kontrol >= '$today'
        ORDER BY rme.surat_kontrol.tgl_kontrol DESC
        LIMIT 2000
    ";

$result = mysqli_query($conn, $query);

$data = [];

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['no_reg']] = $row['telp'];
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>WA Gateway - RSUIK</title>
    <link rel="icon" href="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./assets/css/master.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#"><i class="bi bi-whatsapp"></i>&nbsp;WA Gateway - RSU Islam Klaten</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item ">
                    <a class="nav-link" href="http://192.168.0.93/service_wa_with_venom">IJIN DOKTER</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="http://192.168.0.93/service_wa_with_venom/index_kontrol.php">KONTROL PASIEN</a>
                </li>
                <li class="nav-item">
                    &nbsp;&nbsp;&nbsp;&nbsp;
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-warning text-dark" href="https://wa.me/62895376257021?text=Service%20WA%20perlu%20direstart%20(PENDAFTARAN)"  target="_blank">
                        Klik disini jika WhatsApp terkendala
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <form id="" class="border p-4 bg-light rounded">
            <?php if (!empty($data)): ?>
            <div class="mb-3">
                <label for="numbers" class="form-label">Nomor (pisahkan dengan koma):</label>
                <input type="text" id="numbers" name="numbers" class="form-control" value="<?php echo implode(',', array_unique($data)); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Pesan:</label>
                <textarea id="message" name="message" class="form-control" rows="5" readonly>*Pengumuman RSU Islam Klaten* : &#10;&#10;<?php
                    $index = 1;
                    foreach ($data as $no_reg => $no_hp) {
                        echo $index . ". Pesan untuk No. Registrasi " . $no_reg . ": " . htmlspecialchars($no_hp) . "&#10;&#10;";
                        $index++;
                    }
                ?></textarea>
            </div>
            <!-- <div class="mb-3">
                <button type="button" id="editMessage" class="btn btn-secondary w-100 mb-2">Edit Pesan</button>
                <button type="button" id="cancelEdit" class="btn btn-secondary w-100 mb-2" style="display: none;">Tidak jadi edit</button>
            </div>
            <button type="submit" class="btn btn-primary w-100">Kirim Sekarang!</button> -->
            <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Tidak ada pesan yang perlu dikirim.
            </div>
            <?php endif; ?>
        </form>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-4" style="background-color: #28a745 !important;">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Developed by <a href="https://wa.me/62895376257021" class="sidebar-link" target="_blank">Muhammad Haris Chaidir</a></p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
</body>
</html>
