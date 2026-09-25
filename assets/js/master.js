$(document).ready(function () {

    var tableInitialized = false;
    var table;

    function initDataTable() {
        if (tableInitialized) return;
        tableInitialized = true;

        $('#TblBtlKirim tbody').empty();

        table = $('#TblBtlKirim').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            deferRender: true,
            language: {
                processing:   '<div class="dt-loading"><i class="bi bi-arrow-repeat spin"></i> Memuat data...</div>',
                search:       'Cari:',
                lengthMenu:   'Tampilkan _MENU_ data',
                info:         'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty:    'Tidak ada data tersedia',
                infoFiltered: '(difilter dari _MAX_ total data)',
                zeroRecords:  '<div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada data ditemukan.</p></div>',
                paginate: { first: '«', last: '»', next: '›', previous: '‹' }
            },
            ajax: {
                url:  'ajax_riwayat.php',
                type: 'POST',
                data: function (d) {
                    d.no_reg      = $('#noReg').val();
                    d.no_telp     = $('#noTelp').val();
                    d.poli        = $('#poli').val();
                    d.nama_dokter = $('#namaDokter').val();
                    d.tgl_kirim   = $('#tglKirim').val();
                    d.status      = $('#statusKirim').val();
                },
                cache: false,
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: 'Terjadi kesalahan saat mengambil data riwayat.',
                        confirmButtonColor: '#0d6e4f'
                    });
                }
            },
            columns: [
                { orderable: false, width: '90px'  },
                { orderable: false, width: '110px' },
                { orderable: false                 },
                { orderable: false                 },
                { orderable: false, width: '120px' },
                { orderable: false                 },
                { orderable: false, width: '130px' },
                { orderable: false, width: '100px' },
            ],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            dom: '<"dt-top-bar"lf>rt<"dt-bottom-bar"ip>',

            stateSave: false,
            autoWidth: false,
        });

        bindFilterEvents();
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    initDataTable();
                    observer.disconnect();
                }
            });
        }, { rootMargin: '200px' });

        var tableEl = document.getElementById('TblBtlKirim');
        if (tableEl) observer.observe(tableEl);
    } else {
        setTimeout(initDataTable, 300);
    }

    function bindFilterEvents() {
        var btnCariHtml  = $('#btnCari').html();
        var btnResetHtml = $('#btnReset').html();

        function setBtnLoading(btn, isLoading, originalHtml) {
            if (isLoading) {
                btn.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span> Mencari...');
            } else {
                btn.prop('disabled', false).html(originalHtml);
            }
        }

        $('#btnCari').on('click', function () {
            if (!tableInitialized) { initDataTable(); return; }
            var btn = $(this);
            setBtnLoading(btn, true);
            table.ajax.reload(function () {
                setBtnLoading(btn, false, btnCariHtml);
            });
        });

        var debounceTimer;
        $('#noReg, #noTelp, #poli, #namaDokter, #tglKirim, #statusKirim').on('input change', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                if (tableInitialized) table.ajax.reload(null, false);
            }, 400);
        });

        $('#noReg, #noTelp, #poli, #namaDokter, #tglKirim').on('keydown', function (e) {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                $('#btnCari').trigger('click');
            }
        });

        $('#btnReset').on('click', function () {
            if (!tableInitialized) return;
            var btn = $(this);
            $('#namaDokter, #noReg, #noTelp, #poli, #tglKirim').val('');
            $('#statusKirim').val('');
            setBtnLoading(btn, true);
            table.ajax.reload(function () {
                setBtnLoading(btn, false, btnResetHtml);
            });
        });
    }

    $('#whatsappForm').on('submit', function (event) {
        event.preventDefault();

        var numbers = $('#numbers').val().trim();
        var message = $('#message').val().trim();

        if (!numbers || !message) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Tidak Lengkap',
                text: 'Nomor penerima dan pesan tidak boleh kosong.',
                confirmButtonColor: '#0d6e4f'
            });
            return;
        }

        var numberList   = numbers.split(',').map(function (n) { return n.trim(); }).filter(Boolean);
        var totalNumbers = numberList.length;
        var estimasiDetik = totalNumbers * 7;
        var estimasiLabel = estimasiDetik < 60
            ? estimasiDetik + ' detik'
            : Math.ceil(estimasiDetik / 60) + ' menit';

        Swal.fire({
            title: 'Konfirmasi Pengiriman',
            html:  '<p>Akan dikirim ke <strong>' + totalNumbers + ' nomor</strong>.</p>' +
                   '<p>Estimasi waktu: <strong>~' + estimasiLabel + '</strong></p>' +
                   '<small class="text-muted">Jangan tutup halaman selama proses berlangsung.</small>',
            icon: 'question',
            showCancelButton:  true,
            confirmButtonText: '<i class="bi bi-send-fill"></i> Ya, Kirim!',
            cancelButtonText:  'Batal',
            confirmButtonColor: '#0d6e4f',
            cancelButtonColor:  '#6c757d',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Mengirim Pesan...',
                html:  '<div>Mengirim ke <b>' + totalNumbers + '</b> nomor.<br>' +
                       '<small class="text-muted">Mohon tunggu, jangan tutup halaman ini.</small></div>' +
                       '<div style="margin-top:1rem;background:#e5e7eb;border-radius:20px;height:8px;">' +
                       '<div id="swal-progress-bar" style="width:5%;height:8px;background:linear-gradient(90deg,#0d6e4f,#25d366);border-radius:20px;transition:width .4s;"></div>' +
                       '</div>',
                allowOutsideClick: false,
                allowEscapeKey:    false,
                showConfirmButton:  false,
                didOpen: function () { Swal.showLoading(); }
            });

            $.ajax({
                url:         'http://192.168.0.93:3000/send',
                type:        'POST',
                contentType: 'application/json',
                data:        JSON.stringify({ numbers: numbers, message: message }),
                timeout:     600000,
                success: function (sendResult) {
                    Swal.close();
                    if (sendResult.success) {
                        var berhasil = sendResult.data
                            ? sendResult.data.filter(function (r) { return r.status === 1; }).length
                            : totalNumbers;
                        var gagal = sendResult.data
                            ? sendResult.data.filter(function (r) { return r.status === 2; }).length
                            : 0;

                        Swal.fire({
                            icon:  gagal > 0 ? 'warning' : 'success',
                            title: 'Pengiriman Selesai',
                            html:  '<p>✅ Berhasil: <strong>' + berhasil + '</strong> nomor</p>' +
                                   (gagal > 0 ? '<p>❌ Gagal: <strong>' + gagal + '</strong> nomor</p>' : '') +
                                   '<small class="text-muted">Halaman akan dimuat ulang.</small>',
                            allowOutsideClick: false,
                            allowEscapeKey:    false,
                            confirmButtonColor: '#0d6e4f',
                        }).then(function () { location.reload(); });

                    } else {
                        Swal.fire({
                            icon:  'error',
                            title: 'Pesan Gagal Dikirim',
                            text:  sendResult.message || 'Gagal mengirim pesan.',
                            confirmButtonColor: '#0d6e4f',
                        }).then(function () { location.reload(); });
                    }
                },
                error: function (xhr, status) {
                    Swal.close();
                    var msg = 'Terjadi kesalahan saat mengirim pesan.';
                    if (status === 'timeout') {
                        msg = 'Request timeout. Server mungkin masih memproses. Silakan refresh halaman.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon:  'error',
                        title: 'Terjadi Kesalahan',
                        text:  msg,
                        confirmButtonColor: '#0d6e4f',
                    }).then(function () { location.reload(); });
                }
            });
        });
    });

    $('#editMessage').on('click', function () {
        Swal.fire({
            title: 'Edit Pesan?',
            text:  'Anda akan mengubah isi pesan sebelum dikirim.',
            icon:  'question',
            showCancelButton:  true,
            confirmButtonText: '<i class="bi bi-pencil"></i> Ya, Edit',
            cancelButtonText:  'Batal',
            confirmButtonColor: '#0d6e4f'
        }).then(function (result) {
            if (result.isConfirmed) {
                var ta = document.getElementById('message');
                ta.readOnly = false;
                ta.focus();
                $('#editMessage').hide();
                $('#cancelEdit').show();
            }
        });
    });

    $('#cancelEdit').on('click', function () {
        document.getElementById('message').readOnly = true;
        $('#editMessage').show();
        $('#cancelEdit').hide();
    });

    document.getElementById('btnScanWA').addEventListener('click', function (e) {
        e.preventDefault();
        var qrUrl      = 'http://192.168.0.93:8049/service_wa_with_venom/qr_code.png';
        var qrImage    = document.getElementById('qrImage');
        var qrFallback = document.getElementById('qrFallback');

        qrImage.style.display    = 'block';
        qrFallback.style.display = 'none';
        qrImage.src = qrUrl + '?t=' + Date.now();

        qrImage.onerror = function () {
            qrImage.style.display    = 'none';
            qrFallback.style.display = 'block';
        };

        $('#qrModal').modal('show');
    });

    var btn = document.getElementById('backToTop');
    if (btn) {
        window.addEventListener('scroll', function () {
            btn.style.display = window.scrollY > 300 ? 'flex' : 'none';
        }, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        btn.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 6px 20px rgba(13,110,79,.55)';
        });
        btn.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 14px rgba(13,110,79,.4)';
        });
    }

});