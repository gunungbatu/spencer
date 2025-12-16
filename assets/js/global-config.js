/**
 * GLOBAL CONFIGURATION, HEADER & FOOTER MANAGER
 */

var CONFIG = { API_URL: "", WA: "" };

// Pastikan Font Awesome load
if (!document.querySelector('link[href*="font-awesome"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    document.head.appendChild(link);
}

document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
});

function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        if(data.social_whatsapp) CONFIG.WA = cleanWaNumber(data.social_whatsapp);
        
        // 1. GENERATE HEADER & FOOTER
        // Ambil nama halaman dari atribut body data-page (nanti kita set di HTML)
        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage);
        generateGlobalFooter(data);

        // 2. MAGIC CONTENT LOADER (Isi teks/gambar halaman)
        Object.keys(data).forEach(key => {
            const el = document.getElementById(key);
            if (el) {
                if (key.startsWith('img_')) {
                    el.src = data[key];
                    if(key.includes('_hero')) {
                        const heroSec = document.querySelector('[class*="hero-"]'); 
                        if(heroSec) heroSec.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('${data[key]}')`;
                    }
                } else if (key.endsWith('_link')) {
                    el.href = data[key];
                } else if (!key.startsWith('social_')) {
                    el.innerText = data[key];
                }
            }
        });
    })
    .catch(err => console.error("Data Load Error:", err));
}

// --- FUNGSI GENERATE HEADER (BARU) ---
function generateGlobalHeader(activePage) {
    const headerContainer = document.getElementById('global-header-container');
    if (!headerContainer) return;

    // Daftar Menu
    const menus = [
        { id: 'home', name: 'Home', link: 'index.html' },
        { id: 'dining', name: 'Dining', link: 'dining.html' },
        { id: 'meeting', name: 'Meeting', link: 'meeting.html' },
        { id: 'wedding', name: 'Wedding', link: 'wedding.html' },
        { id: 'gallery', name: 'Gallery', link: 'gallery.html' }
    ];

    // Buat HTML Menu Desktop
    let navHTML = '';
    let mobileNavHTML = '';

    menus.forEach(m => {
        // Cek aktif
        let activeStyle = (m.id === activePage) ? 'style="color:var(--gold); border-bottom:1px solid var(--gold);"' : '';
        let activeClass = (m.id === activePage) ? 'style="color:var(--gold);"' : '';

        navHTML += `<li><a href="${m.link}" class="nav-link" ${activeStyle}>${m.name}</a></li>`;
        
        // Buat Menu Mobile sekalian
        mobileNavHTML += `<a href="${m.link}" class="mobile-nav-link" ${activeClass}>${m.name}</a>`;
    });

    // Injeksi HTML Header Lengkap
    headerContainer.innerHTML = `
        <header id="navbar">
            <div class="logo">Spencer Green</div>
            <ul class="nav-menu">
                ${navHTML}
            </ul>
            <a href="javascript:void(0)" onclick="openBooking()" class="btn-book">Book Your Stay</a>
            <div class="mobile-menu-btn" onclick="toggleMobileMenu()">&#9776;</div>
        </header>

        <div class="mobile-nav-overlay" id="mobileNav">
            ${mobileNavHTML}
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); openBooking()" class="mobile-nav-link" style="border:1px solid var(--gold); padding:10px 30px; margin-top:30px;">Book Now</a>
            <div style="position:absolute; top:30px; right:30px; color:#fff; font-size:2rem; cursor:pointer;" onclick="toggleMobileMenu()">&times;</div>
        </div>
    `;

    // Pasang Event Listener Scroll (Karena elemen baru dibuat, listener harus dipasang disini)
    window.addEventListener('scroll', function() {
        const header = document.getElementById('navbar');
        if(header) header.classList.toggle('scrolled', window.scrollY > 50);
    });
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobileNav');
    if(menu) menu.classList.toggle('active');
}

// --- FUNGSI GENERATE FOOTER ---
function generateGlobalFooter(data) {
    const footerEl = document.getElementById('global-footer');
    if (!footerEl) return;

    const socialMap = [
        { key: 'social_instagram', icon: 'fa-instagram' },
        { key: 'social_tiktok',    icon: 'fa-tiktok' },
        { key: 'social_youtube',   icon: 'fa-youtube' },
        { key: 'social_facebook',  icon: 'fa-facebook-f' },
        { key: 'social_whatsapp',  icon: 'fa-whatsapp' }
    ];

    let socialHTML = '';
    socialMap.forEach(item => {
        const link = data[item.key];
        if (link && link !== "" && link !== "#") {
            let finalLink = link;
            if(item.key === 'social_whatsapp' && !link.includes('http')) finalLink = `https://wa.me/${cleanWaNumber(link)}`;
            socialHTML += `<a href="${finalLink}" target="_blank" class="social-icon"><i class="fab ${item.icon}"></i></a>`;
        }
    });

    footerEl.innerHTML = `
        <p>&copy; ${new Date().getFullYear()} Spencer Green Hotel. All Rights Reserved.</p>
        <div class="social-box">${socialHTML}</div>
    `;
}

function cleanWaNumber(str) {
    if(!str) return "";
    let num = str.toString().replace(/[^0-9]/g, '');
    if(num.startsWith('08')) num = '628' + num.slice(2);
    return num;
}
