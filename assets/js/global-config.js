/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - SPENCER GREEN
 * Perbaikan Full: Mengembalikan fungsi yang hilang (generateGlobalHeader, cleanWaNumber, dll)
 */

var CONFIG = { 
    API_URL: "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec", 
    WA: "6281130700206" 
};

// --- 1. CSS GLOBAL INJECTION ---
const globalCSS = `
    html, body { max-width: 100%; overflow-x: hidden; margin: 0; padding: 0; width: 100%; box-sizing: border-box; }
    header#navbar { position: fixed; top: 0; width: 100%; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; transition: 0.4s; background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent); box-sizing: border-box; }
    header#navbar.scrolled { background: #1B4D3E !important; padding: 15px 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    .logo { color:#fff; font-family:'Cormorant Garamond', serif; font-size:1.8rem; font-weight:600; letter-spacing:2px; text-transform:uppercase; }
    .nav-menu { display:flex; gap:30px; list-style:none; }
    .nav-link { color:#fff; text-decoration:none; text-transform:uppercase; font-size:0.8rem; font-weight:500; }
    .btn-book { padding:10px 20px; border:1px solid #D4AF37; color:#fff; text-decoration:none; text-transform:uppercase; font-size:0.8rem; cursor:pointer; }
    .btn-book:hover { background:#D4AF37; color:#fff; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: 0.3s; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; border-top: 5px solid #D4AF37; border-radius: 8px; position: relative; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }
    .mobile-menu-btn { display: none; font-size: 1.5rem; color: #fff; cursor: pointer; }
    .mobile-nav-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(27, 77, 62, 0.98); z-index: 1001; display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: 0.4s; }
    .mobile-nav-overlay.active { opacity: 1; pointer-events: auto; }
    .mobile-nav-link { color: #fff; font-size: 1.5rem; margin: 15px 0; text-decoration: none; text-transform: uppercase; }
    @media (max-width: 900px) { header#navbar { padding: 15px 20px; } .nav-menu, .btn-book { display: none; } .mobile-menu-btn { display: block; } }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadAllSystemData();
});

async function loadAllSystemData() {
    // A. Ambil Config Sistem (URL API & WA)
    try {
        const sysRes = await fetch('get_config.php');
        const sysData = await sysRes.json();
        if(sysData.api_url) CONFIG.API_URL = sysData.api_url;
        if(sysData.hotel_wa) CONFIG.WA = cleanWaNumber(sysData.hotel_wa);
    } catch (e) {
        console.warn("Gagal memuat Config Sistem, menggunakan default.");
    }

    // B. Ambil Data Visual (Teks & Gambar)
    try {
        const dataRes = await fetch('data.json?t=' + new Date().getTime());
        const data = await dataRes.json();
        const activePage = document.body.getAttribute('data-page') || 'home';
        
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        injectBookingModal();
        magicContentLoader(data);
        
        if(activePage === 'home') loadReviews();
    } catch (e) {
        console.error("Gagal memuat data.json.", e);
    }
}

// --- 3. CORE GENERATORS (FUNGSI YANG TADI HILANG) ---

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
    let navHTML = ''; let mobHTML = '';
    menus.forEach(m => {
        let activeStyle = (m.id === activePage) ? 'style="color:#D4AF37;"' : '';
        navHTML += `<li><a href="${m.link}" class="nav-link" ${activeStyle}>${m.name}</a></li>`;
        mobHTML += `<a href="${m.link}" class="mobile-nav-link">${m.name}</a>`;
    });

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${data.header_btn_text || 'Book Now'}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">&#9776;</div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            ${mobHTML}
            <div style="position:absolute; top:30px; right:30px; color:#fff; font-size:2rem; cursor:pointer;" onclick="toggleMobileMenu()">&times;</div>
        </div>
    `;
}

function generateGlobalFooter(data) {
    const el = document.getElementById('global-footer');
    if(!el) return;
    el.innerHTML = `<div style="background:#1B4D3E; color:#fff; padding:50px; text-align:center;">
        <p>&copy; ${new Date().getFullYear()} Spencer Green Hotel. All Rights Reserved.</p>
    </div>`;
}

function magicContentLoader(data) {
    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_') || key.endsWith('_img')) el.src = data[key];
            else if (key.endsWith('_link')) el.href = data[key];
            else el.innerText = data[key];
        }
    });
}

function cleanWaNumber(str) { 
    return str ? str.toString().replace(/[^0-9]/g, '').replace(/^08/, '628') : ""; 
}

function toggleMobileMenu() { 
    document.getElementById('mobileNav').classList.toggle('active'); 
}

// --- 4. BOOKING MODAL & LOGIC ---

function injectBookingModal() {
    if (document.getElementById('bookingModal')) return;
    const modalHTML = `
    <div class="modal-overlay" id="bookingModal">
        <div class="booking-form-box">
            <span class="close-modal" onclick="closeBooking()">&times;</span>
            <h2 style="text-align: center; margin-bottom: 20px;">Check Availability</h2>
            <form id="resForm" onsubmit="return false;">
                <input type="date" id="checkIn" class="form-input" required style="margin-bottom:10px;">
                <input type="date" id="checkOut" class="form-input" required style="margin-bottom:10px;">
                <button type="button" class="btn-book" style="width:100%; background:#1B4D3E;" onclick="alert('Mencari...')">Search</button>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function openBooking() { document.getElementById('bookingModal').classList.add('active'); }
function closeBooking() { document.getElementById('bookingModal').classList.remove('active'); }

function loadReviews() {
    fetch('reviews.json?t=' + Date.now())
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('reviewContainer');
        if(!container) return;
        container.innerHTML = '';
        data.filter(r => r.visible === true).slice(0, 3).forEach(r => {
            container.innerHTML += `<div class="review-card"><strong>${r.name}</strong>: ${r.comment}</div>`;
        });
    });
}
