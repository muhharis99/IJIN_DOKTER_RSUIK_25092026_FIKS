<?php
$host = '192.168.0.33';
$user = 'admin';
$password = 'admin3dp';
$database = 'rsiklaten';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$query = "
SELECT batal_wa.no_hp as no_wa, batal_wa.pesan, pas_wa.telp as no_hp
FROM batal_praktek_detil_wa as batal_wa
LEFT JOIN rsiklaten.pasien as pas_wa on batal_wa.no_reg = pas_wa.no_reg 
WHERE batal_wa.status IS NULL OR batal_wa.status = ''";
$result = mysqli_query($conn, $query);

$data = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['pesan']][] = $row['no_hp'];
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
                <!-- <li class="nav-item active">
                    <a class="nav-link" href="http://192.168.0.93/service_wa_with_venom">IJIN DOKTER</a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link btn btn-warning text-dark" href="#" 
                       id="btnScanWA">
                        Scan Ulang WhatsApp
                    </a>
                </li>

                <li class="nav-item">
                    &nbsp;&nbsp;&nbsp;&nbsp;
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link btn btn-warning text-dark" href="https://wa.me/62895376257021?text=Service%20WA%20perlu%20direstart%20(PENDAFTARAN)"  target="_blank">
                        Klik disini jika WhatsApp terkendala
                    </a>
                </li> -->
            </ul>
        </div>
    </nav>
    <div class="container mt-3">
        <div style="border-radius: 0.3rem;" class="alert-danger"><b><u>Waktu kirim pesan : 7 detik per nomor</u></b></div>
        <div style="border-radius: 0.3rem;" class="alert-success">
          Jika terjadi kesalahan saat proses pengiriman, silakan lakukan <b>refresh halaman</b> lalu kirim ulang pesan.<br>
          Pesan yang sudah berhasil terkirim sebelumnya <b>sudah terkirim ke pasien.</b>
        </div>
        <br>
        <!-- <u>Sebelum kirim pesan, usahakan icon <img src="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png" width="2%"> di atas tittle sudah muncul.</u> -->
        <form id="whatsappForm" class="border p-4 bg-light rounded">
            <?php if (!empty($data)): ?>
            <div class="mb-3">
                <label for="numbers" class="form-label">Nomor (pisahkan dengan koma):</label>
                <input type="text" id="numbers" name="numbers" class="form-control" value="<?php echo implode(',', array_unique(call_user_func_array('array_merge', array_values($data)))); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Pesan:</label>
                <textarea id="message" name="message" class="form-control" rows="5" readonly>
📢 *Pengumuman RSU Islam Klaten*

Assalâmu’alaikum wr wb

#SahabatSehatRSIKlaten Kami beritahukan perubahan jam praktik sebagai berikut :

<?php $index = 1;
foreach ($data as $pesan => $no_hp_array) {
$pesan = str_replace("Assalamu'alaikum. ", "", $pesan);
$pesan = str_replace("Mohon ma'af atas ketidaknyamanannya. Mks (RSI KLATEN)", "", $pesan);
$pesan = str_replace("Mohon maaf atas ketidaknyamanannya. Mks (RSI KLATEN)", "", $pesan);
$pesan = str_replace("Kami beritahukan bahwa praktek ", "", $pesan);
$pesan = str_replace("Kami beritahukan bahwa ", "", $pesan);
echo $index . ". " . htmlspecialchars($pesan) . "&#10;&#10;";
$index++;
}
?>
Mohon ma'af atas ketidaknyamanannya. Terimakasih 😊🙏🏻

Wassalamu'alaikum wr wb
—

