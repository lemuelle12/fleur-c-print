const express    = require('express');
const cors       = require('cors');
const rateLimit  = require('express-rate-limit');
require('dotenv').config();

const app = express();

// ── CORS ─────────────────────────────────────────────────────
const allowedOrigins = (process.env.ALLOWED_ORIGINS || 'http://localhost,http://127.0.0.1,http://localhost/fleur-c-print')
  .split(',')
  .map(o => o.trim());

app.use(cors({
  origin: function (origin, callback) {
    // Allow requests with no origin (e.g. same-server PHP fetch, curl)
    if (!origin) return callback(null, true);
    if (allowedOrigins.includes(origin)) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  }
}));

app.use(express.json({ limit: '16kb' })); // prevent oversized payloads

// ── RATE LIMITING ─────────────────────────────────────────────
// Max 5 contact form submissions per IP per 15 minutes
const contactLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: {
    success: false,
    error: 'Too many submissions from this device. Please wait 15 minutes and try again.'
  }
});

// ── CONTACT ENDPOINT ─────────────────────────────────────────
app.post('/api/contact', contactLimiter, function (req, res) {
  const name    = (req.body.name    || '').trim();
  const phone   = (req.body.phone   || '').trim();
  const service = (req.body.service || '').trim();
  const message = (req.body.message || '').trim();

  // Required field check
  if (!name || !phone || !message) {
    return res.status(400).json({
      success: false,
      error: 'Name, phone, and message are required.'
    });
  }

  // Phone: strip non-digits, must be 10–13 digits (PH format)
  const digits = phone.replace(/\D/g, '');
  if (digits.length < 10 || digits.length > 13) {
    return res.status(400).json({
      success: false,
      error: 'Please enter a valid Philippine phone number.'
    });
  }

  // Message length
  if (message.length < 10) {
    return res.status(400).json({
      success: false,
      error: 'Message is too short. Please give us more detail.'
    });
  }

  // Hard cap on field lengths to prevent abuse
  if (name.length > 100 || service.length > 100 || message.length > 2000) {
    return res.status(400).json({
      success: false,
      error: 'One or more fields exceed the maximum allowed length.'
    });
  }

  // ── Log the inquiry ───────────────────────────────────────
  // Replace this console.log with a DB insert or email send in production
  console.log('--- New Inquiry ---');
  console.log('Name:    ' + name);
  console.log('Phone:   ' + phone);
  console.log('Service: ' + service || 'Not specified');
  console.log('Message: ' + message);
  console.log('-------------------');

  res.json({
    success: true,
    message: 'Thank you! We will contact you shortly.'
  });
});

// ── HEALTH CHECK ─────────────────────────────────────────────
app.get('/api/health', (req, res) => res.json({ status: 'ok' }));
app.get('/', (req, res) => res.json({ name: 'Fleur C Print API', status: 'running' }));

// ── GLOBAL ERROR HANDLER ─────────────────────────────────────
// Catches any unhandled error thrown inside a route
app.use((err, req, res, next) => {
  console.error('[Express error]', err.message);
  res.status(500).json({
    success: false,
    error: 'A server error occurred. Please try again.'
  });
});

// ── PROCESS-LEVEL SAFETY NET ──────────────────────────────────
// Prevents silent crashes — logs the error and lets the process manager
// (PM2, Railway, etc.) restart the server automatically.
process.on('uncaughtException', (err) => {
  console.error('[uncaughtException]', err);
  // Do NOT call process.exit() here if using a process manager that restarts on crash.
  // Uncomment the line below only if you handle restarts externally:
  // process.exit(1);
});

process.on('unhandledRejection', (reason) => {
  console.error('[unhandledRejection]', reason);
});

// ── START ─────────────────────────────────────────────────────
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Fleur C Print API running on port ${PORT}`);
});