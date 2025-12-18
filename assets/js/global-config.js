/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - SPENCER GREEN
 * Perbaikan: Video Fix, Full Booking Logic, & Dynamic Content
 */

var CONFIG = { 
    API_URL: "", 
    WA: "" 
};

// --- 1. CSS GLOBAL INJECTION ---
const styleTag = document.createElement('style');
styleTag.innerHTML = `
    html, body { max-width: 100%; overflow-x: hidden; margin: 0; padding: 0; width: 100%; box-sizing: border-box; }
    header#navbar.scrolled { background: #1B4D3E !important; padding: 15px 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: 0.3s; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; border-top: 5px solid #D4AF37; border-radius: 8px; position: relative; max-height: 90vh; overflow-y: auto; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }
    .loading { display: none; text-align: center; color: #C5A059; font-style: italic; margin: 10px 0; }
    #roomResultArea { display: none; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
`;
document.head.appendChild(styleTag);

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadAllSystemData();
});

async function loadAllSystemData() {
    // A. Ambil Config dari get_config.php
    try {
        const sysRes = await fetch('get_config.php');
        const sysData = await sysRes.json();
        CONFIG.API_URL = sysData.api_url;
        CONFIG.WA = cleanWaNumber(sysData.hotel_wa);
    } catch (e) { console.warn("Menggunakan fallback config."); }

    // B. Ambil Data Konten dari data.json
    try {
        const dataRes = await fetch('data.json?t=' + Date.now());
        const data = await dataRes.json();
        const activePage = document.body.getAttribute('data-page') || 'home';
        
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        injectBookingModal();
        magicContentLoader(data); // Fungsi ini akan memperbaiki video & teks
        
        if(activePage === 'home') loadReviews();
    } catch (e) { console.error("Gagal memuat data.json", e); }
}

// --- 3. CORE FUNCTIONS ---

function magicContentLoader(data) {
    // Fix Video Hero
    const heroVid = document.getElementById('heroVideo');
    if (heroVid && data.hero_video) {
        heroVid.src = data.hero_video; // Mengganti hero-video.mp4 menjadi Hotel.mp4
        heroVid.load();
    }

    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_') || key.endsWith('_img')) el.src = data[key];
            else if (key.endsWith('_link')) el.href = data[key];
            else if (typeof data[key] === 'string') el.innerText = data[key];
        }
    });
}

function injectBookingModal() {
    if (document.getElementById('bookingModal')) return;
    const modalHTML = `
    <div class="modal-overlay" id="bookingModal">
        <div class="booking-form-box">
            <span class="close-modal" onclick="closeBooking()">&times;</span>
            <h2 style="text-align: center; margin-bottom: 20px; font-family: 'Cormorant Garamond';">Check Availability</h2>
            <form id="resForm" onsubmit="return false;">
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <div style="flex:1;"><label style="font-size:0.7rem;">CHECK IN</label><input type="date" id="checkIn" class="form-input" required style="width:100%; padding:8px;"></div>
                    <div style="flex:1;"><label style="font-size:0.7rem;">CHECK OUT</label><input type="date" id="checkOut" class="form-input" required style="width:100%; padding:8px;"></div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.7rem;">PROMO CODE</label>
                    <input type="text" id="promoCode" placeholder="e.g. VIP2025" style="width:100%; padding:8px; text-transform:uppercase;">
                </div>
                <button type="button" class="btn-check" onclick="checkRooms()" style="width:100%; background:#1B4D3E; color:#fff; padding:12px; border:none; cursor:pointer; font-weight:bold;">SEARCH ROOMS</button>
                <div class="loading" id="searchLoader">Checking availability...</div>
                
                <div id="roomResultArea">
                    <label style="font-size:0.7rem;">AVAILABLE ROOMS</label>
                    <select id="roomSelect" style="width:100%; padding:10px; margin-bottom:15px;" onchange="document.getElementById('btnFinalSubmit').disabled=false"></select>
                    <input type="text" id="namaTamu" placeholder="Your Full Name" style="width:100%; padding:10px; margin-bottom:10px;">
                    <input type="text" id="waTamu" placeholder="WhatsApp Number" style="width:100%; padding:10px; margin-bottom:10px;">
                    <button type="button" id="btnFinalSubmit" class="btn-submit" onclick="submitBooking()" disabled style="width:100%; background:#D4AF37; color:#fff; padding:15px; border:none; cursor:pointer; font-weight:bold;">CONFIRM BOOKING</button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Set min date H+0
    const cin = document.getElementById('checkIn');
    if(cin) cin.setAttribute('min', new Date().toISOString().split('T')[0]);
}

function checkRooms() {
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;
    const promo = document.getElementById('promoCode').value;
    
    if(!cin || !cout) return alert("Please select dates.");
    if(!CONFIG.API_URL) return alert("API System not ready. Check Super Admin.");

    document.getElementById('searchLoader').style.display = 'block';
    
    fetch(CONFIG.API_URL, {
        method: 'POST',
        body: JSON.stringify({ action: "search", checkIn: cin, checkOut: cout, promoCode: promo })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('searchLoader').style.display = 'none';
        const sel = document.getElementById('roomSelect');
        sel.innerHTML = '<option value="">-- Choose Room --</option>';
        
        if(res.status === 'success' && res.data.length > 0) {
            res.data.forEach(room => {
                let opt = document.createElement('option');
                opt.value = room.name;
                opt.text = `${room.name} - Rp ${room.totalPrice.toLocaleString()}`;
                opt.dataset.price = room.totalPrice;
                sel.appendChild(opt);
            });
            document.getElementById('roomResultArea').style.display = 'block';
        } else { alert("Rooms fully booked for these dates."); }
    })
    .catch(err => {
        document.getElementById('searchLoader').style.display = 'none';
        alert("System Error. Try again later.");
    });
}

function submitBooking() {
    const name = document.getElementById('namaTamu').value;
    const room = document.getElementById('roomSelect').value;
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;
    const wa = document.getElementById('waTamu').value;
    const price = document.getElementById('roomSelect').options[document.getElementById('roomSelect').selectedIndex].dataset.price;
    
    if(!name || !wa) return alert("Please fill your contact details.");

    // Redirect to WhatsApp
    const msg = `Halo Spencer Green Hotel,\nSaya mau konfirmasi booking:\n\nNama: ${name}\nKamar: ${room}\nCheck-In: ${cin}\nCheck-Out: ${cout}\nTotal: Rp ${parseInt(price).toLocaleString()}`;
    window.location.href = `https://wa.me/${CONFIG.WA}?text=${encodeURIComponent(msg)}`;
}

