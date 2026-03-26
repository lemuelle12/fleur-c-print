<?php
// ── contact.php ──────────────────────────────────────────────
// Public contact / quote request page.
// Form submits directly to Firebase Realtime Database (no Node.js required)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/public/header.php';
require_once __DIR__ . '/public/footer.php';

// Pull active services for the dropdown
$services = db()->query(
    "SELECT name FROM services WHERE is_active = 1 ORDER BY sort_order"
)->fetchAll(PDO::FETCH_COLUMN);

render_public_header('Contact Us', 'contact');
?>

<!-- Page hero -->
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

<!-- Contact section -->
<section class="section">
  <div class="container">
    <div class="contact-wrap">

      <!-- Left: shop info -->
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
            <div class="contact-item-val">
              <a href="tel:+639298536706">+63 929 853 6706</a>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-label">Email</div>
            <div class="contact-item-val">
              <a href="mailto:hello@fleurcprint.com">hello@fleurcprint.com</a>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-label">Address</div>
            <div class="contact-item-val">
              B5L16 Birch, Urban Deca Homes<br>
              Marilao, Bulacan
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-label">Shop Hours</div>
            <div class="contact-item-val">
              Monday – Saturday<br>
              8:00 AM – 6:00 PM
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-item-label">Payment Methods</div>
            <div class="contact-item-val">Cash &middot; GCash</div>
          </div>

        </div><!-- /.contact-detail -->
      </div><!-- /.contact-info -->

      <!-- Right: form -->
      <div class="contact-form-wrap">
        <div class="form-title">Send a Message</div>

        <form id="contact-form" novalidate>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="cf-name">Full Name *</label>
              <input
                type="text"
                id="cf-name"
                name="name"
                class="form-control"
                placeholder="Juan Dela Cruz"
                required
              >
            </div>
            <div class="form-group">
              <label class="form-label" for="cf-phone">Phone Number *</label>
              <input
                type="tel"
                id="cf-phone"
                name="phone"
                class="form-control"
                placeholder="09XX XXX XXXX"
                required
              >
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
            <textarea
              id="cf-message"
              name="message"
              class="form-control"
              rows="4"
              placeholder="Tell us what you need — quantity, size, deadline, or any special instructions."
              required
            ></textarea>
          </div>

          <!-- Status messages -->
          <div id="form-success" class="form-msg success" role="alert" aria-live="polite" style="display:none;"></div>
          <div id="form-error"   class="form-msg error"   role="alert" aria-live="polite" style="display:none;"></div>

          <button type="submit" id="form-btn" class="btn btn-accent form-submit">
            Send Message
          </button>

        </form>
      </div><!-- /.contact-form-wrap -->

    </div><!-- /.contact-wrap -->
  </div><!-- /.container -->
</section>

<!-- ── FIREBASE + FORM SCRIPT ─────────────────────────────── -->
<script type="module">
// Firebase SDK loaded directly from Google CDN
import { initializeApp }
  from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
import { getDatabase, ref, push, serverTimestamp }
  from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-database.js';

// ── FIREBASE CONFIG ──────────────────────────────────────
const firebaseConfig = {
  apiKey: "AIzaSyBJWEAAQ2mfg7FJazXTgNoZxxGYx_Wc4Wk",
  authDomain: "fleur-c-print-34a2d.firebaseapp.com",
  databaseURL: "https://fleur-c-print-34a2d-default-rtdb.firebaseio.com", 
  projectId: "fleur-c-print-34a2d",
  storageBucket: "fleur-c-print-34a2d.firebasestorage.app",
  messagingSenderId: "991723369051",
  appId: "1:991723369051:web:dcfafa7681edde8c4e857e"
};

// ── INIT FIREBASE ─────────────────────────────────────────────
let firebaseApp;
let firebaseDb;

try {
  firebaseApp = initializeApp(firebaseConfig);
  firebaseDb  = getDatabase(firebaseApp);
  console.log('Firebase initialized successfully');
} catch (err) {
  console.error('Firebase init failed:', err);
}

// ── DOM ELEMENTS ──────────────────────────────────────────────
const form      = document.getElementById('contact-form');
const btnSubmit = document.getElementById('form-btn');
const elSuccess = document.getElementById('form-success');
const elError   = document.getElementById('form-error');

// ── HELPERS ───────────────────────────────────────────────────
function showMsg(el, msg, type) {
  // Hide both first
  elSuccess.style.display = 'none';
  elError.style.display   = 'none';
  elSuccess.className = 'form-msg';
  elError.className   = 'form-msg';
  
  // Show the requested one
  el.textContent = msg;
  el.classList.add(type);
  el.style.display = 'block';
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setLoading(on) {
  btnSubmit.disabled    = on;
  btnSubmit.textContent = on ? 'Sending…' : 'Send Message';
}

// ── CLIENT-SIDE VALIDATION ────────────────────────────────────
function validate(name, phone, message) {
  if (!name.trim())
    return 'Please enter your name.';
  if (!phone.trim())
    return 'Please enter your phone number.';
  const digits = phone.replace(/\D/g, '');
  if (digits.length < 10 || digits.length > 13)
    return 'Please enter a valid Philippine phone number (10-13 digits).';
  if (!message.trim())
    return 'Please write a short message.';
  if (message.trim().length < 10)
    return 'Message is too short. Please give us a bit more detail.';
  return null;
}

// ── FORM SUBMIT ───────────────────────────────────────────────
form.addEventListener('submit', async function (e) {
  e.preventDefault();

  const name    = document.getElementById('cf-name').value.trim();
  const phone   = document.getElementById('cf-phone').value.trim();
  const service = document.getElementById('cf-service').value;
  const message = document.getElementById('cf-message').value.trim();

  // Step 1 — client-side validation
  const clientError = validate(name, phone, message);
  if (clientError) {
    showMsg(elError, clientError, 'error');
    return;
  }

  // Check Firebase is ready
  if (!firebaseDb) {
    showMsg(elError, 'Unable to connect to database. Please try again or call us directly at +63 929 853 6706.', 'error');
    return;
  }

  setLoading(true);

  try {
    // Save directly to Firebase (no Node.js API)
    const inquiryRef = ref(firebaseDb, 'inquiries');
    await push(inquiryRef, {
      name:      name,
      phone:     phone,
      service:   service || 'Not specified',
      message:   message,
      status:    'new',
      createdAt: serverTimestamp(),
      userAgent: navigator.userAgent,
      submittedFrom: window.location.href
    });

    console.log('Inquiry saved to Firebase successfully');

    // Success feedback
    showMsg(
      elSuccess,
      'Thank you, ' + name + '! We received your message and will contact you shortly.',
      'success'
    );
    form.reset();

  } catch (err) {
    console.error('Firebase save error:', err);
    showMsg(
      elError,
      'Could not send your message right now. Please call or text us directly at +63 929 853 6706. (Error: ' + err.message + ')',
      'error'
    );
  } finally {
    setLoading(false);
  }
});
</script>
<script>
// Debug: Test Firebase connection
setTimeout(() => {
  console.log('Firebase App:', firebaseApp);
  console.log('Firebase DB:', firebaseDb);
  console.log('Database URL:', firebaseConfig.databaseURL);
}, 2000);
</script>
<?php render_public_footer(); ?>