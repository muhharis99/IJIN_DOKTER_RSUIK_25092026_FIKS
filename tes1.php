<?php
$host     = '192.168.0.33';
$user     = 'admin';
$password = 'admin3dp';
$database = 'rsiklaten';

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

if (!empty($_POST['ajax_laporan'])) {
    $tglLaporan = mysqli_real_escape_string($conn, $_POST['tgl_laporan']);

    $qLaporan = "
        SELECT bpd.nama_poli, d.nama AS nama_dokter, bpd.pesan
        FROM batal_praktek_detil_wa bpd
        INNER JOIN dokter d ON d.no_dr = bpd.kode_dokter
        WHERE DATE(bpd.tgl_kirim_pesan) = '$tglLaporan'
        GROUP BY bpd.nama_poli, bpd.kode_dokter, bpd.pesan
        ORDER BY bpd.nama_poli ASC";
    $resLaporan = mysqli_query($conn, $qLaporan);

    function ekstrakJam(string $p): string {
        if (preg_match('/jam\s+([\d:.]+(?:\s*[-–]\s*[\d:.]+)?)/i', $p, $m))
            return 'jam ' . trim($m[1]) . ' WIB';
        return '';
    }
    function ekstrakSistem(string $p): string {
        $s = [];
        if (stripos($p,'QB')    !== false) $s[] = 'QB';
        if (stripos($p,'GW')    !== false) $s[] = 'GW';
        if (stripos($p,'SIMRS') !== false) $s[] = 'SIMRS';
        return $s ? implode(', ', $s) : 'QB, GW';
    }
    function isIjin(string $p): bool {
        foreach (['ijin','izin','tidak praktek','libur','cuti','berhalangan'] as $k)
            if (stripos($p, $k) !== false) return true;
        return false;
    }

    $perubahanJam = [];
    $ijinDokter   = [];
    if ($resLaporan) {
        while ($row = mysqli_fetch_assoc($resLaporan)) {
            $entry = [
                'poli'   => $row['nama_poli'],
                'dokter' => $row['nama_dokter'],
                'jam'    => ekstrakJam($row['pesan']),
                'sistem' => ekstrakSistem($row['pesan']),
                'ijin'   => isIjin($row['pesan']),
            ];
            if ($entry['ijin']) $ijinDokter[] = $entry;
            else                $perubahanJam[] = $entry;
        }
    }

    $hariId   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $tglObj   = new DateTime($tglLaporan);
    $labelTgl = $hariId[$tglObj->format('w')] . ' ' . $tglObj->format('d/m/Y');

    $teksPlain  = "*$labelTgl*\n";
    $teksPlain .= "Perubahan jam & Info Poli :\n";
    foreach ($perubahanJam as $i => $e) {
        $j = $e['jam'] ? ' ' . $e['jam'] . '.' : '.';
        $teksPlain .= ($i+1) . ". {$e['poli']} {$e['dokter']}{$j} {$e['sistem']} ✅\n";
    }
    if ($ijinDokter) {
        $teksPlain .= "Ijin Dokter :\n";
        foreach ($ijinDokter as $i => $e)
            $teksPlain .= ($i+1) . ". {$e['poli']} {$e['dokter']}. {$e['sistem']} ✅\n";
    }

    mysqli_close($conn);
    header('Content-Type: application/json');
    echo json_encode([
        'labelTgl'     => $labelTgl,
        'perubahanJam' => $perubahanJam,
        'ijinDokter'   => $ijinDokter,
        'teksPlain'    => $teksPlain,
    ]);
    exit;
}

$qBelumKirim = "
    SELECT batal_wa.no_hp AS no_wa, batal_wa.pesan, pas_wa.telp AS no_hp
    FROM batal_praktek_detil_wa AS batal_wa
    LEFT JOIN rsiklaten.pasien AS pas_wa ON batal_wa.no_reg = pas_wa.no_reg
    WHERE batal_wa.status IS NULL OR batal_wa.status = ''";
$resBelum = mysqli_query($conn, $qBelumKirim);
$data = [];
if (mysqli_num_rows($resBelum) > 0) {
    while ($row = mysqli_fetch_assoc($resBelum))
        $data[$row['pesan']][] = $row['no_hp'];
}

