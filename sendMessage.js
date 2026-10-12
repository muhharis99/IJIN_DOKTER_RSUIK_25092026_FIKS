const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');

let clientReady = false;
let initializing = false;
let policyStore = null;

const connectionStatus = {
  phase: 'starting',
  label: 'Memulai koneksi WhatsApp...',
  state: 'UNKNOWN',
  qrAvailable: false,
  loadingPercent: null,
  loadingMessage: '',
  authenticated: false,
  ready: false,
  webVersion: null,
  updatedAt: new Date().toISOString(),
  events: []
};

function updateCompletedConnectionEventTypes() {
  const loadingEvents = connectionStatus.events
    .map(event => {
      const match = String(event.label || '').match(/^WhatsApp loading:\s*(\d+(?:\.\d+)?)%/i);
      return match ? { event, percent: Number(match[1]) } : null;
    })
    .filter(Boolean);

  // Saat persentase bertambah, langkah loading yang lebih rendah sudah terlewati.
  const currentPercent = Number(connectionStatus.loadingPercent);
  if (Number.isFinite(currentPercent)) {
    loadingEvents.forEach(({ event, percent }) => {
      if (percent < currentPercent && event.type !== 'error') {
        event.type = 'success';
      }
    });
  }

  // Setelah autentikasi berhasil, langkah QR tidak lagi menjadi proses yang tertunda.
  if (connectionStatus.authenticated) {
    connectionStatus.events.forEach(event => {
      if (/^QR diterima; menunggu pemindaian\.$/.test(String(event.label || '')) && event.type !== 'error') {
        event.type = 'success';
      }
    });
  }

  // READY berarti seluruh langkah koneksi yang sementara (kuning/biru) telah selesai.
  // Peringatan nyata dan error (mis. gagal membaca versi atau status) tetap terlihat.
  if (connectionStatus.ready || connectionStatus.phase === 'ready') {
    const completedPatterns = [
      /^QR diterima; menunggu pemindaian\.$/,
      /^WhatsApp loading:/,
      /^WhatsApp authenticated; menunggu sinkronisasi sesi dan event READY\.\.\.$/,
      /^State berubah: CONNECTED$/
    ];

    connectionStatus.events.forEach(event => {
      const label = String(event.label || '');
      if (event.type !== 'error' && completedPatterns.some(pattern => pattern.test(label))) {
        event.type = 'success';
      }
    });
  }
}

function updateConnectionStatus(patch, eventLabel, type = 'info') {
  Object.assign(connectionStatus, patch, {
    updatedAt: new Date().toISOString()
  });

  if (eventLabel) {
    connectionStatus.events.push({
      label: eventLabel,
      type,
      at: new Date().toISOString()
    });
    connectionStatus.events = connectionStatus.events.slice(-12);
  }

  updateCompletedConnectionEventTypes();
}

function getConnectionStatus() {
  return {
    ...connectionStatus,
    events: connectionStatus.events.map(event => ({ ...event }))
  };
}

function setPolicyStore(store) {
  policyStore = store;
}

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
  updateConnectionStatus({
    phase: 'qr_required',
    label: 'QR tersedia. Silakan pindai menggunakan WhatsApp di HP.',
    qrAvailable: true,
    authenticated: false,
    ready: false,
    loadingPercent: null,
    loadingMessage: ''
  }, 'QR diterima; menunggu pemindaian.', 'warning');

  console.log('\n========================================');
  console.log('📸 SCAN QR WHATSAPP');
  console.log('========================================\n');

  qrcode.generate(qr, { small: true });

  try {
    const qrPath = path.join(__dirname, 'qr_code.png');

    await QRCode.toFile(qrPath, qr);

    console.log(`🖼️ QR disimpan: ${qrPath}`);
    updateConnectionStatus({
      qrAvailable: true
    }, 'QR berhasil dibuat dan disimpan.', 'success');
  } catch (err) {
    console.error('❌ Gagal menyimpan QR:', err.message);
    updateConnectionStatus({
      phase: 'error',
      label: 'QR gagal disimpan. Periksa akses file qr_code.png.',
      qrAvailable: false
    }, 'Gagal menyimpan QR: ' + err.message, 'error');
  }
});

