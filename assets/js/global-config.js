
/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER
 * Mengatur: Config API, Header, Footer, Styling, dan Booking Engine Global.
 */

var CONFIG = { API_URL: "", WA: "" };

// --- 1. CSS GLOBAL INJECTION (Modal, Tombol, Loading) ---
const globalCSS = `
    /* TOMBOL BOOKING */
    .btn-check { width: 100%; background-color: #1B4D3E !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; margin-top: 25px; margin-bottom: 20px; letter-spacing: 1px; border-radius: 4px; display: block; font-family: 'Montserrat', sans-serif; }
    .btn-check:hover { background-color: #143d30 !important; }
    .btn-submit { width: 100%; background-color: #D4AF37 !important; color: #fff !important; padding: 15px; border: none; text-transform: uppercase; font-weight: bold; cursor: pointer; border-radius: 4px; margin-top: 10px; font-family: 'Montserrat', sans-serif; }
    .btn-submit:disabled { background-color: #ccc !important; cursor: not-allowed; }
    
    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-top: 5px solid #D4AF37; max-height: 90vh; overflow-y: auto; border-radius: 8px; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }
    .form-group { margin-bottom: 15px; text-align: left; }
    .form-label { display: block; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; color: #1B4D3E; }
    .form-input, .form-select { width: 100%; padding: 10px; border: 1px solid #ddd; font-family: 'Montserrat', sans-serif; font-size: 1rem; border-radius: 4px; box-sizing: border-box; }
    .form-input:focus { border-color: #D4AF37; outline: none; }
    
    /* UTILS */
    #roomResultArea { display: none; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; animation: fadeUp 0.5s; }
    .loading { display: none; text-align: center; margin: 10px 0; color: #C5A059; font-size: 0.9rem; font-style: italic; }
    @keyframes fadeUp { from {opacity:0; transform:translateY(30px);} to {opacity:1; transform:translateY(0);} }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// Load FontAwesome
if (!document.querySelector('link[href*="font-awesome"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    document.head.appendChild(link);
}

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal(); // Pasang Modal di halaman
});

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        if(data.social_whatsapp) CONFIG.WA = cleanWaNumber(data.social_whatsapp);
        if(data.api_url) CONFIG.API_URL = data.api_url; // Pastikan API_URL ada di data.json atau hardcode dibawah
        if(!CONFIG.API_URL) CONFIG.API_URL = "https://script.google.com/macros/s/AKfycbz8NCQgwChpMLSYF3FjkVtXlgoe12u_-UHHNedozKrTuMmp-piWtzINkcCZeF0XuBWdXQ/exec"; // Fallback URL

        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    })
    .catch(err => console.error("Error:", err));
}

// --- 3. BOOKING ENGINE LOGIC ---

// A. HTML Injection
function injectBookingModal() {
    // Cek jika modal sudah ada (misal di index.html lama), jangan buat lagi
    if (document.getElementById('bookingModal')) {
        setupDateValidation(); // Tetap jalankan validasi tanggal
        return;
    }

    const modalHTML = `
    <div class="modal-overlay" id="bookingModal">
        <div class="booking-form-box">
            <span class="close-modal" onclick="closeBooking()">&times;</span>
            <h2 style="font-family: 'Cormorant Garamond', serif; text-align: center; margin-bottom: 20px; color:#1a1a1a;">Check Availability</h2>
            <form id="resForm" onsubmit="return false;">
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Check In</label>
                        <input type="date" id="checkIn" class="form-input" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Check Out</label>
                        <input type="date" id="checkOut" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Promo Code</label>
                    <input type="text" id="promoCode" class="form-input" placeholder="e.g. VIP10" style="text-transform:uppercase;">
                </div>
                <button type="button" class="btn-check" id="btnSearch" onclick="checkRooms()">Search Rooms</button>
                <div class="loading" id="searchLoader">Checking rates & availability...</div>

                <div id="roomResultArea">
                    <div class="form-group">
                        <label class="form-label">Available Rooms</label>
                        <select id="roomSelect" class="form-select" onchange="enableSubmit()"><option value="">-- Select a Room --</option></select>
                    </div>
                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" id="namaTamu" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">WhatsApp</label><input type="text" id="waTamu" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" id="emailTamu" class="form-input" required></div>
                    <button type="button" class="btn-submit" id="btnFinalSubmit" onclick="submitBooking()" disabled>Confirm Booking</button>
                    <div class="loading" id="submitLoader">Processing reservation...</div>
                </div>
            </form>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    setupDateValidation();
}

