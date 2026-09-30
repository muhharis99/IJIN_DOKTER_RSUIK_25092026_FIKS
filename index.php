<?php

$host     = '192.168.0.33';
$user     = 'admin';
$password = 'admin3dp';
$database = 'rsiklaten';

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8');

$query = "
    SELECT no_hp, pesan
    FROM batal_praktek_detil_wa
    WHERE status IS NULL
      AND no_hp IS NOT NULL AND no_hp != ''
    UNION ALL
    SELECT no_hp, pesan
    FROM batal_praktek_detil_wa
    WHERE status = ''
      AND no_hp IS NOT NULL AND no_hp != ''
    LIMIT 5000";

$result = mysqli_query($conn, $query);

$data      = [];
$allNumbers = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $hp = trim($row['no_hp']);
        if ($hp === '') continue;

        $data[$row['pesan']][] = $hp;

        $allNumbers[$hp] = true;
    }
}

$allNumbers    = array_keys($allNumbers);
$totalPenerima = count($allNumbers);

mysqli_close($conn);

$pesanLines = '';
$index = 1;
foreach ($data as $pesan => $nums) {
    $p = $pesan;
    $p = str_replace("Assalamu'alaikum. ", '', $p);
    $p = str_replace("Mohon ma'af atas ketidaknyamanannya. Mks (RSI KLATEN)", '', $p);
    $p = str_replace("Mohon maaf atas ketidaknyamanannya. Mks (RSI KLATEN)", '', $p);
    $p = str_replace("Kami beritahukan bahwa praktek ", '', $p);
    $p = str_replace("Kami beritahukan bahwa ", '', $p);
    $pesanLines .= $index . ". " . trim($p) . "\n\n";
    $index++;
}