$resTgl = mysqli_query($conn,
    "SELECT DATE(tgl_kirim_pesan) AS tgl
     FROM batal_praktek_detil_wa
     WHERE tgl_kirim_pesan IS NOT NULL
     ORDER BY tgl_kirim_pesan DESC LIMIT 1");
$rowTgl     = mysqli_fetch_assoc($resTgl);
$tglLaporan = $rowTgl ? $rowTgl['tgl'] : date('Y-m-d');

function ekstrakJam(string $p): string {
    if (preg_match('/jam\s+([\d:.]+(?:\s*[-–]\s*[\d:.]+)?)/i', $p, $m))
        return 'jam ' . trim($m[1]) . ' WIB';
    return '';
}
function ekstrakSistem(string $p): string {
    $s = [];
    if (stripos($p,'QB')    !== false) $s[] = 'QB';
    if (stripos($p,'GW')    !== false) $s[] = 'GW';
    if (stripos($p,'SIMRS') !== false) $s[] = 'SIMRS';
    return $s ? implode(', ', $s) : 'QB, GW';
}
function isIjin(string $p): bool {
    foreach (['ijin','izin','tidak praktek','libur','cuti','berhalangan'] as $k)
        if (stripos($p, $k) !== false) return true;
    return false;
}

$qLaporan = "
    SELECT bpd.nama_poli, d.nama AS nama_dokter, bpd.pesan
    FROM batal_praktek_detil_wa bpd
    INNER JOIN dokter d ON d.no_dr = bpd.kode_dokter
    WHERE DATE(bpd.tgl_kirim_pesan) = '$tglLaporan'
    GROUP BY bpd.nama_poli, bpd.kode_dokter, bpd.pesan
    ORDER BY bpd.nama_poli ASC";
$resLaporan = mysqli_query($conn, $qLaporan);

$perubahanJam = [];
$ijinDokter   = [];
if ($resLaporan) {
    while ($row = mysqli_fetch_assoc($resLaporan)) {
        $entry = [
            'poli'   => $row['nama_poli'],
            'dokter' => $row['nama_dokter'],
            'jam'    => ekstrakJam($row['pesan']),
            'sistem' => ekstrakSistem($row['pesan']),
        ];
        if (isIjin($row['pesan'])) $ijinDokter[] = $entry;
        else                       $perubahanJam[] = $entry;
    }
}

$hariId   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$tglObj   = new DateTime($tglLaporan);
$labelTgl = $hariId[$tglObj->format('w')] . ' ' . $tglObj->format('d/m/Y');

$laporanTeks  = "*$labelTgl*\n";
$laporanTeks .= "Perubahan jam & Info Poli :\n";
foreach ($perubahanJam as $i => $e) {
    $j = $e['jam'] ? ' ' . $e['jam'] . '.' : '.';
    $laporanTeks .= ($i+1) . ". {$e['poli']} {$e['dokter']}{$j} {$e['sistem']} ✅\n";
}
if ($ijinDokter) {
    $laporanTeks .= "Ijin Dokter :\n";
    foreach ($ijinDokter as $i => $e)
        $laporanTeks .= ($i+1) . ". {$e['poli']} {$e['dokter']}. {$e['sistem']} ✅\n";
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WA Gateway - RSUIK</title>
    <link rel="icon" href="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/master.css">
    <style>
        :root {
            --primary:       #0d6e4f;
            --primary-light: #1a9e72;
            --primary-pale:  #e8f5f0;
            --wa-green:      #25d366;
            --wa-dark:       #128c7e;
            --accent:        #f0a500;
            --danger:        #e53e3e;
            --gray-50:       #f9fafb;
            --gray-100:      #f3f4f6;
            --gray-200:      #e5e7eb;
            --gray-600:      #4b5563;
            --gray-800:      #1f2937;
            --shadow-sm:     0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
            --shadow-md:     0 4px 12px rgba(0,0,0,.1);
            --radius:        12px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: var(--gray-800); min-height: 100vh; }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--wa-dark) 100%) !important;
            padding: .75rem 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,.18);
            position: sticky; top: 0; z-index: 1030;
        }
        .navbar-brand { font-weight: 700; font-size: 1.15rem; letter-spacing: .3px; display: flex; align-items: center; gap: .5rem; }
        .navbar-brand .brand-icon { width: 36px; height: 36px; background: var(--wa-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .navbar-brand .brand-sub { font-size: .7rem; font-weight: 400; opacity: .8; display: block; line-height: 1; }
        .btn-scan { background: linear-gradient(135deg, var(--accent), #e09400); color: #fff !important; border: none; border-radius: 8px; font-weight: 600; font-size: .85rem; padding: .45rem 1.1rem; display: flex; align-items: center; gap: .4rem; transition: all .2s; box-shadow: 0 2px 8px rgba(240,165,0,.4); }
        .btn-scan:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(240,165,0,.5); }

        .page-wrapper { max-width: 1280px; margin: 0 auto; padding: 1.75rem 1rem 3rem; }

        .info-banner { display: flex; align-items: flex-start; gap: .75rem; padding: .85rem 1.1rem; border-radius: var(--radius); font-size: .875rem; margin-bottom: .75rem; }
        .info-banner.warning { background: #fff8e1; border: 1px solid #ffe082; color: #7a5c00; }
        .info-banner.info    { background: #e3f2fd; border: 1px solid #90caf9; color: #0d47a1; }
        .info-banner i { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }

        .card-modern { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-md); border: none; overflow: hidden; }
        .card-header-modern { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: #fff; padding: 1rem 1.4rem; display: flex; align-items: center; gap: .6rem; font-weight: 600; font-size: 1rem; }
        .card-header-modern i { font-size: 1.15rem; }
        .card-body-modern { padding: 1.4rem; }

        .form-label-modern { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-600); margin-bottom: .35rem; display: block; }
        .form-control-modern { border: 1.5px solid var(--gray-200); border-radius: 8px; padding: .6rem .9rem; font-size: .875rem; font-family: 'Inter', sans-serif; background: var(--gray-50); color: var(--gray-800); transition: border-color .2s, box-shadow .2s; width: 100%; }
        .form-control-modern:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(26,158,114,.15); background: #fff; }
        .form-control-modern[readonly] { background: var(--gray-100); cursor: default; }
        textarea.form-control-modern { resize: vertical; line-height: 1.6; }

        .btn-modern { border: none; border-radius: 8px; font-weight: 600; font-size: .9rem; padding: .65rem 1.4rem; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: .45rem; }
        .btn-modern.primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; box-shadow: 0 3px 10px rgba(13,110,79,.35); width: 100%; justify-content: center; font-size: 1rem; padding: .8rem; }
        .btn-modern.primary:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(13,110,79,.45); }
        .btn-modern.secondary { background: var(--gray-100); color: var(--gray-800); border: 1.5px solid var(--gray-200); width: 100%; justify-content: center; }
        .btn-modern.secondary:hover { background: var(--gray-200); }

        .section-divider { display: flex; align-items: center; gap: 1rem; margin: 2rem 0 1.5rem; }
        .section-divider hr { flex: 1; border-color: var(--gray-200); margin: 0; }
        .section-divider span { font-weight: 700; font-size: .95rem; color: var(--gray-600); white-space: nowrap; display: flex; align-items: center; gap: .4rem; }

        .filter-panel { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-sm); padding: 1.25rem 1.4rem; margin-bottom: 1.25rem; }
        .filter-panel .row { row-gap: .75rem; }
        .filter-panel label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--gray-600); margin-bottom: .3rem; }

        .table-wrapper { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        #TblBtlKirim thead th { background: var(--primary); color: #fff; font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; border: none; padding: .9rem 1rem; }
        #TblBtlKirim tbody tr { transition: background .15s; }
        #TblBtlKirim tbody tr:hover { background: var(--primary-pale); }
        #TblBtlKirim tbody td { font-size: .85rem; vertical-align: middle; border-color: var(--gray-100); padding: .75rem 1rem; }

        .badge-status { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .75rem; border-radius: 20px; font-size: .75rem; font-weight: 600; }
        .badge-status.sent    { background: #d1fae5; color: #065f46; }
        .badge-status.failed  { background: #fee2e2; color: #991b1b; }
        .badge-status.unknown { background: var(--gray-100); color: var(--gray-600); }

        .empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--gray-600); }
        .empty-state i { font-size: 3rem; opacity: .35; display: block; margin-bottom: .75rem; }

        .modal-content { border: none; border-radius: var(--radius); overflow: hidden; }
        .modal-header { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; border: none; }
        .modal-header .close { color: #fff; opacity: .8; }
        .modal-header .close:hover { opacity: 1; }

        footer { background: linear-gradient(135deg, var(--primary) 0%, var(--wa-dark) 100%) !important; color: rgba(255,255,255,.85); text-align: center; padding: 1.1rem; font-size: .85rem; margin-top: 2rem; }
        footer a { color: var(--wa-green); font-weight: 600; text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid var(--gray-200); border-radius: 8px; padding: .35rem .7rem; font-size: .85rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; border-radius: 6px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--primary-pale) !important; border-color: transparent !important; border-radius: 6px; }

        .laporan-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; margin-top: 1.5rem; position: relative; }
        .laporan-header { background: linear-gradient(135deg, var(--wa-dark) 0%, var(--wa-green) 100%); color: #fff; padding: 1rem 1.4rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
        .laporan-header .lh-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: .5rem; }
        .laporan-body { padding: 1.4rem; }

        .laporan-section-title { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; padding: .35rem .85rem; border-radius: 6px; display: inline-flex; align-items: center; gap: .4rem; margin-bottom: .85rem; }
        .laporan-section-title.jam  { background: #e8f5f0; color: #0d6e4f; }
        .laporan-section-title.ijin { background: #fee2e2; color: #991b1b; }

        .laporan-list { list-style: none; padding: 0; margin: 0 0 1.25rem; }
        .laporan-list li { display: flex; align-items: flex-start; gap: .6rem; padding: .6rem .85rem; border-radius: 8px; font-size: .875rem; line-height: 1.5; margin-bottom: .3rem; background: var(--gray-50); border-left: 3px solid transparent; transition: background .15s; }
        .laporan-list li:hover     { background: #f0faf6; }
        .laporan-list.jam  li      { border-left-color: var(--wa-green); }
        .laporan-list.ijin li      { border-left-color: var(--danger); }
        .laporan-list .no-urut     { font-weight: 700; color: var(--gray-600); min-width: 1.4rem; flex-shrink: 0; }
        .laporan-list .poli-name   { font-weight: 600; color: var(--primary); }
        .laporan-list .dokter-name { color: var(--gray-800); }
        .laporan-list .jam-info    { color: #0070c0; font-weight: 500; }
        .laporan-list .sistem-info { margin-left: auto; flex-shrink: 0; display: flex; align-items: center; gap: .3rem; font-size: .78rem; font-weight: 600; color: #555; white-space: nowrap; }
        .laporan-list .sistem-info .chk { color: var(--wa-green); font-size: .95rem; }

        .laporan-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; padding-top: .75rem; border-top: 1px solid var(--gray-100); }
        .laporan-stat { font-size: .8rem; color: var(--gray-600); display: flex; gap: 1rem; }
        .laporan-stat span { display: flex; align-items: center; gap: .3rem; }

        .laporan-copy-btn { background: linear-gradient(135deg, var(--wa-dark), var(--wa-green)); color: #fff; border: none; border-radius: 8px; padding: .55rem 1.2rem; font-size: .875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: .45rem; transition: all .2s; box-shadow: 0 3px 10px rgba(37,211,102,.3); }
        .laporan-copy-btn:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(37,211,102,.45); }

        .laporan-empty { text-align: center; padding: 2rem 1rem; color: var(--gray-600); font-size: .875rem; }
        .laporan-empty i { font-size: 2.2rem; opacity: .3; display: block; margin-bottom: .6rem; }

        .laporan-overlay {
            display: none;
            position: absolute; inset: 0;
            background: rgba(255,255,255,.75);
            border-radius: var(--radius);
            z-index: 10;
            align-items: center; justify-content: center;
            flex-direction: column; gap: .75rem;
        }
        .laporan-overlay.show { display: flex; }
        .laporan-overlay .spinner {
            width: 44px; height: 44px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--wa-green);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        .laporan-overlay p { font-size: .85rem; font-weight: 600; color: var(--wa-dark); margin: 0; }
        @keyframes spin { to { transform: rotate(360deg); } }

        #tglLaporanPicker {
            border: none; border-radius: 7px; padding: .3rem .7rem;
            font-size: .85rem; font-weight: 600; cursor: pointer;
            background: rgba(255,255,255,.2); color: #fff; outline: none;
        }
        #tglLaporanPicker::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand" href="#">
        <div class="brand-icon"><i class="bi bi-whatsapp"></i></div>
        <div>WA Gateway<span class="brand-sub">RSU Islam Klaten V.02</span></div>
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

    <div class="card-modern mt-3">
        <div class="card-header-modern">
            <i class="bi bi-send-fill"></i> Kirim Notifikasi ke Pasien
        </div>
        <div class="card-body-modern">
            <form id="whatsappForm">
                <?php if (!empty($data)): ?>
                <div class="mb-3">
                    <label class="form-label-modern"><i class="bi bi-telephone"></i> &nbsp;Nomor Penerima</label>
                    <input type="text" id="numbers" name="numbers" class="form-control-modern"
                           value="<?php echo implode(',', array_unique(call_user_func_array('array_merge', array_values($data)))); ?>" readonly>
                    <small class="text-muted" style="font-size:.78rem;">
                        <i class="bi bi-people"></i>
                        <?php echo count(array_unique(call_user_func_array('array_merge', array_values($data)))); ?> nomor penerima
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label-modern"><i class="bi bi-chat-text"></i> &nbsp;Isi Pesan</label>
                    <textarea id="message" name="message" class="form-control-modern" rows="10" readonly>📢 *Pengumuman RSU Islam Klaten*

Assalâmu'alaikum wr wb

#SahabatSehatRSIKlaten Kami beritahukan perubahan jam praktik sebagai berikut :

<?php
$index = 1;
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
_Ramah, Amanah, Profesional, Islami_ (RAPI)</textarea>
                </div>
                <div class="mb-3 d-flex" style="gap:.6rem;">
                    <button type="button" id="editMessage" class="btn-modern secondary" style="width:auto;flex:1;">
                        <i class="bi bi-pencil-square"></i> Edit Pesan
                    </button>
                    <button type="button" id="cancelEdit" class="btn-modern secondary" style="display:none;width:auto;flex:1;">
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

    <div class="laporan-card" id="laporanCard">

        <div class="laporan-overlay" id="laporanOverlay">
            <div class="spinner"></div>
            <p>Memuat laporan...</p>
        </div>

        <div class="laporan-header">
            <div class="lh-title"><i class="bi bi-journal-text"></i> Laporan Harian Jadwal Dokter</div>
            <div class="d-flex align-items-center" style="gap:.5rem;">
                <label style="font-size:.8rem;font-weight:600;opacity:.9;margin:0;white-space:nowrap;">
                    <i class="bi bi-calendar3"></i> Pilih Tanggal:
                </label>
                <input type="date" id="tglLaporanPicker"
                       value="<?= htmlspecialchars($tglLaporan) ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="laporan-body" id="laporanBody">
            <?php if (empty($perubahanJam) && empty($ijinDokter)): ?>
                <div class="laporan-empty"><i class="bi bi-inbox"></i>Belum ada data laporan untuk tanggal ini.</div>
            <?php else: ?>

            <?php if (!empty($perubahanJam)): ?>
            <span class="laporan-section-title jam"><i class="bi bi-clock-history"></i> Perubahan Jam &amp; Info Poli</span>
            <ul class="laporan-list jam">
                <?php foreach ($perubahanJam as $i => $e): ?>
                <li>
                    <span class="no-urut"><?= $i+1 ?>.</span>
                    <span>
                        <span class="poli-name"><?= htmlspecialchars($e['poli']) ?></span>
                        <span class="dokter-name"> <?= htmlspecialchars($e['dokter']) ?></span>
                        <?php if ($e['jam']): ?><span class="jam-info"> <?= htmlspecialchars($e['jam']) ?>.</span><?php endif; ?>
                    </span>
                    <span class="sistem-info"><?= htmlspecialchars($e['sistem']) ?> <i class="bi bi-check-circle-fill chk"></i></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($ijinDokter)): ?>
            <span class="laporan-section-title ijin"><i class="bi bi-person-x-fill"></i> Ijin Dokter</span>
            <ul class="laporan-list ijin">
                <?php foreach ($ijinDokter as $i => $e): ?>
                <li>
                    <span class="no-urut"><?= $i+1 ?>.</span>
                    <span>
                        <span class="poli-name"><?= htmlspecialchars($e['poli']) ?></span>
                        <span class="dokter-name"> <?= htmlspecialchars($e['dokter']) ?>.</span>
                    </span>
                    <span class="sistem-info"><?= htmlspecialchars($e['sistem']) ?> <i class="bi bi-check-circle-fill chk"></i></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <div class="laporan-footer">
                <div class="laporan-stat">
                    <?php if (!empty($perubahanJam)): ?>
                    <span><i class="bi bi-clock-history" style="color:var(--wa-green);"></i><?= count($perubahanJam) ?> Perubahan Jam</span>
                    <?php endif; ?>
                    <?php if (!empty($ijinDokter)): ?>
                    <span><i class="bi bi-person-x-fill" style="color:var(--danger);"></i><?= count($ijinDokter) ?> Ijin Dokter</span>
                    <?php endif; ?>
                </div>
                <button class="laporan-copy-btn" onclick="salinLaporan()">
                    <i class="bi bi-clipboard2-check"></i> Salin Laporan
                </button>
            </div>
            <textarea id="laporanTeks" style="position:absolute;opacity:0;pointer-events:none;height:0;"><?= htmlspecialchars($laporanTeks) ?></textarea>

            <?php endif; ?>
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
                        <th>No. RM</th><th>No. HP</th><th>Nama Pasien</th><th>Pesan</th>
                        <th>Poli</th><th>Dokter</th><th>Tgl Kirim</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $koneksi = mysqli_connect('192.168.0.33', 'admin', 'admin3dp', 'rsiklaten');
                    if (mysqli_connect_errno()) { echo "Failed to connect: " . mysqli_connect_error(); exit(); }
                    $qRiwayat = "SELECT batal_praktek_detil_wa.no_reg, batal_praktek_detil_wa.no_hp,
                                        batal_praktek_detil_wa.nama_pasien, batal_praktek_detil_wa.pesan,
                                        batal_praktek_detil_wa.nama_poli, batal_praktek_detil_wa.tgl_kirim_pesan,
                                        batal_praktek_detil_wa.status, dokter.nama AS nama_dokter
                                 FROM batal_praktek_detil_wa
                                 INNER JOIN dokter ON dokter.no_dr = batal_praktek_detil_wa.kode_dokter
                                 ORDER BY id DESC LIMIT 1500";
                    $resRiwayat = mysqli_query($koneksi, $qRiwayat);
                    while ($row = mysqli_fetch_assoc($resRiwayat)):
                        if ($row['status'] == 1)      $badge = "<span class='badge-status sent'><i class='bi bi-check-circle-fill'></i> Terkirim</span>";
                        elseif ($row['status'] == 2)  $badge = "<span class='badge-status failed'><i class='bi bi-x-circle-fill'></i> Gagal</span>";
                        else                          $badge = "<span class='badge-status unknown'><i class='bi bi-dash-circle'></i> N/A</span>";
                    ?>
                    <tr>
                        <td><span style="font-size:.8rem;font-weight:600;"><?= $row['no_reg'] ?></span></td>
                        <td><?= $row['no_hp'] ?></td>
                        <td style="font-weight:500;"><?= $row['nama_pasien'] ?></td>
                        <td style="max-width:260px;font-size:.8rem;color:#555;"><?= $row['pesan'] ?></td>
                        <td><span style="background:#e8f5f0;color:#0d6e4f;padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600;"><?= $row['nama_poli'] ?></span></td>
                        <td style="font-size:.85rem;"><?= $row['nama_dokter'] ?></td>
                        <td style="font-size:.82rem;white-space:nowrap;"><?= $row['tgl_kirim_pesan'] ?></td>
                        <td><?= $badge ?></td>
                    </tr>
                    <?php endwhile; mysqli_close($koneksi); ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code-scan"></i> &nbsp;Scan Ulang WhatsApp</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="text-muted" style="font-size:.85rem;">Buka WhatsApp di HP Anda → Menu → Perangkat Tertaut → Tautkan Perangkat</p>
                <img id="qrImage" src="" alt="QR Code" class="img-fluid rounded" style="max-width:280px;display:block;margin:0 auto;box-shadow:0 4px 16px rgba(0,0,0,.15);">
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
        <img src="https://rsuislamklaten.co.id/assets_front/images/logo-rsi-single.png" width="22" style="vertical-align:middle;margin-right:.4rem;">
        &copy; <?php echo date("Y"); ?> RSU Islam Klaten &mdash;
        Dikembangkan oleh <a href="https://www.harisuix.com/" target="_blank">Muhammad Haris Chaidir</a>
    </div>
</footer>

<button id="backToTop" title="Kembali ke atas" style="
    display:none; position:fixed; bottom:2rem; right:2rem; z-index:9999;
    width:44px; height:44px; border:none; border-radius:50%; cursor:pointer;
    background:linear-gradient(135deg,var(--primary),var(--primary-light));
    color:#fff; font-size:1.2rem; box-shadow:0 4px 14px rgba(13,110,79,.4);
    transition:all .2s; align-items:center; justify-content:center;">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
function renderLaporan(data) {
    var html = '';

    if (!data.perubahanJam.length && !data.ijinDokter.length) {
        html = '<div class="laporan-empty"><i class="bi bi-inbox"></i>Belum ada data laporan untuk tanggal ini.</div>';
        document.getElementById('laporanBody').innerHTML = html;
        return;
    }

    if (data.perubahanJam.length) {
        html += '<span class="laporan-section-title jam"><i class="bi bi-clock-history"></i> Perubahan Jam &amp; Info Poli</span>';
        html += '<ul class="laporan-list jam">';
        data.perubahanJam.forEach(function(e, i) {
            html += '<li>';
            html += '<span class="no-urut">' + (i+1) + '.</span>';
            html += '<span><span class="poli-name">' + e.poli + '</span>';
            html += '<span class="dokter-name"> ' + e.dokter + '</span>';
            if (e.jam) html += '<span class="jam-info"> ' + e.jam + '.</span>';
            html += '</span>';
            html += '<span class="sistem-info">' + e.sistem + ' <i class="bi bi-check-circle-fill chk"></i></span>';
            html += '</li>';
        });
        html += '</ul>';
    }

    if (data.ijinDokter.length) {
        html += '<span class="laporan-section-title ijin"><i class="bi bi-person-x-fill"></i> Ijin Dokter</span>';
        html += '<ul class="laporan-list ijin">';
        data.ijinDokter.forEach(function(e, i) {
            html += '<li>';
            html += '<span class="no-urut">' + (i+1) + '.</span>';
            html += '<span><span class="poli-name">' + e.poli + '</span>';
            html += '<span class="dokter-name"> ' + e.dokter + '.</span></span>';
            html += '<span class="sistem-info">' + e.sistem + ' <i class="bi bi-check-circle-fill chk"></i></span>';
            html += '</li>';
        });
        html += '</ul>';
    }

    var stat = '';
    if (data.perubahanJam.length) stat += '<span><i class="bi bi-clock-history" style="color:var(--wa-green);"></i>' + data.perubahanJam.length + ' Perubahan Jam</span>';
    if (data.ijinDokter.length)   stat += '<span><i class="bi bi-person-x-fill" style="color:var(--danger);"></i>' + data.ijinDokter.length + ' Ijin Dokter</span>';

    html += '<div class="laporan-footer">';
    html += '<div class="laporan-stat">' + stat + '</div>';
    html += '<button class="laporan-copy-btn" onclick="salinLaporan()"><i class="bi bi-clipboard2-check"></i> Salin Laporan</button>';
    html += '</div>';
    html += '<textarea id="laporanTeks" style="position:absolute;opacity:0;pointer-events:none;height:0;">' + data.teksPlain + '</textarea>';

    document.getElementById('laporanBody').innerHTML = html;
}

document.getElementById('tglLaporanPicker').addEventListener('change', function() {
    var tgl = this.value;
    if (!tgl) return;

    var overlay = document.getElementById('laporanOverlay');
    overlay.classList.add('show');

    var fd = new FormData();
    fd.append('ajax_laporan', '1');
    fd.append('tgl_laporan', tgl);

    fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderLaporan(data);
        })
        .catch(function() {
            document.getElementById('laporanBody').innerHTML =
                '<div class="laporan-empty"><i class="bi bi-exclamation-circle"></i>Gagal memuat data. Coba lagi.</div>';
        })
        .finally(function() {
            overlay.classList.remove('show');
        });
});

