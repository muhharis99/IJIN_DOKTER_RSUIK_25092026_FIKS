const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');

let clientReady = false;
let initializing = false;

const AUTH_PATH = path.join(__dirname, '.wwebjs_auth');

const client = new Client({
  authStrategy: new LocalAuth({
    dataPath: AUTH_PATH
  }),

  puppeteer: {
    headless: true,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu'
    ]
  }

});

client.on('qr', async (qr) => {
  console.log('\n========================================');
  console.log('📸 SCAN QR WHATSAPP');
  console.log('========================================\n');

  qrcode.generate(qr, { small: true });

  try {
    const qrPath = path.join(__dirname, 'qr_code.png');

    await QRCode.toFile(qrPath, qr);

    console.log(`🖼️ QR disimpan: ${qrPath}`);
  } catch (err) {
    console.error('❌ Gagal menyimpan QR:', err.message);
  }
});

client.on('authenticated', () => {
  console.log('🔐 WhatsApp authenticated');
});

client.on('ready', async () => {
  clientReady = true;

  console.log('\n========================================');
  console.log('🟢 WHATSAPP READY');
  console.log('========================================');

  try {
    const version = await client.getWWebVersion();
    console.log('🌐 WhatsApp Web Version:', version);
  } catch (err) {
    console.warn('⚠️ Tidak dapat membaca WA Web version:', err.message);
  }

  try {
    const state = await client.getState();
    console.log('🔍 State:', state);
  } catch (err) {
    console.warn('⚠️ Tidak dapat membaca state:', err.message);
  }

  console.log('========================================\n');
});

client.on('auth_failure', (msg) => {
  console.error('❌ WhatsApp auth failure:', msg);
  clientReady = false;
});

client.on('disconnected', async (reason) => {
  console.warn('🔴 WhatsApp disconnected:', reason);

  clientReady = false;

});

client.on('change_state', (state) => {
  console.log('🔄 WhatsApp state berubah:', state);

  if (state === 'CONNECTED') {
    clientReady = true;
  } else if (
    state === 'DISCONNECTED' ||
    state === 'UNPAIRED' ||
    state === 'UNPAIRED_IDLE'
  ) {
    clientReady = false;
  }
});

client.on('error', (err) => {
  console.error('❌ WhatsApp client error:', err.message);
});

const delay = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

function normalizeNumber(number) {
  const n = String(number || '').replace(/\D/g, '');

  if (!n) {
    return null;
  }

  if (n.startsWith('0')) {
    return '62' + n.slice(1);
  }

  if (n.startsWith('8')) {
    return '62' + n;
  }

  if (n.startsWith('62')) {
    return n;
  }

  return n;
}

async function ensureConnected() {
  try {
    const state = await client.getState();

    console.log('🔍 Current state:', state);

    if (state === 'CONNECTED') {
      clientReady = true;
      return true;
    }

    clientReady = false;

    console.warn('⚠️ WhatsApp belum CONNECTED:', state);

    return false;

  } catch (err) {
    clientReady = false;

    console.error(
      '❌ Gagal mengecek state WhatsApp:',
      err.message
    );

    return false;
  }
}

async function sendMessage(numbers, message) {

  if (!clientReady) {
    const connected = await ensureConnected();

    if (!connected) {
      throw new Error(
        'WhatsApp belum READY / CONNECTED. Silakan scan QR atau tunggu koneksi.'
      );
    }
  }

  if (!numbers || !message) {
    throw new Error('Nomor dan pesan wajib diisi.');
  }

  const list = String(numbers)
    .split(',')
    .map(n => n.trim())
    .filter(Boolean);

  const results = [];

  console.log(`📨 Total penerima: ${list.length}`);

  for (const originalNumber of list) {

    const intl = normalizeNumber(originalNumber);

    if (!intl) {
      console.error(`❌ Nomor tidak valid: ${originalNumber}`);

      results.push({
        number: originalNumber,
        status: 2
      });

      continue;
    }

    const chatId = `${intl}@c.us`;

    console.log(`\n📤 Mengirim ke: ${intl}`);

    try {

      const connected = await ensureConnected();

      if (!connected) {
        throw new Error(
          'WhatsApp terputus sebelum pengiriman.'
        );
      }

      let isRegistered = false;

      try {
        isRegistered = await client.isRegisteredUser(chatId);
      } catch (err) {
        console.warn(
          `⚠️ Gagal mengecek nomor ${intl}:`,
          err.message
        );

        // Kita tetap coba kirim.
        isRegistered = true;
      }

      if (!isRegistered) {
        console.warn(
          `⚠️ Nomor ${intl} tidak terdaftar di WhatsApp`
        );

        results.push({
          number: intl,
          status: 2
        });

        continue;
      }

      let sent = false;

      for (let attempt = 1; attempt <= 2; attempt++) {

        try {

          console.log(
            `📨 Attempt ${attempt}/2 -> ${intl}`
          );

          // PENTING:
          // Jangan gunakan { sendSeen: false }
          await client.sendMessage(
            chatId,
            message
          );

          console.log(
            `✅ BERHASIL terkirim ke ${intl}`
          );

          results.push({
            number: intl,
            status: 1
          });

          sent = true;

          break;

        } catch (err) {

          console.error(
            `❌ Gagal attempt ${attempt} ke ${intl}:`,
            err.message
          );

          if (attempt === 1) {
            console.log(
              '⏳ Tunggu 5 detik sebelum retry...'
            );

            await delay(5000);

            const connectedAfterError =
              await ensureConnected();

            if (!connectedAfterError) {
              throw new Error(
                'WhatsApp terputus setelah gagal mengirim.'
              );
            }
          } else {
            throw err;
          }
        }
      }


      if (!sent) {
        throw new Error('Gagal mengirim pesan.');
      }

    } catch (err) {

      console.error(
        `❌ FINAL ERROR ${originalNumber}:`,
        err.message
      );

      results.push({
        number: intl,
        status: 2
      });
    }


    /*
     * Delay antar nomor.
     * 7 detik seperti sistem lama.
     */
    await delay(7000);
  }

  return results;
}

async function initializeClient() {

  if (initializing) {
    return;
  }

  initializing = true;

  try {

    console.log('\n========================================');
    console.log('🚀 Initializing WhatsApp client...');
    console.log('========================================\n');

    await client.initialize();

  } catch (err) {

    console.error(
      '❌ Gagal initialize WhatsApp:',
      err.message
    );

    initializing = false;
  }
}

initializeClient();


module.exports = {
  sendMessage,
  client
};