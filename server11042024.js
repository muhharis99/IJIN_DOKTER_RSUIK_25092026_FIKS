const mysql = require('mysql');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const { sendMessage } = require('./sendMessage');

const app = express();
const port = 3000;

app.use(cors({
  origin: '*'
}));

app.use(bodyParser.json());

let db;

/*const db = mysql.createConnection({
  host: '103.181.182.132',
  port : '9969',
  user: 'admin',
  password: '@admin3dp',
  database: 'humas'
});*/

function handleDisconnect() {
  db = mysql.createConnection({
    host: '192.168.0.33',
    user: 'admin',
    password: 'admin3dp',
    database: 'rsiklaten'
  });

  db.connect((err) => {
    if (err) {
      console.log('Error connecting to database:', err);
      setTimeout(handleDisconnect, 2000);
    } else {
      console.log('Database connected!');
    }
  });

  db.on('error', (err) => {
    console.log('Database error:', err);
    if (err.code === 'PROTOCOL_CONNECTION_LOST') {
      handleDisconnect();
    } else {
      throw err;
    }
  });
}

handleDisconnect();

app.post('/send', async (req, res) => {
  const { numbers, message } = req.body;
  try {
    const result = await sendMessage(numbers, message);
    
    const numbersArray = numbers.split(',').map(num => num.trim());
    const updatePromises = numbersArray.map(number => {
      return new Promise((resolve, reject) => {
        const updateQuery = "UPDATE batal_praktek_detil_wa SET status = 1";
        db.query(updateQuery, [number], (err, result) => {
          if (err) {
            return reject(err);
          }
          resolve(result);
        });
      });
    });

    await Promise.all(updatePromises);

    res.json({ success: true, message: result });
  } catch (error) {
    res.status(500).json({ success: false, message: `Gagal mengirim pesan: ${error}` });
  }
});

app.listen(port, '0.0.0.0', () => {
  console.log(`Server berjalan di http://192.168.0.93:${port}`);
});
