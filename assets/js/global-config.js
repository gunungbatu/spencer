/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - SPENCER GREEN
 */

var CONFIG = { API_URL: "", WA: "" };

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
    
    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .booking-form-box { background: #fff; padding: 40px; width: 90%; max-width: 500px; position: relative; border-top: 5px solid #D4AF37; border-radius: 8px; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; }
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; color: #1B4D3E; }
    .form-input, .form-select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .btn-check { width: 100%; background: #1B4D3E; color: #fff; padding: 15px; border: none; cursor: pointer; font-weight: bold; text-transform: uppercase; }
    .btn-submit { width: 100%; background: #D4AF37; color: #fff; padding: 15px; border: none; cursor: pointer; font-weight: bold; text-transform: uppercase; margin-top: 10px; }
    .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
`;

const styleTag = document.createElement('style');
styleTag.innerHTML = globalCSS;
document.head.appendChild(styleTag);

// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalConfig();
});

function loadGlobalConfig() {
    // Ambil URL API dan WA dari super_admin via PHP Bridge
    fetch('get_config.php')
    .then(r => r.json())
    .then(sysConfig => {
        CONFIG.API_URL = sysConfig.api_url;
        CONFIG.WA = sysConfig.hotel_wa;

        // Ambil data konten visual
        return fetch('data.json?t=' + new Date().getTime());
    })
    .then(r => r.json())
    .then(data => {
        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        injectBookingModal();
        magicContentLoader(data);
    })
    .catch(err => console.error("Sistem gagal sinkronisasi:", err));
}

// --- 3. CORE FUNCTIONS ---
function injectBookingModal() {
    if (document.getElementById('bookingModal')) return;
    const modalHTML = `
    <div class="modal-overlay" id="bookingModal">
        <div class="booking-form-box">
            <span class="close-modal" onclick="closeBooking()">&times;</span>
            <h2 style="text-align: center; margin-bottom: 20px;">Check Availability</h2>
            <form id="resForm" onsubmit="return false;">
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;"><label class="form-label">Check In</label><input type="date" id="checkIn" class="form-input" required></div>
                    <div class="form-group" style="flex:1;"><label class="form-label">Check Out</label><input type="date" id="checkOut" class="form-input" required></div>
                </div>
                <button type="button" class="btn-check" onclick="checkRooms()">Search Rooms</button>
                <div id="roomResultArea" style="display:none; margin-top:20px; border-top:1px solid #eee; padding-top:20px;">
                    <div class="form-group"><label class="form-label">Available Rooms</label><select id="roomSelect" class="form-select" onchange="document.getElementById('btnFinalSubmit').disabled=false"></select></div>
                    <div class="form-group"><label class="form-label">Name</label><input type="text" id="namaTamu" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">WhatsApp</label><input type="text" id="waTamu" class="form-input" required></div>
                    <button type="button" class="btn-submit" id="btnFinalSubmit" onclick="submitBooking()" disabled>Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Set min date
    const cin = document.getElementById('checkIn');
    if(cin) cin.setAttribute('min', new Date().toISOString().split('T')[0]);
}

function checkRooms() {
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;
    if(!cin || !cout) return alert("Pilih tanggal!");

    fetch(CONFIG.API_URL, {
        method: 'POST',
        body: JSON.stringify({ action: "search", checkIn: cin, checkOut: cout })
    })
    .then(r => r.json())
    .then(res => {
        const sel = document.getElementById('roomSelect');
        sel.innerHTML = '<option value="">-- Pilih Kamar --</option>';
        if(res.status === 'success' && res.data.length > 0) {
            res.data.forEach(room => {
                let opt = document.createElement('option');
                opt.value = room.name;
                opt.text = `${room.name} - Rp ${room.totalPrice.toLocaleString()}`;
                sel.appendChild(opt);
            });
            document.getElementById('roomResultArea').style.display = 'block';
        } else { alert("Maaf, kamar penuh pada tanggal tersebut."); }
    });
}

function submitBooking() {
    const name = document.getElementById('namaTamu').value;
    const room = document.getElementById('roomSelect').value;
    const cin = document.getElementById('checkIn').value;
    const cout = document.getElementById('checkOut').value;
    
    if(!name || !room) return alert("Lengkapi data!");

    const msg = `Halo Spencer Green, saya mau booking:\n\nNama: ${name}\nKamar: ${room}\nCheck-In: ${cin}\nCheck-Out: ${cout}`;
    window.location.href = `https://wa.me/${CONFIG.WA}?text=${encodeURIComponent(msg)}`;
}

function openBooking() { document.getElementById('bookingModal').classList.add('active'); }
function closeBooking() { document.getElementById('bookingModal').classList.remove('active'); }

function generateGlobalHeader(activePage, data) {
    const container = document.getElementById('global-header-container');
    if (!container) return;
    const menu = [
        {id:'home', n:'Home', l:'index.html'},
        {id:'dining', n:'Dining', l:'dining.html'},
        {id:'meeting', n:'Meeting', l:'meeting.html'},
        {id:'gallery', n:'Gallery', l:'gallery.html'}
    ];
    let nav = '';
    menu.forEach(m => nav += `<li><a href="${m.l}" class="nav-link" ${m.id==activePage?'style="color:#D4AF37"':''}>${m.n}</a></li>`);

    container.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">${nav}</ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">${data.header_btn_text || 'Book Now'}</a>
        </header>
    `;
}

function generateGlobalFooter(data) {
    const el = document.getElementById('global-footer');
    if(!el) return;
    el.innerHTML = `<div style="background:#1B4D3E; color:#fff; padding:50px; text-align:center;">
        <p>&copy; ${new Date().getFullYear()} Spencer Green Hotel. Member of The Batu Hotel Group.</p>
    </div>`;
}

function magicContentLoader(data) {
    Object.keys(data).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            if (key.startsWith('img_')) el.src = data[key];
            else el.innerText = data[key];
        }
    });
}
