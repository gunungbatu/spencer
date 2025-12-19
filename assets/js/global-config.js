/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER
 */

var CONFIG = { API_URL: "", WA: "" };

// CSS GLOBAL (Header, Modal, Footer, dll)
const globalCSS = `
    :root { --gold: #D4AF37; --emerald: #1B4D3E; --dark: #1a1a1a; --light: #F9F9F9; }

    /* MODAL */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-top: 5px solid var(--gold); max-height: 90vh; overflow-y: auto; border-radius: 8px; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }

    /* HEADER */
    #navbar { 
        position: fixed; top: 0; left: 0; width: 100%; padding: 20px 50px; 
        display: flex; justify-content: space-between; align-items: center; 
        z-index: 1000; transition: all 0.4s ease; 
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent); 
        box-sizing: border-box;
    }
    #navbar.scrolled { background: var(--emerald); padding: 15px 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .logo { color:#fff; font-family:'Cormorant Garamond', serif; font-size:1.8rem; font-weight:600; letter-spacing:2px; text-transform:uppercase; }
    .nav-menu { display:flex; gap:30px; list-style:none; margin:0; padding:0; }
    .nav-link { color:#fff; text-decoration:none; text-transform:uppercase; font-size:0.8rem; letter-spacing:1px; font-weight:500; }
    .btn-book { padding:10px 20px; border:1px solid var(--gold); color:#fff; text-decoration:none; text-transform:uppercase; font-size:0.8rem; cursor:pointer; border-radius:4px; }
    .mobile-menu-btn { display: none; font-size: 1.5rem; color: #fff; cursor: pointer; }
    .mobile-nav-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(27, 77, 62, 0.98); z-index: 1001; display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: 0.4s; }
    .mobile-nav-overlay.active { opacity: 1; pointer-events: auto; }
    .mobile-nav-link { color: #fff; font-size: 1.5rem; margin: 15px 0; text-decoration: none; font-family: 'Cormorant Garamond', serif; text-transform: uppercase; letter-spacing: 2px; }

    /* FOOTER */
    #global-footer { background: var(--emerald); color: #fff; padding: 50px; text-align: center; }
    .social-box { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
    .social-icon { color: #fff; width: 40px; height: 40px; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
    .social-icon:hover { background: var(--gold); border-color: var(--gold); }

    @media (max-width: 900px) {
        #navbar { padding: 15px 20px; }
        .nav-menu, .btn-book { display: none; }
        .mobile-menu-btn { display: block; }
    }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// FontAwesome
if (!document.querySelector('link[href*="font-awesome"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    document.head.appendChild(link);
}

// INITIALIZATION
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal();

    // Scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
    });
});

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(r => r.json())
    .then(data => {
        if(data.social_whatsapp) CONFIG.WA = cleanWaNumber(data.social_whatsapp);
        if(data.api_url) CONFIG.API_URL = data.api_url;
        if(!CONFIG.API_URL) CONFIG.API_URL = "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec";

        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    })
    .catch(err => console.error(err));
}

function injectBookingModal() { /* kode modal Anda yang sudah ada, tetap */ }

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
        let activeClass = (m.id === activePage) ? 'style="color:var(--gold);"' : '';
        navHTML += `<li><a href="${m.link}" class="nav-link" ${activeStyle}>${m.name}</a></li>`;
        mobHTML += `<a href="${m.link}" class="mobile-nav-link" ${activeClass}>${m.name}</a>`;
    });

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${navHTML}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${bookText}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">&#9776;</div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            ${mobHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="border:1px solid var(--gold); padding:10px 30px; margin-top:30px;">${bookText}</a>
            <div style="position:absolute; top:30px; right:30px; color:#fff; font-size:2rem; cursor:pointer;" onclick="toggleMobileMenu()">&times;</div>
        </div>
    `;
}

function generateGlobalFooter(data) {
    const el = document.getElementById('global-footer');
    if(!el) return;
    const socs = [
        {k:'social_instagram', i:'fa-instagram'},
        {k:'social_tiktok', i:'fa-tiktok'},
        {k:'social_youtube', i:'fa-youtube'},
        {k:'social_facebook', i:'fa-facebook-f'},
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

function toggleMobileMenu() { document.getElementById('mobileNav').classList.toggle('active'); }
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