function generateGlobalHeader(activePage, data) {
    const container = document.getElementById('global-header-container');
    if (!container) return;
    const menu = [
        {id:'home', n:'Home', l:'index.html'}, {id:'dining', n:'Dining', l:'dining.html'},
        {id:'meeting', n:'Meeting', l:'meeting.html'}, {id:'wedding', n:'Wedding', l:'wedding.html'},
        {id:'gallery', n:'Gallery', l:'gallery.html'}
    ];
    let nav = '';
    menu.forEach(m => {
        let active = (m.id === activePage) ? 'style="color:#D4AF37; border-bottom:1px solid #D4AF37;"' : '';
        nav += `<li><a href="${m.l}" class="nav-link" ${active}>${m.n}</a></li>`;
    });

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${nav}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${data.header_btn_text || 'BOOK NOW'}</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">&#9776;</div>
        </header>
        <div class="mobile-nav-overlay" id="mobileNav">
            <div style="text-align:right; padding:30px; font-size:2rem; color:#fff;" onclick="toggleMobileMenu()">&times;</div>
            ${menu.map(m => `<a href="${m.l}" class="mobile-nav-link">${m.n}</a>`).join('')}
        </div>`;
}

function generateGlobalFooter(data) {
    const el = document.getElementById('global-footer');
    if(!el) return;
    el.innerHTML = `
        <footer style="background:#1B4D3E; color:#fff; padding:60px 20px; text-align:center;">
            <h3 style="font-family:'Cormorant Garamond'; font-size:2rem; margin-bottom:10px;">Spencer Green Hotel</h3>
            <p style="font-size:0.8rem; opacity:0.7;">Member of The Batu Hotel & Villas Group</p>
            <div style="margin:20px 0;">
                <a href="https://wa.me/${CONFIG.WA}" style="color:#fff; margin:0 10px;"><i class="fab fa-whatsapp"></i></a>
                <a href="${data.social_instagram}" style="color:#fff; margin:0 10px;"><i class="fab fa-instagram"></i></a>
            </div>
            <p style="font-size:0.7rem; margin-top:30px;">&copy; ${new Date().getFullYear()} All Rights Reserved.</p>
        </footer>`;
}

function loadReviews() {
    fetch('reviews.json?t=' + Date.now())
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('reviewContainer');
        if(!container) return;
        const active = data.filter(r => r.visible === true).slice(0, 3);
        container.innerHTML = active.map(r => `
            <div style="background:#fff; padding:30px; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.05); text-align:left;">
                <div style="color:#D4AF37; margin-bottom:15px;">${'⭐'.repeat(r.rating)}</div>
                <p style="font-style:italic; color:#666;">"${r.comment}"</p>
                <strong style="display:block; margin-top:20px; color:#1B4D3E;">- ${r.name}</strong>
            </div>
        `).join('');
    });
}

function openBooking() { document.getElementById('bookingModal').classList.add('active'); }
function closeBooking() { document.getElementById('bookingModal').classList.remove('active'); }
function toggleMobileMenu() { document.getElementById('mobileNav').classList.toggle('active'); }
function cleanWaNumber(str) { return str ? str.toString().replace(/[^0-9]/g, '').replace(/^08/, '628') : ""; }