// B. Date Validation Logic (H+1)
function setupDateValidation() {
    const elCheckIn = document.getElementById('checkIn');
    const elCheckOut = document.getElementById('checkOut');
    if(!elCheckIn || !elCheckOut) return;

    const today = new Date().toISOString().split('T')[0];
    elCheckIn.setAttribute('min', today);
    elCheckOut.disabled = true;

    elCheckIn.addEventListener('change', function() {
        if (this.value) {
            elCheckOut.disabled = false;
            let date = new Date(this.value);
            date.setDate(date.getDate() + 1);
            let nextDay = date.toISOString().split('T')[0];
            elCheckOut.setAttribute('min', nextDay);
            elCheckOut.value = nextDay; // Auto Fill H+1
        } else {
            elCheckOut.disabled = true;
            elCheckOut.value = '';
        }
    });
}

// C. Booking Functions
function openBooking() { document.getElementById('bookingModal').classList.add('active'); }
function closeBooking() { document.getElementById('bookingModal').classList.remove('active'); }
function enableSubmit() { 
    const sel = document.getElementById('roomSelect');
    document.getElementById('btnFinalSubmit').disabled = !sel.value; 
}

function checkRooms() {
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;
    const promo = document.getElementById('promoCode').value;

    if(!cin || !cout) return alert("Please select Check-In and Check-Out dates.");
    
    document.getElementById('searchLoader').style.display = 'block';
    document.getElementById('btnSearch').disabled = true;

    fetch(CONFIG.API_URL, {
        method: 'POST',
        body: JSON.stringify({ action: "search", checkIn: cin, checkOut: cout, promoCode: promo })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('searchLoader').style.display = 'none';
        document.getElementById('btnSearch').disabled = false;
        
        const sel = document.getElementById('roomSelect');
        sel.innerHTML = '<option value="">-- Select a Room --</option>';
        
        if(res.status === 'success' && res.data.length > 0) {
            res.data.forEach(room => {
                let priceText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumSignificantDigits: 3 }).format(room.totalPrice);
                let opt = document.createElement('option');
                opt.value = room.name;
                opt.text = `${room.name} - ${priceText}`;
                opt.dataset.price = room.totalPrice;
                opt.dataset.pricetext = priceText;
                sel.appendChild(opt);
            });
            document.getElementById('roomResultArea').style.display = 'block';
            if(promo && res.data[0].totalPrice < res.data[0].normalPrice) alert("Promo Code Applied!");
        } else {
            alert("No rooms available for selected dates.");
        }
    })
    .catch(e => {
        document.getElementById('searchLoader').style.display = 'none';
        document.getElementById('btnSearch').disabled = false;
        alert("Connection Error. Please try again.");
    });
}

function submitBooking() {
    const roomSelect = document.getElementById('roomSelect');
    const roomVal = roomSelect.value;
    const priceRaw = roomSelect.options[roomSelect.selectedIndex].dataset.price;
    const priceFormatted = roomSelect.options[roomSelect.selectedIndex].dataset.pricetext;
    
    const name = document.getElementById('namaTamu').value;
    const wa = document.getElementById('waTamu').value;
    const email = document.getElementById('emailTamu').value;
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;

    if(!roomVal || !name || !wa || !email) return alert("Please fill all details.");

    document.getElementById('submitLoader').style.display = 'block';
    document.getElementById('btnFinalSubmit').disabled = true;

    // 1. Kirim ke Google Sheets
    fetch(CONFIG.API_URL, {
        method: 'POST',
        body: JSON.stringify({ action: "book", nama: name, email: email, whatsapp: wa, checkIn: cin, checkOut: cout, kamar: roomVal, totalHarga: priceRaw })
    });

    // 2. Kirim Email (PHP)
    fetch('send_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nama: name, email: email, whatsapp: wa, checkIn: cin, checkOut: cout, kamar: roomVal, totalHarga: priceFormatted })
    });

    // 3. Redirect WA
    setTimeout(() => {
        const msg = `Halo Spencer Green, saya mau konfirmasi booking:\n\nNama: ${name}\nKamar: ${roomVal}\nCheck-In: ${cin}\nCheck-Out: ${cout}\nTotal: ${priceFormatted}\n\nMohon info pembayaran.`;
        const waNum = CONFIG.WA || "6281234567890";
        window.location.href = `https://wa.me/${waNum}?text=${encodeURIComponent(msg)}`;
    }, 1500);
}

// --- 4. HEADER & FOOTER GENERATOR ---
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

function toggleMobileMenu() { document.getElementById('mobileNav').classList.toggle('active'); }
function cleanWaNumber(str) { return str.toString().replace(/[^0-9]/g, '').replace(/^08/, '628'); }

// --- 5. MAGIC CONTENT LOADER ---
function magicContentLoader(data) {
    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_')) {
                el.src = data[key];
                if(key.includes('_hero')) {
                    const hero = document.querySelector('[class*="hero-"]');
                    if(hero) hero.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('${data[key]}')`;
                }
            } else if (key.endsWith('_link')) {
                el.href = data[key];
            } else if (!key.startsWith('social_')) {
                el.innerText = data[key];
            }
        }
    });
}