document.getElementById('btnScanWA').addEventListener('click', function(e) {
    e.preventDefault();
    var url = "http://192.168.0.93:8049/service_wa_with_venom/qr_code.png";
    var img = document.getElementById('qrImage');
    var fb  = document.getElementById('qrFallback');
    img.style.display = 'block'; fb.style.display = 'none';
    img.src = url + "?t=" + Date.now();
    img.onerror = function() { img.style.display = 'none'; fb.style.display = 'block'; };
    $('#qrModal').modal('show');
});

$(document).ready(function() {
    var table = $('#TblBtlKirim').DataTable({ responsive: true });
    $('#btnCari').on('click', function() {
        var status = $('#statusKirim').val();
        table.columns(5).search($('#namaDokter').val().trim());
        table.columns(0).search($('#noReg').val().trim());
        table.columns(1).search($('#noTelp').val().trim());
        table.columns(4).search($('#poli').val().trim());
        table.columns(6).search($('#tglKirim').val().trim());
        table.columns(7).search(status === '1' ? 'Terkirim' : status === '2' ? 'Gagal' : '').draw();
    });
    $('#btnReset').on('click', function() {
        $('#namaDokter, #noReg, #noTelp, #poli, #tglKirim').val('');
        $('#statusKirim').val('');
        table.columns().search('').draw();
    });
});

