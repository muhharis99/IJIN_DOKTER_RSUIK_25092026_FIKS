$(document).ready(function() {
    $('#whatsappForm').submit(function(event) {
        event.preventDefault();

        var numbers = $('#numbers').val();
        var message = $('#message').val();

        var loadingSwal = Swal.fire({
            title: 'Mengirim Pesan',
            html: 'Mohon tunggu, pesan sedang dikirim...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: function() {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'http://192.168.0.93:3000/send',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ numbers: numbers, message: message }),
            success: function(sendResult) {
                if (sendResult.success) {
                    loadingSwal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Semua Pesan Terkirim',
                        text: 'Semua pesan berhasil dikirim.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    loadingSwal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Pesan Gagal Dikirim',
                        text: 'Pesan gagal dikirim ke ' + numbers,
                    }).then(function() {
                        location.reload();
                    });
                }
            },
            error: function(error) {
                loadingSwal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: 'Terjadi kesalahan saat mengirim pesan.',
                }).then(function() {
                    location.reload();
                });
            }
        });
    });
});