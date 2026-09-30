# WhatsApp Policy Compliance Hardening

Tanggal: 30 September 2026

Repository ini sekarang memiliki guardrail kepatuhan untuk mengurangi risiko pengiriman pesan yang tidak diinginkan.

## Yang diterapkan

1. **Explicit opt-in wajib untuk notifikasi layanan**
   - Nomor baru berstatus unknown dan tidak dikirimi pesan.
   - Penerima dapat mengirim DAFTAR untuk mengaktifkan notifikasi layanan.
   - Persetujuan disimpan dengan waktu dan sumbernya.

2. **Opt-out langsung**
   - STOP, BERHENTI, UNSUBSCRIBE, OPTOUT, dan beberapa variasi lain menonaktifkan notifikasi.
   - Setelah opt-out, gateway menolak pengiriman ke nomor tersebut.

3. **Duplicate protection**
   - Pesan yang sama ke nomor yang sama diblokir selama window duplikasi.
   - Default: 360 menit.

4. **Recipient daily limit**
   - Default maksimal 10 pesan berhasil per nomor per hari.
   - Nilai dapat diubah melalui environment variable WA_MAX_DAILY_PER_NUMBER.

5. **Batch protection**
   - Default maksimal 500 nomor dalam satu request.
   - Nilai dapat diubah melalui WA_MAX_BATCH_SIZE.

6. **Audit log**
   - Aktivitas pengiriman disimpan pada wa_send_log.
   - Data consent disimpan pada wa_contact_consent.

7. **Status operator**
   - 1 = Terkirim
   - 2 = Gagal
   - 3 = Ditahan oleh guardrail
   - 4 = Opt-out
   - 5 = Duplikat

8. **Inbound consent handling**
   - Pesan masuk dicatat sebagai last_inbound_at.
   - Keyword consent diproses secara eksplisit dan tidak semua pesan masuk otomatis dianggap sebagai persetujuan.

## Catatan penting

Guardrail ini adalah lapisan pencegahan pada aplikasi. Ini bukan mekanisme untuk mengakali, menyembunyikan, atau menonaktifkan penegakan WhatsApp.

Kebijakan WhatsApp Business menyatakan bahwa bisnis hanya boleh menghubungi orang melalui WhatsApp bila penerima telah memberikan nomor dan persetujuan opt-in yang menyatakan secara jelas keinginan menerima pesan serta nama bisnis. Kebijakan tersebut juga mewajibkan penghormatan terhadap permintaan berhenti menerima komunikasi. Untuk Platform WhatsApp Business, percakapan yang dimulai bisnis harus menggunakan template yang disetujui, sedangkan balasan non-template diizinkan dalam jendela 24 jam setelah pesan pengguna. WhatsApp juga menyatakan dapat membatasi atau menghapus akses bila layanan digunakan untuk pengiriman massal yang tidak sah.

Sumber resmi:
https://business.whatsapp.com/policy/preview?lang=id_ID

## Transport yang digunakan

Transport pada repository ini tetap menggunakan whatsapp-web.js. Guardrail di atas tidak mengubah library tersebut menjadi WhatsApp Business Platform/Cloud API resmi.

Untuk penggunaan produksi yang membutuhkan kepatuhan platform secara penuh, jalur yang relevan adalah migrasi transport ke WhatsApp Business Platform / Cloud API resmi dengan template pesan yang disetujui untuk pesan yang diinisiasi bisnis.
