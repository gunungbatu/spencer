/**
 * GLOBAL CONFIGURATION & UNIFIED HEADER SYSTEM
 * Mengatur: API, Header, Footer, Styling, dan Booking Engine secara terpusat.
 */

var CONFIG = { API_URL: "", WA: "" };

// --- 1. CSS GLOBAL INJECTION ---
// Semua style header dan modal dipindah ke sini agar konsisten di semua halaman.
const globalCSS = `
    /* UNIFIED HEADER STYLES */
    header#navbar { 
        position: fixed; top: 0; left: 0; width: 100%; padding: 15px 50px; 
        display: flex; justify-content: space-between; align-items: center; 
        z-index: 9000; transition: 0.4s; 
        background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent); 
        box-sizing: border-box; font-family: 'Montserrat', sans-serif;
    }
    header#navbar.scrolled { background-color: #1B4D3E !important; padding: 10px 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    header#navbar .logo { color: #fff; font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; letter-spacing: 2px; font-weight: 600; text-transform: uppercase; text-decoration:none; }
    header#navbar .nav-menu { display: flex; gap: 30px; list-style: none; margin: 0; padding: 0; align-items: center; }
    header#navbar .nav-link { color: #fff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500; text-decoration:none; transition: 0.3s; }
    header#navbar .nav-link:hover { color: #D4AF37; }
    header#navbar .btn-book { background: #D4AF37; color: #fff; padding: 10px 25px; font-size: 0.75rem; text-transform: uppercase; border: 1px solid #D4AF37; cursor: pointer; text-decoration: none; font-weight: 600; }
    header#navbar .btn-book:hover { background: transparent; color: #D4AF37; }

    /* MENCEGAH HEADER MENUMPUK */
    /* Menyembunyikan header manual/lama yang mungkin tertinggal di HTML */
    header:not(#navbar), .manual-header { display: none !important; }

    /* MOBILE MENU */
    .mobile-menu-btn { display: none; font-size: 1.5rem; color: #fff; cursor: pointer; z-index: 9002; }
    .mobile-nav-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(27, 77, 62, 0.98); z-index: 9001; display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: 0.4s; }
    .mobile-nav-overlay.active { opacity: 1; pointer-events: auto; }
    .mobile-nav-link { color: #fff; font-size: 1.5rem; margin: 15px 0; text-decoration: none; font-family: 'Cormorant Garamond', serif; text-transform: uppercase; letter-spacing: 2px; }

    /* MODAL & UTILS */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .booking-form-box { background: #fff; padding: 40px; width: 95%; max-width: 500px; border-top: 5px solid #D4AF37; border-radius: 8px; position:relative; max-height:90vh; overflow-y:auto; }
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; color: #1B4D3E; }
    .form-input, .form-select { width: 100%; padding: 10px; border: 1px solid #ddd; font-size: 1rem; border-radius: 4px; box-sizing: border-box; }
    .loading { display: none; text-align: center; margin: 10px 0; color: #D4AF37; font-size: 0.9rem; font-style: italic; }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// Load FontAwesome secara otomatis
if (!document.querySelector('link[href*="font-awesome"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    document.head.appendChild(link);
}

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal();
});

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(r => r.json())
    .then(data => {
        if(data.social_whatsapp) CONFIG.WA = cleanWaNumber(data.social_whatsapp);
        CONFIG.API_URL = data.api_url || "https://script.google.com/macros/s/AKfycbz8NCQgwChpMLSYF3FjkVtXlgoe12u_-UHHNedozKrTuMmp-piWtzINkcCZeF0XuBWdXQ/exec";

        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    })
    .catch(err => console.error("Error Loading Config:", err));
}

// --- 3. GENERATORS ---
function generateGlobalHeader(activePage, data) {
    const container = document.getElementById('global-header-container');
    if (!container) return;

    const bookText = data.header_btn_text || "Book Now";
    const menus = [
        { id: 'home', name: 'Home', link: 'index.html' },
        { id: 'dining', name: 'Dining', link: 'dining.html' },
        { id: 'meeting', name: 'Meeting', link: 'meeting.html' },
        { id: 'wedding', name: 'Wedding', link: 'wedding.html' },
        { id: 'gallery', name: 'Gallery', link: 'gallery.html' }
    ];

    let navHTML = '';
    let mobHTML = '';
    menus.forEach(m => {
        // Highlight menu aktif dengan warna emas
        let activeStyle = (m.id === activePage) ? 'style="color:#D4AF37; font-weight:bold; border-bottom:1px solid #D4AF37;"' : '';
        navHTML += `<li><a href="${m.link}" class="nav-link" ${activeStyle}>${m.name}</a></li>`;
        mobHTML += `<a href="${m.link}" class="mobile-nav-link">${m.name}</a>`;
    });

    container.innerHTML = `
        <header id="navbar">
            <a href="index.html" class="logo">Spencer Green</a>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${bookText}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">&#9776;</div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            ${mobHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="color:#D4AF37; margin-top:30px; border:1px solid #D4AF37; padding:10px 30px;">${bookText}</a>
            <div style="position:absolute; top:30px; right:30px; color:#fff; font-size:2.5rem; cursor:pointer;" onclick="toggleMobileMenu()">&times;</div>
        </div>
    `;

    // Efek Scroll
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
    });
}

function generateGlobalFooter(data) {
    const el = document.getElementById('global-footer');
    if(!el) return;
    const socs = [
        {k:'social_instagram', i:'fa-instagram'}, {k:'social_tiktok', i:'fa-tiktok'},
        {k:'social_youtube', i:'fa-youtube'}, {k:'social_facebook', i:'fa-facebook-f'},
        {k:'social_whatsapp', i:'fa-whatsapp'}
    ];
    let html = '';
    socs.forEach(s => {
        if(data[s.k]) {
            let url = data[s.k];
            if(s.k === 'social_whatsapp' && !url.includes('http')) url = `https://wa.me/${cleanWaNumber(url)}`;
            html += `<a href="${url}" target="_blank" class="social-icon"><i class="fab ${s.i}"></i></a>`;
        }
    });
    el.innerHTML = `<p>&copy; ${new Date().getFullYear()} Spencer Green Hotel. All Rights Reserved.</p><div class="social-box">${html}</div>`;
}

