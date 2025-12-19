/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - SPENCER GREEN
 * FIX: Fullscreen Video & Mobile Layout
 */

var CONFIG = { 
    API_URL: "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec", 
    WA: "6281130700206" 
};

// --- 1. CSS GLOBAL INJECTION (Mengatasi Tampilan Terpotong & Bertumpuk) ---
const globalCSS = `
    /* CSS RESET & UTILS */
    * { box-sizing: border-box !important; }
    html, body { max-width: 100% !important; overflow-x: hidden !important; width: 100%; }

    /* HEADER & NAV FIX */
    header#navbar { 
        display: flex !important; justify-content: space-between !important; align-items: center !important;
        padding: 20px 50px; width: 100% !important; left: 0 !important; right: 0 !important;
    }
    header.scrolled { background: #1B4D3E !important; padding: 15px 50px !important; }

    /* VIDEO MODAL FULLSCREEN */
    .video-modal-overlay { 
        display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
        background: #000; z-index: 999999 !important; justify-content: center; align-items: center; 
    }
    .video-modal-overlay.active { display: flex; }
    .video-wrapper { width: 100%; height: 100%; position: relative; display: flex; justify-content: center; align-items: center; }
    .video-wrapper video { width: 100%; height: 100%; object-fit: contain; background: #000; }
    .close-video { 
        position: absolute; top: 20px; right: 20px; color: #fff; font-size: 3rem; 
        cursor: pointer; z-index: 10; text-shadow: 0 0 10px rgba(0,0,0,0.8);
    }

    /* RESPONSIVE OVERRIDE (AGRESIF) */
    @media (max-width: 900px) {
        header#navbar { padding: 10px 20px !important; }
        .nav-menu, header .btn-book { display: none !important; } /* Sembunyikan menu desktop */
        .mobile-menu-btn { display: block !important; color: #fff; font-size: 1.8rem; cursor: pointer; }
        
        /* Fix teks yang meluber di screenshot */
        .hero-title { font-size: 2.2rem !important; letter-spacing: 2px !important; width: 100%; }
        .section-separator h2, #home_intro_title { font-size: 2rem !important; width: 100%; }
        .content-card { flex-direction: column !important; }
        .card-image-box { height: 280px !important; }
        .card-info-box { padding: 30px 20px !important; }
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

// --- 3. HEADER GENERATOR (Hamburger Menu Fix) ---
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
    let mobHTML = menus.map(m => `<a href="${m.link}" class="mobile-nav-link" ${m.id === activePage ? 'style="color:#D4AF37"' : ''}>${m.name}</a>`).join('');

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${data.header_btn_text || "Book Now"}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            <div style="position:absolute; top:25px; right:25px; color:#fff; font-size:3rem;" onclick="toggleMobileMenu()">&times;</div>
            ${mobHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="color:#D4AF37; border:1px solid #D4AF37; padding:10px; margin-top:20px;">Book Now</a>
        </div>
    `;
}

// --- 4. VIDEO TOUR FUNCTIONS (Fullscreen Force) ---
window.openTour = function(src) {
    const modal = document.getElementById('tourModal');
    const vid = document.getElementById('tourVideo');
    if(!src || !vid) return;

    vid.src = src;
    modal.classList.add('active');
    
    // FORCE FULLSCREEN PADA MOBILE (Android & iOS)
    if (window.innerWidth <= 768) {
        if (vid.requestFullscreen) {
            vid.requestFullscreen();
        } else if (vid.webkitEnterFullscreen) { // iOS Safari magic
            vid.webkitEnterFullscreen();
        }
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

// Booking & Content Loader Functions
function injectBookingModal() { /* ... kode booking Anda ... */ }
function magicContentLoader(data) {
    if(data.room_deluxe_video) document.getElementById('btn_tour_deluxe').onclick = () => openTour(data.room_deluxe_video);
    if(data.room_superior_video) document.getElementById('btn_tour_superior').onclick = () => openTour(data.room_superior_video);
    if(data.room_executive_video) document.getElementById('btn_tour_executive').onclick = () => openTour(data.room_executive_video);
    // ... loader teks & gambar ...
}
function generateGlobalFooter(data) { /* ... kode footer Anda ... */ }