$pesanFull = "📢 *Pengumuman RSU Islam Klaten*\n\n"
           . "Assalâmu'alaikum wr wb\n\n"
           . "#SahabatSehatRSIKlaten Kami beritahukan perubahan jam praktik sebagai berikut :\n\n"
           . $pesanLines
           . "Mohon ma'af atas ketidaknyamanannya. Terimakasih 😊🙏🏻\n\n"
           . "Wassalamu'alaikum wr wb\n"
           . "—\n\n"
           . "*RSU Islam Klaten*\n"
           . "_Ramah, Amanah, Profesional, Islami_ (RAPI)";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WA Gateway - RSUIK</title>
    <link rel="icon" href="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <link rel="preload" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css"></noscript>

    <link rel="preload" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css"></noscript>

    <link rel="preload" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css"></noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"></noscript>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>

    <style>
        :root {
            --primary:       #0d6e4f;
            --primary-light: #1a9e72;
            --primary-pale:  #e8f5f0;
            --wa-green:      #25d366;
            --wa-dark:       #128c7e;
            --accent:        #f0a500;
            --danger:        #e53e3e;
            --success:       #38a169;
            --gray-50:       #f9fafb;
            --gray-100:      #f3f4f6;
            --gray-200:      #e5e7eb;
            --gray-600:      #4b5563;
            --gray-800:      #1f2937;
            --radius:        12px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif; /* OPTIMASI 6: fallback font lokal */
            background: #f0f4f8;
            color: var(--gray-800);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--wa-dark) 100%) !important;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: .3px;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .navbar-brand .brand-icon {
            width: 36px; height: 36px;
            background: var(--wa-green);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .navbar-brand .brand-sub {
            font-size: .7rem;
            font-weight: 400;
            opacity: .8;
            display: block;
            line-height: 1;
        }
        .btn-scan {
            background: linear-gradient(135deg, var(--accent), #e09400);
            color: #fff !important;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            padding: .45rem 1.1rem;
            display: flex; align-items: center; gap: .4rem;
            transition: all .2s;
        }
        .btn-scan:hover { transform: translateY(-1px); }
        .page-wrapper { max-width: 1280px; margin: 0 auto; padding: 1.75rem 1rem 3rem; }
        .info-banner {
            display: flex; align-items: flex-start; gap: .75rem;
            padding: .85rem 1.1rem;
            border-radius: var(--radius);
            font-size: .875rem;
            margin-bottom: .75rem;
        }
        .info-banner.warning { background: #fff8e1; border: 1px solid #ffe082; color: #7a5c00; }
        .info-banner.info    { background: #e3f2fd; border: 1px solid #90caf9; color: #0d47a1; }
        .info-banner i { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }
        .card-modern {
            background: #fff;
            border-radius: var(--radius);
            border: none;
            overflow: hidden;
        }
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            padding: 1rem 1.4rem;
            display: flex; align-items: center; gap: .6rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .card-header-modern i { font-size: 1.15rem; }
        .card-body-modern { padding: 1.4rem; }
        .form-label-modern {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--gray-600);
            margin-bottom: .35rem;
            display: block;
        }
        .form-control-modern {
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            padding: .6rem .9rem;
            font-size: .875rem;
            font-family: inherit;
            background: var(--gray-50);
            color: var(--gray-800);
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
        }
        .form-control-modern:focus {
            outline: none;
            border-color: var(--primary-light);
            background: #fff;
        }
        .form-control-modern[readonly] { background: var(--gray-100); cursor: default; }
        textarea.form-control-modern { resize: vertical; line-height: 1.6; }
        .btn-modern {
            border: none; border-radius: 8px;
            font-weight: 600; font-size: .9rem;
            padding: .65rem 1.4rem;
            cursor: pointer; transition: all .2s;
            display: inline-flex; align-items: center; gap: .45rem;
        }
        .btn-modern.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            width: 100%;
            justify-content: center;
            font-size: 1rem;
            padding: .8rem;
        }
        .btn-modern.primary:hover { transform: translateY(-1px); }
        .btn-modern.secondary {
            background: var(--gray-100);
            color: var(--gray-800);
            border: 1.5px solid var(--gray-200);
            width: 100%; justify-content: center;
        }
        .btn-modern.secondary:hover { background: var(--gray-200); }
        .section-divider {
            display: flex; align-items: center; gap: 1rem;
            margin: 2rem 0 1.5rem;
        }
        .section-divider hr { flex: 1; border-color: var(--gray-200); margin: 0; }
        .section-divider span {
            font-weight: 700; font-size: .95rem; color: var(--gray-600);
            white-space: nowrap; display: flex; align-items: center; gap: .4rem;
        }
        .filter-panel {
            background: #fff;
            border-radius: var(--radius);
            padding: 1.25rem 1.4rem;
            margin-bottom: 1.25rem;
        }
        .filter-panel .row { row-gap: .75rem; }
        .filter-panel label {
            font-size: .78rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .4px;
            color: var(--gray-600); margin-bottom: .3rem;
        }
        .table-wrapper {
            background: #fff;
            border-radius: var(--radius);
            overflow: hidden;
        }
        .table-wrapper .p-3 { padding: 1.25rem !important; }
        #TblBtlKirim thead th {
            background: var(--primary);
            color: #fff;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            border: none;
            padding: .9rem 1rem;
        }
        #TblBtlKirim tbody tr { transition: background .15s; }
        #TblBtlKirim tbody tr:hover { background: var(--primary-pale); }
        #TblBtlKirim tbody td {
            font-size: .85rem; vertical-align: middle;
            border-color: var(--gray-100);
            padding: .75rem 1rem;
        }
        .badge-status {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .3rem .75rem; border-radius: 20px;
            font-size: .75rem; font-weight: 600;
        }
        .badge-status.sent    { background: #d1fae5; color: #065f46; }
        .badge-status.failed  { background: #fee2e2; color: #991b1b; }
        .badge-status.unknown { background: var(--gray-100); color: var(--gray-600); }
        .empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--gray-600); }
        .empty-state i { font-size: 3rem; opacity: .35; display: block; margin-bottom: .75rem; }
        .dt-top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: .75rem 1rem .5rem; flex-wrap: wrap; gap: .5rem;
        }
        .dt-bottom-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: .5rem 1rem .75rem; flex-wrap: wrap; gap: .5rem;
        }
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid var(--gray-200);
            border-radius: 8px; padding: .35rem .7rem;
            font-size: .85rem; font-family: inherit;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
            outline: none;
            border-color: var(--primary-light);
          
        }
        .dataTables_wrapper .dataTables_info { font-size: .82rem; color: var(--gray-600); }
        .dataTables_wrapper .dataTables_processing {
            background: rgba(255,255,255,.95);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
          
            padding: 1rem 1.5rem;
            font-size: .875rem;
            color: var(--primary);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary) !important;
            color: #fff !important;
            border-color: var(--primary) !important;
            border-radius: 6px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--primary-pale) !important;
            border-color: transparent !important;
            border-radius: 6px;
        }
        .dt-loading { display: flex; align-items: center; gap: .5rem; }
        .spin { animation: spin .8s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal-content { border: none; border-radius: var(--radius); overflow: hidden; }
        .modal-header { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; border: none; }
        .modal-header .close { color: #fff; opacity: .8; }
        .modal-header .close:hover { opacity: 1; }
        footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--wa-dark) 100%) !important;
            color: rgba(255,255,255,.85);
            text-align: center;
            padding: 1.1rem;
            font-size: .85rem;
            margin-top: 2rem;
        }
        footer a { color: var(--wa-green); font-weight: 600; text-decoration: none; }
        footer a:hover { text-decoration: underline; }
        #backToTop {
            display: none; position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
            width: 44px; height: 44px; border: none; border-radius: 50%; cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; font-size: 1.2rem;
       
            transition: all .2s;
            align-items: center; justify-content: center;
        }

        .skeleton-row td { padding: .75rem 1rem; }
        .skeleton-bar {
            height: 14px; border-radius: 6px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
        }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand" href="#">
        <div class="brand-icon"><i class="bi bi-whatsapp"></i></div>
        <div>
            WA Gateway
            <span class="brand-sub">RSU Islam Klaten V.02</span>
        </div>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item">
                <a class="nav-link btn-scan" href="#" id="btnScanWA">
                    <i class="bi bi-qr-code-scan"></i> Scan Ulang WhatsApp
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="page-wrapper">

    <div class="info-banner warning">
        <i class="bi bi-clock-history"></i>
        <span><strong>Waktu kirim pesan:</strong> 7 s/d 20 detik per nomor. Harap bersabar saat proses berlangsung.</span>
    </div>
    <div class="info-banner info">
        <i class="bi bi-info-circle"></i>
        <span>Jika terjadi kesalahan saat pengiriman, lakukan <strong>refresh halaman</strong> lalu kirim ulang. Pesan yang sudah berhasil terkirim sebelumnya <strong>sudah diterima pasien.</strong></span>
    </div>

    <div class="info-banner warning">
        <i class="bi bi-shield-check"></i>
        <span><strong>Perlindungan kebijakan WhatsApp aktif:</strong> nomor yang belum memiliki persetujuan eksplisit untuk menerima notifikasi layanan akan otomatis ditahan. Penerima dapat berhenti menerima notifikasi dengan membalas <strong>STOP</strong>. Untuk mendaftar kembali, balas <strong>DAFTAR</strong>.</span>
    </div>

    <div class="card-modern mt-3">
        <div class="card-header-modern">
            <i class="bi bi-send-fill"></i> Kirim Notifikasi ke Pasien
        </div>
        <div class="card-body-modern">
            <form id="whatsappForm">
                <?php if (!empty($data)): ?>

                <div class="mb-3">
                    <label class="form-label-modern">
                        <i class="bi bi-telephone"></i> &nbsp;Nomor Penerima
                    </label>
                    <input type="text" id="numbers" name="numbers" class="form-control-modern"
                           value="<?php echo htmlspecialchars(implode(',', $allNumbers)); ?>"
                           readonly>
                    <small class="text-muted" style="font-size:.78rem;">
                        <i class="bi bi-people"></i>
                        <?php echo $totalPenerima; ?> nomor penerima
                        &nbsp;•&nbsp; pemeriksaan persetujuan dilakukan oleh gateway sebelum pengiriman
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label-modern">
                        <i class="bi bi-chat-text"></i> &nbsp;Isi Pesan
                    </label>
                    <textarea id="message" name="message" class="form-control-modern" rows="10" readonly><?php echo htmlspecialchars($pesanFull); ?></textarea>
                </div>

                <div class="mb-3 d-flex" style="gap:.6rem;">
                    <button type="button" id="editMessage" class="btn-modern secondary" style="width:auto; flex:1;">
                        <i class="bi bi-pencil-square"></i> Edit Pesan
                    </button>
                    <button type="button" id="cancelEdit" class="btn-modern secondary" style="display:none; width:auto; flex:1;">
                        <i class="bi bi-x-circle"></i> Batalkan Edit
                    </button>
                </div>

                <button type="submit" class="btn-modern primary">
                    <i class="bi bi-send-fill"></i> Kirim Sekarang!
                </button>

                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="mb-0" style="font-weight:600;">Tidak ada pesan yang perlu dikirim.</p>
                    <small class="text-muted">Semua notifikasi sudah terkirim atau belum ada data baru.</small>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="section-divider">
        <hr><span><i class="bi bi-clock-history"></i> Riwayat Pengiriman Pesan</span><hr>
    </div>

    <div class="filter-panel">
        <div class="row align-items-end">
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-person-badge"></i> No. Rekam Medis</label>
                <input type="text" id="noReg" class="form-control form-control-sm" placeholder="Cari No. RM...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-telephone"></i> No. Telepon</label>
                <input type="text" id="noTelp" class="form-control form-control-sm" placeholder="Cari No. Telp...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-hospital"></i> Poli</label>
                <input type="text" id="poli" class="form-control form-control-sm" placeholder="Nama Poli...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-person-lines-fill"></i> Nama Dokter</label>
                <input type="text" id="namaDokter" class="form-control form-control-sm" placeholder="Nama Dokter...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-calendar-event"></i> Tanggal Kirim</label>
                <input type="date" id="tglKirim" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label><i class="bi bi-funnel"></i> Status Kirim</label>
                <select id="statusKirim" class="form-control form-control-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="1">Terkirim</option>
                    <option value="2">Gagal Kirim</option>
                    <option value="3">Ditahan Kebijakan</option>
                    <option value="4">Opt-out</option>
                    <option value="5">Duplikat</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-12 d-flex" style="gap:.5rem;">
                <button class="btn btn-primary btn-sm flex-fill" id="btnCari" style="border-radius:8px;">
                    <i class="bi bi-search"></i> Cari
                </button>
                <button class="btn btn-outline-secondary btn-sm flex-fill" id="btnReset" style="border-radius:8px;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="p-3" style="overflow-x:auto;">
            <table id="TblBtlKirim" class="table table-hover mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>No. HP</th>
                        <th>Nama Pasien</th>
                        <th>Pesan</th>
                        <th>Poli</th>
                        <th>Dokter</th>
                        <th>Tgl Kirim</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- OPTIMASI 8: Skeleton loader supaya tabel tidak kosong saat data belum datang -->
                    <?php for ($s = 0; $s < 5; $s++): ?>
                    <tr class="skeleton-row">
                        <?php for ($c = 0; $c < 8; $c++): ?>
                        <td><div class="skeleton-bar" style="width:<?php echo [70,90,120,180,80,100,85,65][$c]; ?>px;"></div></td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal QR -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code-scan"></i> &nbsp;Scan Ulang WhatsApp</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="text-muted" style="font-size:.85rem;">Buka WhatsApp di HP Anda → Menu → Perangkat Tertaut → Tautkan Perangkat</p>
                <img id="qrImage" src="" alt="QR Code" class="img-fluid rounded"
                     style="max-width:280px;display:block;margin:0 auto;">
                <div id="qrFallback" style="display:none;">
                    <div style="font-size:3rem;color:#ccc;"><i class="bi bi-wifi-off"></i></div>
                    <p class="mt-2 text-danger" style="font-weight:600;">Service WhatsApp belum berjalan</p>
                    <a class="btn btn-warning btn-sm" href="https://wa.me/62895376257021?text=Service%20WA%20perlu%20dijalankan%20" target="_blank">
                        <i class="bi bi-whatsapp"></i> Hubungi Admin
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div>
        <img src="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png" width="22"
             style="vertical-align:middle;margin-right:.4rem;">
        &copy; <?php echo date("Y"); ?> RSU Islam Klaten &mdash;
        Dikembangkan oleh <a href="https://www.harisuix.com/" target="_blank">Muhammad Haris Chaidir</a>
    </div>
</footer>

<button id="backToTop" title="Kembali ke atas">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" defer></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js" defer></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js" defer></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js" defer></script>
<script src="./assets/js/master.js" defer></script>

</body>
</html>