// --- 4. BOOKING MODAL INJECTION ---
function injectBookingModal() {
    if (document.getElementById('bookingModal')) return;
    const modalHTML = `
    <div class="modal-overlay" id="bookingModal">
        <div class="booking-form-box">
            <span style="position:absolute; top:10px; right:20px; font-size:2rem; cursor:pointer; color:#999;" onclick="closeBooking()">&times;</span>
            <h2 style="font-family:'Cormorant Garamond',serif; text-align:center; margin-bottom:20px;">Check Availability</h2>
            <form id="resForm" onsubmit="return false;">
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;"><label class="form-label">Check In</label><input type="date" id="checkIn" class="form-input" required></div>
                    <div class="form-group" style="flex:1;"><label class="form-label">Check Out</label><input type="date" id="checkOut" class="form-input" required></div>
                </div>
                <button type="button" style="width:100%; padding:15px; background:#1B4D3E; color:#fff; border:none; cursor:pointer; font-weight:bold; text-transform:uppercase;" onclick="checkRooms()">Search Rooms</button>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// --- 5. UTILS ---
function toggleMobileMenu() { document.getElementById('mobileNav').classList.toggle('active'); }
function openBooking() { document.getElementById('bookingModal').classList.add('active'); }
function closeBooking() { document.getElementById('bookingModal').classList.remove('active'); }
function cleanWaNumber(str) { return str.toString().replace(/[^0-9]/g, '').replace(/^08/, '628'); }

function magicContentLoader(data) {
    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_')) {
                el.src = data[key];
            } else if (!key.startsWith('social_')) {
                el.innerText = data[key];
            }
        }
    });
}
