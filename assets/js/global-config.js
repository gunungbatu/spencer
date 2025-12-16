/**
 * GLOBAL CONFIGURATION & FOOTER MANAGER
 * Script ini mengatur URL API, Nomor WA, dan Membuat Footer Otomatis di semua halaman.
 */

var CONFIG = {
    API_URL: "", // Akan diisi otomatis dari data.json
    WA: ""       // Akan diisi otomatis dari data.json
};

document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
});

function loadGlobalData() {
    // Tambahkan timestamp agar data tidak di-cache browser
    fetch('data.json?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        // 1. SIMPAN CONFIG UTAMA
        if(data.api_url) CONFIG.API_URL = data.api_url;
        if(data.social_whatsapp) CONFIG.WA = cleanWaNumber(data.social_whatsapp);

        // 2. GENERATE FOOTER OTOMATIS
        generateGlobalFooter(data);

        // 3. UPDATE KONTEN HALAMAN (Jika ada ID yang cocok)
        // Ini script "Magic" yang kemarin, dipindah kesini agar ringkas
        Object.keys(data).forEach(key => {
            const el = document.getElementById(key);
            if (el) {
                if (key.startsWith('img_')) {
                    el.src = data[key];
                    // Khusus Hero Background
                    if(key.includes('_hero')) {
                        const heroSec = document.querySelector('[class*="hero-"]'); 
                        if(heroSec) heroSec.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('${data[key]}')`;
                    }
                } else if (key.endsWith('_link')) {
                    el.href = data[key]; // Update Link Tombol
                } else if (!key.startsWith('social_')) {
                    el.innerText = data[key]; // Update Teks Biasa
                }
            }
        });
    })
    .catch(err => console.error("Gagal memuat data:", err));
}

function generateGlobalFooter(data) {
    const footerEl = document.getElementById('global-footer');
    if (!footerEl) return; // Jika tidak ada tag footer, stop.

    // Mapping Data JSON ke Icon FontAwesome
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
        // Hanya tampilkan jika link diisi di dashboard
        if (link && link !== "" && link !== "#") {
            // Khusus WA, pastikan formatnya benar
            let finalLink = link;
            if(item.key === 'social_whatsapp' && !link.includes('http')) {
                finalLink = `https://wa.me/${cleanWaNumber(link)}`;
            }
            
            socialHTML += `
                <a href="${finalLink}" target="_blank" class="social-icon">
                    <i class="fab ${item.icon}"></i>
                </a>
            `;
        }
    });

    // Inject HTML ke dalam Footer
    footerEl.innerHTML = `
        <p>&copy; ${new Date().getFullYear()} Spencer Green Hotel. All Rights Reserved.</p>
        <div class="social-box">
            ${socialHTML}
        </div>
    `;
}

// Helper: Bersihkan nomor WA (buang +, -, spasi, ganti 08 jadi 628)
function cleanWaNumber(str) {
    if(!str) return "";
    let num = str.toString().replace(/[^0-9]/g, ''); // Hapus non-angka
    if(num.startsWith('08')) num = '628' + num.slice(2);
    return num;
}
