// C:\xampp\htdocs\GenRev\mailer-service\server.js
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const nodemailer = require('nodemailer');

const app = express();
app.use(cors());
app.use(express.json());

const transporter = nodemailer.createTransport({
  host: process.env.MAIL_HOST,
  port: Number(process.env.MAIL_PORT || 587),
  secure: false,
  auth: {
    user: process.env.MAIL_USERNAME,
    pass: process.env.MAIL_PASSWORD,
  },
});

app.post('/send-otp', async (req, res) => {
  try {
    const { to, otp } = req.body;

    await transporter.sendMail({
      from: `"GenRev System" <${process.env.MAIL_FROM_ADDRESS}>`,
      to,
      subject: 'GenRev Registration OTP',
      text: `Your GenRev OTP is: ${otp}`,
    });

    console.log('OTP sent to', to);
    res.json({ ok: true });
  } catch (err) {
    console.error('Error sending OTP:', err);
    res.status(500).json({ ok: false, error: 'EMAIL_FAILED' });
  }
});

const PORT = process.env.MAIL_PORT_NODE || 4000;
app.listen(PORT, () => {
  console.log(`Mailer service running on http://localhost:${PORT}`);
});
