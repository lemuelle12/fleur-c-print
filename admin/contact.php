<?php
// ── contact.php ──────────────────────────────────────────────
// Public contact / quote request page.
// On submit: (1) client validation → (2) Node.js API → (3) Firebase save

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/public/header.php';
require_once __DIR__ . '/public/footer.php';

// Pull active services for the dropdown
$services = db()->query(
    "SELECT name FROM services WHERE is_active = 1 ORDER BY sort_order"
)->fetchAll(PDO::FETCH_COLUMN);

// Inject Node API URL from environment so it works on any host
$api_url = $_ENV['NODE_API_URL'] ?? 'http://localhost:3000/api/contact';

render_public_header('Contact Us', 'contact');
?>

<section class="page-hero">
  <div class="container">
    <p class="label">Get in touch</p>
    <div class="rule"></div>
    <h1 class="section-title">Contact <em>&amp; Quote</em></h1>
    <p class="section-sub">
      Send us your requirements and we'll get back to you as soon as possible — usually within the hour.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-wrap">

      <div class="contact-info">
        <p class="label">Our Details</p>
        <div class="rule"></div>
        <h2 class="section-title" style="font-size:32px;">Come <em>visit us</em></h2>
        <p style="font-size:14px;color:var(--text-3);margin-top:12px;line-height:1.75;">
          Walk-ins are always welcome. For bulk or custom orders, sending a message ahead of time helps us prepare faster.
        </p>
        <div class="contact-detail">
          <div class="contact-item">
            <div class="contact-item-label">Phone / GCash</div>
            <div class="contact-item-val"><a href="tel:+639298536706">+63 929 853 6706</a></div>
          </div>
          <div class="contact-item">
            <div class="contact-item-label">Email</div>
            <div class="contact-item-val"><a href="mailto:hello@fleurcprint.com">hello@fleurcprint.com</a></div>
          </div>
          <div class="contact-item">
            <div class="contact-item-label">Address</div>
            <div class="contact-item-val">B5L16 Birch, Urban Deca Homes<br>Marilao, Bulacan</div>
          </div>
          <div class="contact-item">
            <div class="contact-item-label">Shop Hours</div>
            <div class="contact-item-val">Monday – Saturday<br>8:00 AM – 6:00 PM</div>
          </div>
          <div class="contact-item">
            <div class="contact-item-label">Payment Methods</div>
            <div class="contact-item-val">Cash &middot; GCash</div>
          </div>
        </div>
      </div>

      <div class="contact-form-wrap">
        <div class="form-title">Send a Message</div>
        <form id="contact-form" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="cf-name">Full Name *</label>
              <input type="text" id="cf-name" name="name" class="form-control" placeholder="Maria Santos" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="cf-phone">Phone Number *</label>
              <input type="tel" id="cf-phone" name="phone" class="form-control" placeholder="09XX XXX XXXX" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="cf-service">Service Interested In</label>
            <select id="cf-service" name="service" class="form-control">
              <option value="">— Select a service —</option>
              <?php foreach ($services as $svc): ?>
                <option value="<?= e($svc) ?>"><?= e($svc) ?></option>
              <?php endforeach; ?>
              <option value="Other">Other / Not sure yet</option>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label" for="cf-message">Message / Requirements *</label>
            <textarea id="cf-message" name="message" class="form-control" rows="4"
              placeholder="Tell us what you need — quantity, size, deadline, or any special instructions."
              required></textarea>
          </div>
          <div id="form-success" class="form-msg" role="alert" aria-live="polite"></div>
          <div id="form-error"   class="form-msg" role="alert" aria-live="polite"></div>
          <button type="submit" id="form-btn" class="btn btn-accent form-submit">Send Message</button>
        </form>
      </div>

    </div>
  </div>
</section>

<script type="module">
import { initializeApp }
  from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
import { getDatabase, ref, push, serverTimestamp }
  from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-database.js';

