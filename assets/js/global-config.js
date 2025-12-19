/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - FIXED
 */

var CONFIG = { API_URL: "", WA: "" };

// --- 1. CSS GLOBAL INJECTION (Modal, Tombol, Responsive Fixes) ---
const globalCSS = `
    /* TOMBOL & MODAL BOOKING */
    .btn-check { width: 100%; background-color: #1B4D3E !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; margin-top: 25px; margin-bottom: 20px; letter-spacing: 1px; border-radius: 4px; display: block; font-family: 'Montserrat', sans-serif; }
    .btn-submit { width: 100%; background-color: #D4AF37 !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; border-radius: 4px; margin-top: 10px; font-family: 'Montserrat', sans-serif; }
    .btn-submit:disabled { background-color: #ccc !important; cursor: not-allowed; }
    
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-top: 5px solid #D4AF37; max-height: 90vh; overflow-y: auto; border-radius: 8px; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }
    
    /* FIX: HEADER RESPONSIVE & MOBILE MENU */
    @media (max-width: 900px) {
        header { padding: 15px 20px !important; width: 100% !important; box-sizing: border-box !important; }
        .nav-menu, .btn-book { display: none !important; }
        .mobile-menu-btn { display: block !important; color: #fff; font-size: 1.8rem; cursor: pointer; z-index: 1002; }
    }

    /* FIX: VIDEO ROOM TOUR FULLSCREEN MOBILE */
    .video-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 10000; justify-content: center; align-items: center; }
    .video-modal-overlay.active { display: flex; }
    .video-wrapper { width: 90%; max-width: 1000px; aspect-ratio: 16/9; position: relative; background: #000; }
    .close-video { position: absolute; top: -45px; right: 0; color: #fff; font-size: 2.5rem; cursor: pointer; }
    .video-wrapper video { width: 100%; height: 100%; object-fit: cover; }

    @media (max-width: 600px) {
        .video-wrapper { width: 100%; height: 100%; aspect-ratio: auto; }
        .video-wrapper video { object-fit: contain; }
        .close-video { top: 20px; right: 20px; font-size: 3rem; z-index: 10001; }
    }

    #roomResultArea { display: none; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; animation: fadeUp 0.5s; }
    .loading { display: none; text-align: center; margin: 10px 0; color: #C5A059; font-size: 0.9rem; font-style: italic; }
    @keyframes fadeUp { from {opacity:0; transform:translateY(30px);} to {opacity:1; transform:translateY(0);} }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal();
    setupScrollEffect(); // Menangani header ganti warna saat scroll
});

// Menangani efek header saat scroll (Scrolled Class)
function setupScrollEffect() {
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if(nav) nav.classList.toggle('scrolled', window.scrollY > 50);
    });
}

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        if(data.social_whatsapp) CONFIG.WA = data.social_whatsapp;
        CONFIG.API_URL = "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec";

        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    })
    .catch(err => console.error("Error:", err));
}

// --- 3. HEADER GENERATOR (Hamburger Menu Fix) ---
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
        let activeStyle = (m.id === activePage) ? 'style="color:var(--gold); border-bottom:1px solid var(--gold);"' : '';
        navHTML += `<li><a href="${m.link}" class="nav-link" ${activeStyle}>${m.name}</a></li>`;
        mobHTML += `<a href="${m.link}" class="mobile-nav-link">${m.name}</a>`;
    });

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${bookText}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            <div style="position:absolute; top:30px; right:30px; color:#fff; font-size:2.5rem; cursor:pointer;" onclick="toggleMobileMenu()">&times;</div>
            ${mobHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="border:1px solid var(--gold); padding:10px 30px; margin-top:30px; color:var(--gold);">${bookText}</a>
        </div>
    `;
}

// --- 4. VIDEO & MODAL FUNCTIONS (Fullscreen Mobile Fix) ---
window.openTour = function(src) {
    const modal = document.getElementById('tourModal');
    const vid = document.getElementById('tourVideo');
    if(!src || !vid) return alert("Video belum tersedia.");
    
    vid.src = src;
    modal.classList.add('active');
    vid.currentTime = 0;
    vid.play().catch(err => console.log("Autoplay blocked"));
}

window.closeTour = function() {
    const modal = document.getElementById('tourModal');
    const vid = document.getElementById('tourVideo');
    if(vid) { vid.pause(); vid.src = ""; }
    modal.classList.remove('active');
}

window.toggleMobileMenu = function() {
    document.getElementById('mobileNav').classList.toggle('active');
}

// --- FUNGSI LAINNYA (Booking, Footer, Loader) TETAP SAMA ---
function injectBookingModal() {
    if (document.getElementById('bookingModal')) return;
    const modalHTML = `<div class="modal-overlay" id="bookingModal">...</div>`; // (Isi modal booking Anda)
    // ... (Logika booking modal tetap seperti kode Anda sebelumnya)
}

function generateGlobalFooter(data) { /* ... */ }
function magicContentLoader(data) {
    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_') || key.endsWith('_img')) { el.src = data[key]; }
            else if (!key.startsWith('social_')) { el.innerText = data[key]; }
        }
    });
    // Set Video Tour jika ada tombolnya
    if(data.room_deluxe_video) document.getElementById('btn_tour_deluxe').onclick = () => openTour(data.room_deluxe_video);
    if(data.room_superior_video) document.getElementById('btn_tour_superior').onclick = () => openTour(data.room_superior_video);
    if(data.room_executive_video) document.getElementById('btn_tour_executive').onclick = () => openTour(data.room_executive_video);
}

// Tambahkan sisa fungsi booking Anda di sini (checkRooms, submitBooking, dll)
