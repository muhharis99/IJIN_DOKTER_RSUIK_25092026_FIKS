const venom = require('venom-bot');

venom
  .create({
    session: 'session',
    multidevice: true // Menggunakan multi-device
  })
  .then((client) => start(client))
  .catch((error) => console.log(error));

function start(client) {
  // Fungsi untuk mengonversi nomor dari awalan 0 ke awalan 62
  const convertNumberToInternational = (number) => {
    if (number.startsWith('0')) {
      return '62' + number.slice(1);
    }
    return number;
  };

  // Fungsi untuk mengirim pesan ke nomor tertentu
  const sendMessageToNumber = async (number, message) => {
    try {
      const internationalNumber = convertNumberToInternational(number);
      const chatId = `${internationalNumber}@c.us`; // Format nomor telepon
      await client.sendText(chatId, message);
      console.log(`Pesan berhasil dikirim ke ${internationalNumber}`);
    } catch (error) {
      console.error(`Gagal mengirim pesan ke ${internationalNumber}:`, error);
    }
  };

  // Menangani perubahan status
  client.onStateChange((state) => {
    console.log('State changed:', state);
    const conflicts = [
      venom.SocketState.CONFLICT,
      venom.SocketState.UNPAIRED,
      venom.SocketState.UNPAIRED_IDLE,
    ];
    if (conflicts.includes(state)) {
      client.useHere();
    }
  });

  // Event untuk mendapatkan kode QR
  client.onStreamChange((state) => {
    console.log('Stream state:', state);
    if (state === 'OPENING' || state === 'CLOSED') client.refresh();
  });

  // Ganti nomor tujuan dan pesan yang ingin dikirim
  const targetNumber = '08972870408'; // Nomor telepon dengan awalan 0
  const message = 'test bot';

  // Mengirim pesan ke nomor tujuan
  sendMessageToNumber(targetNumber, message);
}
