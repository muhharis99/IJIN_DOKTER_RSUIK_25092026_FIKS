const mysql = require('mysql');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const {
  sendMessage,
  setPolicyStore
} = require('./sendMessage');

const {
  createPolicyStore
} = require('./policy');

const app = express();
const port = 3000;

app.use(cors({ origin: '*' }));

app.use(
  bodyParser.json({
    limit: '5mb'
  })
);

const db = mysql.createPool({
  host: process.env.WA_DB_HOST || '192.168.0.33',
  port: Number(process.env.WA_DB_PORT || 3306),
  user: process.env.WA_DB_USER || 'admin',
  password: process.env.WA_DB_PASSWORD || 'admin3dp',
  database: process.env.WA_DB_NAME || 'rsiklaten',
  connectionLimit: Number(process.env.WA_DB_POOL_SIZE || 10),
  waitForConnections: true,
  queueLimit: 0
});

const policy = createPolicyStore(db);
setPolicyStore(policy);

function splitNumbers(value) {
  return [...new Set(
    String(value || '')
      .split(',')
      .map(v => v.trim())
      .filter(Boolean)
  )];
}

function localNumber(number) {
  const n = String(number || '');
  return n.startsWith('62') ? '0' + n.slice(2) : n;
}

function updateStatus(number, status) {
  return new Promise((resolve, reject) => {
    db.query(
      `UPDATE batal_praktek_detil_wa
          SET status = ?
        WHERE no_hp = ?`,
      [status, localNumber(number)],
      (err, result) => {
        if (err) return reject(err);
        resolve(result);
      }
    );
  });
}

app.get('/health', async (req, res) => {
  try {
    await policy.ensureSchema();

    db.query('SELECT 1 AS ok', (err, rows) => {
      if (err) {
        return res.status(503).json({
          success: false,
          database: 'down',
          policyGuard: 'enabled',
          message: err.message
        });
      }

      return res.json({
        success: true,
        database: rows?.[0]?.ok === 1 ? 'up' : 'unknown',
        policyGuard: 'enabled'
      });
    });
  } catch (err) {
    return res.status(503).json({
      success: false,
      policyGuard: 'error',
      message: err.message
    });
  }
});

app.post('/send', async (req, res) => {
  const numbers = String(req.body?.numbers || '').trim();
  const message = String(req.body?.message || '').trim();

  if (!numbers || !message) {
    return res.status(400).json({
      success: false,
      message: 'numbers dan message wajib diisi'
    });
  }

  const batchCheck = policy.validateBatchSize(numbers);

  if (!batchCheck.allowed) {
    return res.status(413).json({
      success: false,
      message: `Batch terlalu besar. Maksimal ${process.env.WA_MAX_BATCH_SIZE || 500} nomor per request.`
    });
  }

  const numberList = splitNumbers(numbers);

  if (!numberList.length) {
    return res.status(400).json({
      success: false,
      message: 'Tidak ada nomor penerima yang valid.'
    });
  }

  try {
    await policy.ensureSchema();

    console.log('\n========================================');
    console.log('📨 REQUEST /send');
    console.log('========================================');
    console.log('Recipients:', numberList.length);
    console.log('Message length:', message.length);
    console.log('Policy guard: ENABLED');
    console.log('========================================\n');

    const results = await sendMessage(
      numberList.join(','),
      message
    );

    await Promise.all(
      results.map(r => updateStatus(r.number, r.status))
    );

    const summary = {
      total: results.length,
      sent: results.filter(r => r.status === 1).length,
      failed: results.filter(r => r.status === 2).length,
      blocked: results.filter(r => r.status === 3).length,
      optedOut: results.filter(r => r.status === 4).length,
      duplicate: results.filter(r => r.status === 5).length
    };

    return res.json({
      success: true,
      message: summary.sent > 0
        ? 'Proses selesai.'
        : 'Tidak ada pesan yang dikirim karena guardrail atau koneksi.',
      summary,
      data: results
    });
  } catch (err) {
    console.error('❌ /send error:', err.message);

    return res.status(500).json({
      success: false,
      message: err.message
    });
  }
});

(async () => {
  try {
    await policy.ensureSchema();

    app.listen(
      port,
      '0.0.0.0',
      () => {
        console.log('='.repeat(60));
        console.log('🚀 WhatsApp Gateway Server');
        console.log(`📡 http://192.168.0.93:${port}`);
        console.log('🛡️ Policy guard: ENABLED');
        console.log('='.repeat(60));
      }
    );
  } catch (err) {
    console.error('❌ Gateway gagal start:', err.message);
    process.exitCode = 1;
  }
})();
