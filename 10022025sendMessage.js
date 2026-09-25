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

const delay = (min, max) => {
  const ms = Math.floor(Math.random() * (max - min + 1) + min);
  return new Promise(resolve => setTimeout(resolve, ms));
};

const getRandomMessage = () => {
  const variations = [
    "Mohon maaf, kami tidak dapat membalas pesan Anda karena bot.",
    "Hai! Saat ini bot tidak dapat membalas pesan Anda. Mohon maaf atas ketidaknyamanannya.",
    "Mohon maaf, pesan ini dikirim oleh bot dan tidak dapat membalas secara langsung.",
    "Halo! Kami tidak dapat membalas pesan Anda saat ini karena sistem otomatis (bot).",
    "Pesan otomatis: Mohon maaf, kami tidak bisa membalas pesan Anda karena bot." 
  ];
  return variations[Math.floor(Math.random() * variations.length)];
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
        const randomMessage = getRandomMessage();
        const finalMessage = `${message}\n\n${randomMessage}`;
        await client.sendText(chatId, finalMessage);
        console.log(`Pesan berhasil dikirim ke ${internationalNumber}`);
        
        // Random delay antara 10 - 17 detik
        await delay(10000, 17000);
        
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