/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - FINAL MOBILE FIX
 */

var CONFIG = { 
    API_URL: "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec", 
    WA: "6281130700206" 
};

// --- 1. CSS GLOBAL INJECTION ---
const globalCSS = `
    /* TOMBOL & MODAL BOOKING */
    .btn-check { width: 100%; background-color: #1B4D3E !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; margin-top: 25px; border-radius: 4px; display: block; }
    .btn-submit { width: 100%; background-color: #D4AF37 !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; border-radius: 4px; }
    .btn-submit:disabled { background-color: #ccc !important; }
    
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .booking-form-box { background: #fff; padding: 30px; width: 95%; max-width: 500px; position: relative; border-top: 5px solid #D4AF37; max-height: 90vh; overflow-y: auto; border-radius: 8px; }
    
    /* VIDEO MODAL FULLSCREEN FIX */
    .video-modal-overlay { 
        display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
        background: #000; z-index: 2147483647 !important; justify-content: center; align-items: center; 
    }
    .video-modal-overlay.active { display: flex; }
    .video-wrapper { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; position: relative; }
    .video-wrapper video { max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; }
    .close-video { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 3.5rem; cursor: pointer; z-index: 10; text-shadow: 0 0 10px rgba(0,0,0,0.8); }

    /* MOBILE HEADER & CONTENT OVERRIDE */
    @media (max-width: 900px) {
        header#navbar { 
            padding: 10px 15px !important; 
            width: 100% !important; 
            left: 0 !important; 
            right: 0 !important;
            display: flex !important; 
            justify-content: space-between !important; 
            box-sizing: border-box !important;
        }
        .nav-menu, header .btn-book { display: none !important; }
        .mobile-menu-btn { 
            display: block !important; 
            color: #fff !important; 
            font-size: 1.8rem !important; 
            cursor: pointer; 
            z-index: 1002; 
        }
        .hero-title { font-size: 2.2rem !important; letter-spacing: 2px !important; }
        .section-separator h2 { font-size: 2rem !important; }
    }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal();
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if(nav) nav.classList.toggle('scrolled', window.scrollY > 50);
    });
});

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(r => r.json())
    .then(data => {
        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    });
}

// --- 3. HEADER GENERATOR ---
function generateGlobalHeader(activePage, data) {
    const container = document.getElementById('global-header-container');
    if (!container) return;
    
    const menus = [
        { id: 'home', name: 'Home', link: 'index.html' },
        { id: 'dining', name: 'Dining', link: 'dining.html' },
        { id: 'meeting', name: 'Meeting', link: 'meeting.html' },
        { id: 'wedding', name: 'Wedding', link: 'wedding.html' },
        { id: 'gallery', name: 'Gallery', link: 'gallery.html' }
    ];

    let navHTML = menus.map(m => `<li><a href="${m.link}" class="nav-link" ${m.id === activePage ? 'style="color:#D4AF37"' : ''}>${m.name}</a></li>`).join('');
    let mobHTML = menus.map(m => `<a href="${m.link}" class="mobile-nav-link">${m.name}</a>`).join('');

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">Book Now</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            <div style="position:absolute; top:25px; right:25px; color:#fff; font-size:3rem;" onclick="toggleMobileMenu()">&times;</div>
            ${mobHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="color:#D4AF37; border:1px solid #D4AF37; padding:10px;">Book Now</a>
        </div>
    `;
}

// --- 4. VIDEO TOUR FUNCTIONS ---
window.openTour = function(src) {
    const modal = document.getElementById('tourModal');
    const vid = document.getElementById('tourVideo');
    if(!src || !vid) return;

    vid.src = src;
    modal.classList.add('active');
    
    // FORCE FULLSCREEN PADA MOBILE
    if (window.innerWidth <= 768) {
        if (vid.requestFullscreen) vid.requestFullscreen();
        else if (vid.webkitRequestFullscreen) vid.webkitRequestFullscreen();
        else if (vid.msRequestFullscreen) vid.msRequestFullscreen();
    }
    
    vid.play().catch(e => console.log("Play blocked"));
};

window.closeTour = function() {
    const modal = document.getElementById('tourModal');
    const vid = document.getElementById('tourVideo');
    if(vid) { vid.pause(); vid.src = ""; }
    modal.classList.remove('active');
    if (document.fullscreenElement) document.exitFullscreen();
};

window.toggleMobileMenu = function() {
    document.getElementById('mobileNav').classList.toggle('active');
};

// --- 5. BOOKING ENGINE (Simplified for Paste) ---
function injectBookingModal() {
    // Fungsi ini tetap menggunakan logika fetch CONFIG.API_URL Anda sebelumnya
}

function magicContentLoader(data) {
    // Memetakan video room tour ke tombol
    if(data.room_deluxe_video) document.getElementById('btn_tour_deluxe').onclick = () => openTour(data.room_deluxe_video);
    if(data.room_superior_video) document.getElementById('btn_tour_superior').onclick = () => openTour(data.room_superior_video);
    if(data.room_executive_video) document.getElementById('btn_tour_executive').onclick = () => openTour(data.room_executive_video);
    // ... sisa loader teks/gambar
}