*RSU Islam Klaten*
_Ramah, Amanah, Profesional, Islami_ (RAPI)
            </textarea>
            </div>
            <div class="mb-3">
                <button type="button" id="editMessage" class="btn btn-secondary w-100 mb-2">Edit Pesan</button>
                <button type="button" id="cancelEdit" class="btn btn-secondary w-100 mb-2" style="display: none;">Tidak jadi edit</button>
            </div>
            <button type="submit" class="btn btn-primary w-100">Kirim Sekarang!</button>
            <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Tidak ada pesan yang perlu dikirim.
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="container">
        <hr>
        <div class="row mb-12">
            <div class="col-md-3">
                <label for="noReg">Filter by No. Rekam Medis:</label>
                <input type="text" id="noReg" class="form-control" placeholder="No. Rekam Medis Pasien">
            </div>
            <div class="col-md-3">
                <label for="noTelp">Filter by No. Telp:</label>
                <input type="text" id="noTelp" class="form-control" placeholder="No. Telpon Pasien">
            </div>
            <div class="col-md-3">
                <label for="poli">Filter by Poli:</label>
                <input type="text" id="poli" class="form-control" placeholder="Poli">
            </div>
            <div class="col-md-3">
                <label for="namaDokter">Filter by Nama Dokter:</label>
                <input type="text" id="namaDokter" class="form-control" placeholder="Nama Dokter">
            </div>
            
            <div class="col-md-3"><br>
                <label for="tglKirim">Filter by Tanggal Kirim Pesan:</label>
                <input type="date" id="tglKirim" class="form-control">
            </div>

            <div class="col-md-3"><br>
                <label for="statusKirim">Filter by Status Kirim:</label>
                <select id="statusKirim" class="custom-select">
                    <option selected>-- Pilih Status --</option>
                    <option value="1">Terkirim</option>
                    <option value="2">Gagal Kirim</option>
                </select>
            </div>

            <div class="col-md-2 mt-4"><br>
                <button class="btn btn-primary mt-2" id="btnCari">Cari</button>
                <button class="btn btn-secondary mt-2 ml-2" id="btnReset">Reset</button>
            </div>

        </div>
        <br>
        <div class="table-responsive">
            <table id="TblBtlKirim" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>No. RM Pasien</th>
                        <th>No. HP</th>
                        <th>Nama Pasien</th>
                        <th>Pesan</th>
                        <th>Nama Poli</th>
                        <th>Nama Dokter</th>
                        <th>Tanggal Kirim Pesan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hostname = "192.168.0.33";
                    $username = "admin";
                    $password = "admin3dp";
                    $database = "rsiklaten";
                    $koneksi = mysqli_connect($hostname, $username, $password, $database);

                    if (mysqli_connect_errno()) {
                        echo "Failed to connect to MySQL: " . mysqli_connect_error();
                        exit();
                    }

                    $query = "SELECT batal_praktek_detil_wa.no_reg, 
                                   batal_praktek_detil_wa.no_hp, 
                                   batal_praktek_detil_wa.nama_pasien, 
                                   batal_praktek_detil_wa.pesan, 
                                   batal_praktek_detil_wa.nama_poli, 
                                   batal_praktek_detil_wa.tgl_kirim_pesan, 
                                   batal_praktek_detil_wa.status,
                                   dokter.nama as nama_dokter
                            FROM batal_praktek_detil_wa 
                            INNER JOIN dokter ON dokter.no_dr = batal_praktek_detil_wa.kode_dokter ORDER BY id DESC LIMIT 1500";

                    $result = mysqli_query($koneksi, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['no_reg'] . "</td>";
                        echo "<td>" . $row['no_hp'] . "</td>";
                        echo "<td>" . $row['nama_pasien'] . "</td>";
                        echo "<td>" . $row['pesan'] . "</td>";
                        echo "<td>" . $row['nama_poli'] . "</td>";
                        echo "<td>" . $row['nama_dokter'] . "</td>";
                        echo "<td>" . $row['tgl_kirim_pesan'] . "</td>";
                        echo "<td>";
                        if ($row['status'] == 1) {
                            echo "<span class='badge badge-success'>Terkirim</span>";
                        } elseif ($row['status'] == 2) {
                            echo "<span class='badge badge-danger'>Gagal Kirim</span>";
                        } else {
                            echo "Status Tidak Valid";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }

                    mysqli_close($koneksi);
                    ?>
                </tbody>
            </table>
        </div>
        <br>
    </div>

    <!-- Modal QR Code -->
    <div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="qrModalLabel">Scan Ulang WhatsApp</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body text-center">
            <img id="qrImage" src="" alt="QR Code" class="img-fluid" style="max-width: 100%; display:block;">
                <div id="qrFallback" style="display:none; font-weight:bold; color:red;">
                    <a class="nav-link btn btn-warning text-dark" href="https://wa.me/62895376257021?text=Service%20WA%20perlu%20dijalankan%20"  target="_blank">
                        Service WhatsApp belum berjalan
                    </a>
                </div>
          </div>

        </div>
      </div>
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
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    document.getElementById('btnScanWA').addEventListener('click', function(e) {
        e.preventDefault(); 
        var qrUrl = "http://192.168.0.93:8049/service_wa_with_venom/qr_code.png";

        var qrImage = document.getElementById('qrImage');
        var qrFallback = document.getElementById('qrFallback');

        qrImage.style.display = "block";
        qrFallback.style.display = "none";

        qrImage.src = qrUrl + "?t=" + new Date().getTime();

        qrImage.onerror = function() {
            qrImage.style.display = "none";
            qrFallback.style.display = "block";
        };

        $('#qrModal').modal('show');
    });
    </script>


    <script>
        $(document).ready(function() {
            var table = $('#TblBtlKirim').DataTable({
                responsive: true
            });

            $('#btnCari').on('click', function() {
                var namaDokter = $('#namaDokter').val().trim();
                var noReg = $('#noReg').val().trim();
                var noTelp = $('#noTelp').val().trim();
                var poli = $('#poli').val().trim();
                var statusKirim = $('#statusKirim').val().trim();
                var tglKirim = $('#tglKirim').val().trim();

                if (namaDokter !== '') {
                    table.columns(5).search(namaDokter).draw();
                } else {
                    table.columns(5).search('').draw();
                }

                if (noReg !== '') {
                    table.columns(0).search(noReg).draw();
                } else {
                    table.columns(0).search('').draw();
                }

                if (noTelp !== '') {
                    table.columns(1).search(noTelp).draw();
                } else {
                    table.columns(1).search('').draw();
                }

                if (poli !== '') {
                    table.columns(4).search(poli).draw();
                } else {
                    table.columns(4).search('').draw();
                }

                if (statusKirim !== '') {
                    var statusText = '';
                    if (statusKirim === '1') {
                        statusText = 'Terkirim';
                    } else if (statusKirim === '2') {
                        statusText = 'Gagal Kirim';
                    }
                    table.columns(7).search(statusText).draw();
                } else {
                    table.columns(7).search('').draw();
                }

                if (tglKirim !== '') {
                    table.columns(6).search(tglKirim).draw();
                } else {
                    table.columns(6).search('').draw();
                }
            });

            $('#btnReset').on('click', function() {
                $('#namaDokter').val('');
                $('#noReg').val('');
                $('#noTelp').val('');
                $('#poli').val('');
                $('#statusKirim').val('-- Pilih Status --');

                table.columns().search('').draw();
            });
        });

        document.getElementById('editMessage').addEventListener('click', function() {
            Swal.fire({
                title: 'Apakah anda ingin mengedit Pesan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    var messageTextarea = document.getElementById('message');
                    messageTextarea.readOnly = false;
                    messageTextarea.focus();
                    document.getElementById('editMessage').style.display = 'none';
                    document.getElementById('cancelEdit').style.display = 'block';
                }
            });
        });

        document.getElementById('cancelEdit').addEventListener('click', function() {
            var messageTextarea = document.getElementById('message');
            messageTextarea.readOnly = true;
            document.getElementById('editMessage').style.display = 'block';
            document.getElementById('cancelEdit').style.display = 'none';
        });

    </script>
    <script src="./assets/js/master.js"></script>
</body>
</html>