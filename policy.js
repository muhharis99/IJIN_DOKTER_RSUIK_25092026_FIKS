const crypto = require('crypto');

const OPT_IN_WORDS = new Set([
  'daftar',
  'optin',
  'opt-in',
  'subscribe',
  'berlangganan'
]);

const OPT_OUT_WORDS = new Set([
  'stop',
  'berhenti',
  'unsubscribe',
  'optout',
  'opt-out',
  'jangan kirim',
  'jangan kirim lagi'
]);

function normalizeNumber(number) {
  const n = String(number || '').replace(/\D/g, '');
  if (!n) return null;
  if (n.startsWith('0')) return '62' + n.slice(1);
  if (n.startsWith('8')) return '62' + n;
  if (n.startsWith('62')) return n;
  return n;
}

function maskNumber(number) {
  const n = normalizeNumber(number) || String(number || '');
  return n.length > 4 ? '****' + n.slice(-4) : '****';
}

function cleanBody(text) {
  return String(text || '').trim().toLowerCase().replace(/\s+/g, ' ');
}

function query(pool, sql, params = []) {
  return new Promise((resolve, reject) => {
    pool.query(sql, params, (err, rows) => {
      if (err) return reject(err);
      resolve(rows);
    });
  });
}

function createPolicyStore(pool) {
  const MAX_DAILY_PER_NUMBER = Number(process.env.WA_MAX_DAILY_PER_NUMBER || 10);
  const DUPLICATE_WINDOW_MINUTES = Number(process.env.WA_DUPLICATE_WINDOW_MINUTES || 360);
  const MAX_BATCH_SIZE = Number(process.env.WA_MAX_BATCH_SIZE || 500);

  async function ensureSchema() {
    await query(pool, `
      CREATE TABLE IF NOT EXISTS wa_contact_consent (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        no_hp VARCHAR(32) NOT NULL,
        service_opt_in TINYINT(1) NOT NULL DEFAULT 0,
        service_opt_in_at DATETIME NULL,
        service_opt_in_source VARCHAR(120) NULL,
        service_opt_out_at DATETIME NULL,
        last_inbound_at DATETIME NULL,
        last_inbound_text TEXT NULL,
        last_outbound_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_wa_contact_consent_no_hp (no_hp),
        KEY idx_wa_contact_consent_optin (service_opt_in),
        KEY idx_wa_contact_consent_inbound (last_inbound_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    await query(pool, `
      CREATE TABLE IF NOT EXISTS wa_send_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        no_hp VARCHAR(32) NOT NULL,
        message_hash CHAR(64) NOT NULL,
        status TINYINT NOT NULL,
        reason VARCHAR(160) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wa_send_log_phone_time (no_hp, created_at),
        KEY idx_wa_send_log_hash (no_hp, message_hash, created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  async function getConsent(number) {
    const noHp = normalizeNumber(number);
    if (!noHp) return null;

    const rows = await query(
      pool,
      `SELECT *
         FROM wa_contact_consent
        WHERE no_hp = ?
        LIMIT 1`,
      [noHp]
    );

    return rows[0] || null;
  }

  async function upsertInbound(number, body) {
    const noHp = normalizeNumber(number);
    if (!noHp) return;

    await query(pool, `
      INSERT INTO wa_contact_consent
        (no_hp, last_inbound_at, last_inbound_text)
      VALUES (?, NOW(), ?)
      ON DUPLICATE KEY UPDATE
        last_inbound_at = VALUES(last_inbound_at),
        last_inbound_text = VALUES(last_inbound_text)
    `, [noHp, String(body || '').slice(0, 2000)]);
  }

  async function setOptIn(number, source = 'whatsapp_keyword') {
    const noHp = normalizeNumber(number);
    if (!noHp) return;

    await query(pool, `
      INSERT INTO wa_contact_consent
        (no_hp, service_opt_in, service_opt_in_at, service_opt_in_source, service_opt_out_at)
      VALUES (?, 1, NOW(), ?, NULL)
      ON DUPLICATE KEY UPDATE
        service_opt_in = 1,
        service_opt_in_at = NOW(),
        service_opt_in_source = VALUES(service_opt_in_source),
        service_opt_out_at = NULL
    `, [noHp, source]);
  }

  async function setOptOut(number, source = 'whatsapp_keyword') {
    const noHp = normalizeNumber(number);
    if (!noHp) return;

    await query(pool, `
      INSERT INTO wa_contact_consent
        (no_hp, service_opt_in, service_opt_out_at, service_opt_in_source)
      VALUES (?, 0, NOW(), ?)
      ON DUPLICATE KEY UPDATE
        service_opt_in = 0,
        service_opt_out_at = NOW()
    `, [noHp, source]);
  }

  async function recordInbound(message, client) {
    if (!message || !message.from || !String(message.from).endsWith('@c.us')) return;

    const noHp = normalizeNumber(String(message.from).split('@')[0]);
    if (!noHp) return;

    const body = cleanBody(message.body);
    await upsertInbound(noHp, message.body);

    if (OPT_OUT_WORDS.has(body)) {
      await setOptOut(noHp, 'whatsapp_keyword:' + body);
      try {
        await client.sendMessage(
          message.from,
          'Permintaan berhenti diterima. Notifikasi WhatsApp dari RSU Islam Klaten telah dinonaktifkan. Untuk menerima kembali, kirim pesan: DAFTAR.'
        );
      } catch (err) {
        console.warn('⚠️ Gagal mengirim konfirmasi opt-out ke', maskNumber(noHp), err.message);
      }
      console.log('🛑 OPT-OUT:', maskNumber(noHp));
      return;
    }

    if (OPT_IN_WORDS.has(body)) {
      await setOptIn(noHp, 'whatsapp_keyword:' + body);
      try {
        await client.sendMessage(
          message.from,
          'Permintaan diterima. Anda telah menyetujui menerima notifikasi layanan dari RSU Islam Klaten melalui WhatsApp. Untuk berhenti kapan saja, balas STOP.'
        );
      } catch (err) {
        console.warn('⚠️ Gagal mengirim konfirmasi opt-in ke', maskNumber(noHp), err.message);
      }
      console.log('✅ OPT-IN:', maskNumber(noHp));
    }
  }

  async function canSend(number, message) {
    const noHp = normalizeNumber(number);
    if (!noHp) {
      return { allowed: false, status: 3, reason: 'invalid_number' };
    }

    const consent = await getConsent(noHp);

    if (!consent || Number(consent.service_opt_in) !== 1) {
      return { allowed: false, status: 3, reason: 'no_explicit_opt_in' };
    }

    if (consent.service_opt_out_at) {
      return { allowed: false, status: 4, reason: 'opted_out' };
    }

    const dailyRows = await query(
      pool,
      `SELECT COUNT(*) AS total
         FROM wa_send_log
        WHERE no_hp = ?
          AND status = 1
          AND created_at >= CURDATE()
          AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)`,
      [noHp]
    );

    const dailyTotal = Number(dailyRows[0]?.total || 0);
    if (dailyTotal >= MAX_DAILY_PER_NUMBER) {
      return { allowed: false, status: 3, reason: 'daily_recipient_limit' };
    }

    const hash = crypto
      .createHash('sha256')
      .update(String(message || ''), 'utf8')
      .digest('hex');

    const duplicateRows = await query(
      pool,
      `SELECT id
         FROM wa_send_log
        WHERE no_hp = ?
          AND message_hash = ?
          AND status = 1
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        LIMIT 1`,
      [noHp, hash, DUPLICATE_WINDOW_MINUTES]
    );

    if (duplicateRows.length > 0) {
      return { allowed: false, status: 5, reason: 'duplicate_message_window' };
    }

    return {
      allowed: true,
      status: 0,
      reason: null,
      messageHash: hash
    };
  }

  async function recordOutbound(number, message, status, reason = null) {
    const noHp = normalizeNumber(number);
    if (!noHp) return;

    const hash = crypto
      .createHash('sha256')
      .update(String(message || ''), 'utf8')
      .digest('hex');

    await query(
      pool,
      `INSERT INTO wa_send_log
        (no_hp, message_hash, status, reason)
       VALUES (?, ?, ?, ?)`,
      [noHp, hash, Number(status), reason]
    );

    if (Number(status) === 1) {
      await query(
        pool,
        `INSERT INTO wa_contact_consent (no_hp, last_outbound_at)
         VALUES (?, NOW())
         ON DUPLICATE KEY UPDATE last_outbound_at = NOW()`,
        [noHp]
      );
    }
  }

  function validateBatchSize(numbers) {
    const list = String(numbers || '')
      .split(',')
      .map(n => n.trim())
      .filter(Boolean);

    return {
      list,
      allowed: list.length <= MAX_BATCH_SIZE
    };
  }

  return {
    ensureSchema,
    getConsent,
    setOptIn,
    setOptOut,
    recordInbound,
    canSend,
    recordOutbound,
    validateBatchSize,
    maskNumber,
    normalizeNumber
  };
}

module.exports = { createPolicyStore, normalizeNumber };
