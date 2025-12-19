/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER
 * Header, Footer, Global Styles, Booking Modal
 */

var CONFIG = {
  API_URL: "",
  WA: ""
};

/* =========================
   GLOBAL HEADER & FOOTER CSS
========================= */
const globalCSS = `
:root {
  --emerald: #1B4D3E;
  --gold: #D4AF37;
  --dark: #1a1a1a;
}

/* ===== HEADER ===== */
header {
  position: fixed;
  top: 0;
  width: 100%;
  padding: 20px 50px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 1000;
  transition: 0.4s;
  background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
  box-sizing: border-box;
}

header.scrolled {
  background: var(--emerald);
  padding: 15px 50px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.logo {
  color: #fff;
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.8rem;
  letter-spacing: 2px;
  text-transform: uppercase;
}

.nav-menu {
  display: flex;
  gap: 30px;
  list-style: none;
}

.nav-link {
  color: #fff;
  text-decoration: none;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 1px;
}

.btn-book {
  padding: 10px 20px;
  border: 1px solid var(--gold);
  color: #fff;
  text-transform: uppercase;
  font-size: 0.75rem;
  cursor: pointer;
  background: transparent;
}

.btn-book:hover {
  background: var(--gold);
}

/* MOBILE */
.mobile-menu-btn {
  display: none;
  font-size: 1.5rem;
  color: #fff;
  cursor: pointer;
}

.mobile-nav-overlay {
  position: fixed;
  inset: 0;
  background: rgba(27,77,62,0.98);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  opacity: 0;
  pointer-events: none;
  transition: 0.4s;
  z-index: 999;
}

.mobile-nav-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.mobile-nav-link {
  color: #fff;
  font-size: 1.5rem;
  margin: 15px 0;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 2px;
}

@media (max-width: 900px) {
  header { padding: 15px 20px; }
  .nav-menu, .btn-book { display: none; }
  .mobile-menu-btn { display: block; }
}
`;

const styleTag = document.createElement("style");
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

/* =========================
   FONT AWESOME (ONCE)
========================= */
if (!document.querySelector('link[href*="font-awesome"]')) {
  const fa = document.createElement("link");
  fa.rel = "stylesheet";
  fa.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css";
  document.head.appendChild(fa);
}

/* =========================
   INIT
========================= */
document.addEventListener("DOMContentLoaded", () => {
  injectHeader();
  injectFooter();
  setupScrollHeader();
});

/* =========================
   HEADER
========================= */
function injectHeader() {
  const headerHTML = `
<header>
  <div class="logo">Spencer Green</div>

  <ul class="nav-menu">
    <li><a href="index.html" class="nav-link">Home</a></li>
    <li><a href="dining.html" class="nav-link">Dining</a></li>
    <li><a href="meeting.html" class="nav-link">Meeting</a></li>
    <li><a href="wedding.html" class="nav-link">Wedding</a></li>
    <li><a href="gallery.html" class="nav-link">Gallery</a></li>
  </ul>

  <a class="btn-book" onclick="openBooking()">Book Now</a>
  <i class="fas fa-bars mobile-menu-btn" onclick="toggleMobileNav()"></i>
</header>

<div class="mobile-nav-overlay" id="mobileNav">
  <a href="index.html" class="mobile-nav-link">Home</a>
  <a href="dining.html" class="mobile-nav-link">Dining</a>
  <a href="meeting.html" class="mobile-nav-link">Meeting</a>
  <a href="wedding.html" class="mobile-nav-link">Wedding</a>
  <a href="gallery.html" class="mobile-nav-link">Gallery</a>
</div>
`;
  document.getElementById("global-header-container").innerHTML = headerHTML;
}

function toggleMobileNav() {
  document.getElementById("mobileNav").classList.toggle("active");
}

/* =========================
   SCROLL EFFECT (SATU-SATUNYA)
========================= */
function setupScrollHeader() {
  window.addEventListener("scroll", () => {
    const header = document.querySelector("header");
    if (!header) return;
    header.classList.toggle("scrolled", window.scrollY > 50);
  });
}

/* =========================
   FOOTER
========================= */
function injectFooter() {
  const footer = document.getElementById("global-footer");
  if (!footer) return;

  footer.innerHTML = `
    <p>&copy; ${new Date().getFullYear()} Spencer Green Hotel Batu</p>
  `;
}

/* =========================
   BOOKING (stub, pakai punya kamu)
========================= */
function openBooking() {
  alert("Booking modal here");
}