client.on('authenticated', () => {
  updateConnectionStatus({
    phase: 'authenticated',
    label: 'Autentikasi berhasil; menunggu sinkronisasi sesi...',
    authenticated: true,
    ready: false
  }, 'WhatsApp authenticated; menunggu sinkronisasi sesi dan event READY...', 'info');

  console.log('🔐 WhatsApp authenticated; menunggu sinkronisasi sesi dan event READY...');
});

client.on('loading_screen', (percent, message) => {
  const progress = Number(percent);
  updateConnectionStatus({
    phase: connectionStatus.authenticated ? 'syncing' : 'loading',
    label: connectionStatus.authenticated
      ? 'Autentikasi berhasil; sinkronisasi WhatsApp sedang berlangsung...'
      : 'WhatsApp sedang dimuat' + (Number.isFinite(progress) ? ': ' + progress + '%' : '...'),
    loadingPercent: Number.isFinite(progress) ? progress : null,
    loadingMessage: String(message || '')
  }, 'WhatsApp loading: ' + (Number.isFinite(progress) ? progress + '%' : 'proses berjalan') + (message ? ' - ' + message : ''), 'info');

  console.log(`⏳ WhatsApp loading: ${percent}%${message ? ' - ' + message : ''}`);
});

client.on('ready', async () => {
  clientReady = true;
  updateConnectionStatus({
    phase: 'ready',
    label: 'WhatsApp siap digunakan untuk mengirim pesan.',
    authenticated: true,
    ready: true,
    state: 'CONNECTED',
    qrAvailable: false,
    loadingPercent: 100
  }, 'WHATSAPP READY', 'success');

  console.log('\n========================================');
  console.log('🟢 WHATSAPP READY');
  console.log('========================================');

  try {
    const version = await client.getWWebVersion();
    updateConnectionStatus({
      webVersion: String(version || '')
    }, 'WhatsApp Web Version: ' + String(version || 'tidak diketahui'), 'success');
    console.log('🌐 WhatsApp Web Version:', version);
  } catch (err) {
    console.warn('⚠️ Tidak dapat membaca WA Web version:', err.message);
    updateConnectionStatus({}, 'Versi WhatsApp Web tidak dapat dibaca: ' + err.message, 'warning');
  }

  try {
    const state = await client.getState();
    updateConnectionStatus({
      state: String(state || 'UNKNOWN')
    }, 'State: ' + String(state || 'UNKNOWN'), state === 'CONNECTED' ? 'success' : 'warning');
    console.log('🔍 State:', state);
  } catch (err) {
    console.warn('⚠️ Tidak dapat membaca state:', err.message);
    updateConnectionStatus({}, 'Status koneksi tidak dapat dibaca: ' + err.message, 'warning');
  }

  console.log('========================================\n');
});

client.on('auth_failure', (msg) => {
  console.error('❌ WhatsApp auth failure:', msg);
  clientReady = false;
  updateConnectionStatus({
    phase: 'error',
    label: 'Autentikasi WhatsApp gagal. Periksa terminal dan coba sambungkan kembali.',
    authenticated: false,
    ready: false
  }, 'WhatsApp auth failure: ' + String(msg || 'alasan tidak diketahui'), 'error');
});

client.on('disconnected', async (reason) => {
  console.warn('🔴 WhatsApp disconnected:', reason);

  clientReady = false;
  updateConnectionStatus({
    phase: 'disconnected',
    label: 'WhatsApp terputus. Silakan tunggu koneksi pulih atau pindai QR jika diminta.',
    authenticated: false,
    ready: false,
    state: 'DISCONNECTED',
    qrAvailable: false
  }, 'WhatsApp disconnected: ' + String(reason || 'alasan tidak diketahui'), 'error');
});

client.on('change_state', (state) => {
  console.log('🔄 WhatsApp state berubah:', state);
  const disconnected = ['DISCONNECTED', 'UNPAIRED', 'UNPAIRED_IDLE'].includes(state);

  updateConnectionStatus({
    state: String(state || 'UNKNOWN'),
    ...(disconnected ? {
      phase: state === 'DISCONNECTED' ? 'disconnected' : 'qr_required',
      label: state === 'DISCONNECTED'
        ? 'WhatsApp terputus.'
        : 'WhatsApp belum tertaut. Pindai QR untuk menghubungkan perangkat.',
      ready: false,
      authenticated: false,
      qrAvailable: state !== 'DISCONNECTED'
    } : state === 'CONNECTED' && !connectionStatus.ready ? {
      phase: 'syncing',
      label: 'WhatsApp tersambung; menunggu proses sinkronisasi sampai READY...',
      ready: false
    } : {})
  }, 'State berubah: ' + String(state || 'UNKNOWN'), disconnected ? 'warning' : 'info');

  if (disconnected) {
    clientReady = false;
  } else if (state === 'CONNECTED' && connectionStatus.ready) {
    clientReady = true;
  }
});