document.getElementById('editMessage').addEventListener('click', function() {
    Swal.fire({
        title: 'Edit Pesan?', text: 'Anda akan mengubah isi pesan sebelum dikirim.',
        icon: 'question', showCancelButton: true,
        confirmButtonText: '<i class="bi bi-pencil"></i> Ya, Edit',
        cancelButtonText: 'Batal', confirmButtonColor: '#0d6e4f'
    }).then(function(r) {
        if (r.isConfirmed) {
            document.getElementById('message').readOnly = false;
            document.getElementById('message').focus();
            document.getElementById('editMessage').style.display = 'none';
            document.getElementById('cancelEdit').style.display  = 'inline-flex';
        }
    });
});

document.getElementById('cancelEdit').addEventListener('click', function() {
    document.getElementById('message').readOnly = true;
    document.getElementById('editMessage').style.display = 'inline-flex';
    document.getElementById('cancelEdit').style.display  = 'none';
});

function salinLaporan() {
    var el = document.getElementById('laporanTeks');
    if (!el) { Swal.fire('Kosong', 'Tidak ada data laporan.', 'info'); return; }
    var teks = el.value.trim();
    if (!teks) { Swal.fire('Kosong', 'Tidak ada data laporan.', 'info'); return; }
    var done = function() {
        Swal.fire({ icon: 'success', title: 'Tersalin!', text: 'Laporan siap ditempel ke WhatsApp.', timer: 1800, showConfirmButton: false });
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(teks).then(done).catch(function() {
            var ta = document.createElement('textarea');
            ta.value = teks; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta); done();
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = teks; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta); done();
    }
}

var btn = document.getElementById('backToTop');
window.addEventListener('scroll', function() { btn.style.display = scrollY > 300 ? 'flex' : 'none'; });
btn.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });
btn.addEventListener('mouseenter', function() { btn.style.transform = 'translateY(-3px)'; btn.style.boxShadow = '0 6px 20px rgba(13,110,79,.55)'; });
btn.addEventListener('mouseleave', function() { btn.style.transform = 'translateY(0)';    btn.style.boxShadow = '0 4px 14px rgba(13,110,79,.4)';  });
</script>
<script src="./assets/js/master.js"></script>
</body>
</html>