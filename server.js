const mysql = require('mysql');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const { sendMessage } = require('./sendMessage');

const app = express();
const port = 3000;

app.use(cors({ origin: '*' }));

app.use(
  bodyParser.json({
    limit: '5mb'
  })
);

const db = mysql.createPool({
  host: '192.168.0.33',
  user: 'admin',
  password: 'admin3dp',
  database: 'rsiklaten',
  connectionLimit: 10
});

app.post('/send', async (req, res) => {

  const {
    numbers,
    message
  } = req.body;


  if (!numbers || !message) {

    return res.status(400).json({
      success: false,
      message: 'numbers dan message wajib diisi'
    });

  }


  try {

    console.log('\n========================================');
    console.log('📨 REQUEST /send');
    console.log('========================================');
    console.log('Numbers :', numbers);
    console.log('Message :', message.substring(0, 100));
    console.log('========================================\n');


    const results = await sendMessage(
      numbers,
      message
    );

    const jobs = results.map((r) => {

      return new Promise((resolve, reject) => {

        const local =
          r.number.startsWith('62')
            ? '0' + r.number.slice(2)
            : r.number;


        db.query(
          `
          UPDATE batal_praktek_detil_wa
          SET status = ?
          WHERE no_hp = ?
          `,
          [
            r.status,
            local
          ],
          (err) => {

            if (err) {
              return reject(err);
            }

            resolve();
          }
        );

      });

    });


    await Promise.all(jobs);

    return res.json({
      success: true,
      data: results
    });


  } catch (err) {

    console.error(
      '❌ /send error:',
      err.message
    );


    return res.status(500).json({
      success: false,
      message: err.message
    });

  }

});

app.listen(
  port,
  '0.0.0.0',
  () => {

    console.log('='.repeat(60));
    console.log('🚀 WhatsApp Gateway Server');
    console.log(`📡 http://192.168.0.93:${port}`);
    console.log('='.repeat(60));

  }
);