const firebaseConfig = {
  apiKey:            "AIzaSyBJWEAAQ2mfg7FJazXTgNoZxxGYx_Wc4Wk",
  authDomain:        "fleur-c-print-34a2d.firebaseapp.com",
  databaseURL:       "https://fleur-c-print-34a2d-default-rtdb.firebaseio.com",
  projectId:         "fleur-c-print-34a2d",
  storageBucket:     "fleur-c-print-34a2d.firebasestorage.app",
  messagingSenderId: "991723369051",
  appId:             "1:991723369051:web:dcfafa7681edde8c4e857e"
};

const firebaseApp = initializeApp(firebaseConfig);
const firebaseDb  = getDatabase(firebaseApp);

// ── API URL injected server-side — works on localhost and production ──
const API_URL = <?= json_encode($api_url) ?>;

const form      = document.getElementById('contact-form');
const btnSubmit = document.getElementById('form-btn');
const elSuccess = document.getElementById('form-success');
const elError   = document.getElementById('form-error');

function showMsg(el, msg, type) {
  elSuccess.textContent = '';
  elError.textContent   = '';
  elSuccess.className   = 'form-msg';
  elError.className     = 'form-msg';
  el.textContent = msg;
  el.classList.add(type);
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setLoading(on) {
  btnSubmit.disabled    = on;
  btnSubmit.textContent = on ? 'Sending…' : 'Send Message';
}

function validate(name, phone, message) {
  if (!name.trim())                          return 'Please enter your name.';
  if (!phone.trim())                         return 'Please enter your phone number.';
  if (phone.replace(/\D/g, '').length < 10) return 'Please enter a valid Philippine phone number (at least 10 digits).';
  if (!message.trim())                       return 'Please write a short message.';
  if (message.trim().length < 10)            return 'Message is too short — please give us a bit more detail.';
  return null;
}

form.addEventListener('submit', async function (e) {
  e.preventDefault();

  const name    = document.getElementById('cf-name').value.trim();
  const phone   = document.getElementById('cf-phone').value.trim();
  const service = document.getElementById('cf-service').value;
  const message = document.getElementById('cf-message').value.trim();

  const clientError = validate(name, phone, message);
  if (clientError) { showMsg(elError, clientError, 'error'); return; }

  setLoading(true);

  try {
    // ── Step 1: Node API with 8-second timeout ────────────────
    const controller = new AbortController();
    const timeout    = setTimeout(() => controller.abort(), 8000);

    let res, data;
    try {
      res  = await fetch(API_URL, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ name, phone, service, message }),
        signal:  controller.signal,
      });
      data = await res.json();
    } catch (fetchErr) {
      if (fetchErr.name === 'AbortError') {
        showMsg(elError, 'Request timed out. Please try calling us directly at +63 929 853 6706.', 'error');
        setLoading(false);
        return;
      }
      // Node API is unreachable — skip to Firebase-only save
      console.warn('Node API unreachable, saving directly to Firebase:', fetchErr.message);
      data = { success: true };
      res  = { ok: true };
    } finally {
      clearTimeout(timeout);
    }

    if (!res.ok || !data.success) {
      showMsg(elError, data.error || 'Something went wrong. Please try again.', 'error');
      setLoading(false);
      return;
    }

    // ── Step 2: Firebase save — isolated so it never blocks success ──
    try {
      await push(ref(firebaseDb, 'inquiries'), {
        name:      name,
        phone:     phone,
        service:   service || 'Not specified',
        message:   message,
        status:    'new',
        createdAt: serverTimestamp(),
      });
    } catch (fbErr) {
      // Log but don't fail the user — the API already logged the inquiry
      console.error('Firebase save failed (inquiry was still received):', fbErr);
    }

    // ── Step 3: Success ───────────────────────────────────────
    showMsg(
      elSuccess,
      'Thank you, ' + name + '! We received your message and will contact you shortly.',
      'success'
    );
    form.reset();

  } catch (err) {
    console.error('Submission error:', err);
    showMsg(
      elError,
      'Could not send your message right now. Please call or text us directly at +63 929 853 6706.',
      'error'
    );
  } finally {
    setLoading(false);
  }
});
</script>

<?php render_public_footer(); ?>
