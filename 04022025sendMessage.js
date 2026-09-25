const venom = require('venom-bot');

let client;

venom
  .create({
    session: 'session',
    multidevice: true,
    headless: 'new',
    browserArgs: ['--no-sandbox', '--disable-setuid-sandbox']
  })
  .then((createdClient) => {
    client = createdClient;
    console.log('Venom client created');
  })
  .catch((error) => console.log(error));

const delay = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

const sendMessage = (numbers, message) => {
  return new Promise(async (resolve, reject) => {
    if (!client) {
      console.log('Client is not initialized');
      return reject('Client is not initialized');
    }

    const convertNumberToInternational = (number) => {
      if (number.startsWith('0')) {
        return '62' + number.slice(1);
      }
      return number;
    };

    const sendMessageToNumber = async (number, message) => {
      try {
        const internationalNumber = convertNumberToInternational(number);
        const chatId = `${internationalNumber}@c.us`;
        await client.sendText(chatId, message);
        console.log(`Pesan berhasil dikirim ke ${internationalNumber}`);
        // Tunggu selama 7 detik sebelum melanjutkan ke nomor berikutnya
        await delay(7000);
        return `Pesan berhasil dikirim ke ${internationalNumber}`;
      } catch (error) {
        console.log(`Pesan gagal dikirim ke ${internationalNumber}`);
        throw new Error(`Gagal mengirim pesan ke ${internationalNumber}: ${error}`);
      }
    };

    const numbersArray = numbers.split(',').map(num => num.trim());
    const results = [];

    for (const number of numbersArray) {
      try {
        const result = await sendMessageToNumber(number, message);
        results.push(result);
      } catch (error) {
        results.push(error.message);
      }
    }

    resolve(results.join('\n'));
  });
};

module.exports = { sendMessage };