client.on('message', async (message) => {
  if (!policyStore) return;
  try {
    await policyStore.recordInbound(message, client);
  } catch (err) {
    console.error('❌ Gagal memproses pesan masuk untuk policy guard:', err.message);
  }
});

client.on('error', (err) => {
  console.error('❌ WhatsApp client error:', err.message);
  if (!connectionStatus.ready) {
    updateConnectionStatus({
      phase: 'error',
      label: 'Terjadi kendala saat menghubungkan WhatsApp. Detail tersedia di terminal.'
    }, 'WhatsApp client error: ' + String(err?.message || err), 'error');
  } else {
    updateConnectionStatus({}, 'WhatsApp client error: ' + String(err?.message || err), 'warning');
  }
});

const delay = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

function normalizeNumber(number) {
  const n = String(number || '').replace(/\D/g, '');

  if (!n) return null;
  if (n.startsWith('0')) return '62' + n.slice(1);
  if (n.startsWith('8')) return '62' + n;
  if (n.startsWith('62')) return n;
  return n;
}

function maskNumber(number) {
  return policyStore?.maskNumber
    ? policyStore.maskNumber(number)
    : ('****' + String(number || '').slice(-4));
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

async function sendMessage(numbers, message, eventKey = null) {

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
      console.error(`❌ Nomor tidak valid: ${maskNumber(originalNumber)}`);

      results.push({
        number: originalNumber,
        status: 2
      });

      continue;
    }

    const chatId = `${intl}@c.us`;

    console.log(`\n📤 Memproses nomor: ${maskNumber(intl)}`);

    try {
      if (!policyStore) {
        throw new Error('Policy guard belum terinisialisasi. Pengiriman dihentikan.');
      }

      const guard = await policyStore.canSend(intl, message, eventKey);

      if (!guard.allowed) {
        console.warn(`🛡️ DITAHAN ${maskNumber(intl)}: ${guard.reason}`);

        results.push({
          number: intl,
          status: guard.status,
          reason: guard.reason
        });

        continue;
      }

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
            `📨 Attempt ${attempt}/2 -> ${maskNumber(intl)}`
          );

          // PENTING:
          // Jangan gunakan { sendSeen: false }
          await client.sendMessage(
            chatId,
            message
          );

          console.log(
            `✅ BERHASIL terkirim ke ${maskNumber(intl)}`
          );

          // Logging tidak boleh membuat pesan yang sudah terkirim
          // dicoba ulang atau dianggap gagal.
          try {
            await policyStore.recordOutbound(intl, message, 1, null, eventKey);
          } catch (logErr) {
            console.error('⚠️ Pesan sudah terkirim, tetapi log database gagal:', logErr.message);
          }

          results.push({
            number: intl,
            status: 1,
            reason: 'sent'
          });

          sent = true;

          break;

        } catch (err) {

          console.error(
            `❌ Gagal attempt ${attempt} ke ${maskNumber(intl)}:`,
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
        `❌ FINAL ERROR ${maskNumber(originalNumber)}:`,
        err.message
      );

      results.push({
        number: intl,
        status: 2,
        reason: 'send_failed'
      });

      if (policyStore) {
        try {
          await policyStore.recordOutbound(intl, message, 2, err.message, eventKey);
        } catch (logErr) {
          console.error('❌ Gagal mencatat kegagalan pengiriman:', logErr.message);
        }
      }
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
    updateConnectionStatus({
      phase: 'error',
      label: 'Gagal memulai koneksi WhatsApp. Periksa log terminal.'
    }, 'Gagal initialize WhatsApp: ' + String(err?.message || err), 'error');

    initializing = false;
  }
}

initializeClient();


module.exports = {
  sendMessage,
  client,
  setPolicyStore,
  getConnectionStatus
};