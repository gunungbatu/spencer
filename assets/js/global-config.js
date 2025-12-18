/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER
 * Mengatur: Config API, Header, Footer, Styling, dan Booking Engine Global.
 */
var CONFIG = { API_URL: "", WA: "" };
// --- 1. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function() {
    loadGlobalData();
    injectBookingModal(); // Pasang Modal di halaman
});
function loadGlobalData() {
    fetch('data.json?t=' + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        // Prioritas WA dari config.json jika ada, fallback data.json
        fetch('config.json')
        .then(r => r.json())
        .then(config => {
            CONFIG.WA = cleanWaNumber(config.hotel_wa || data.social_whatsapp || "628123456789");
            CONFIG.API_URL = config.api_url || "https://script.google.com/macros/s/AKfycbz8NCQgwChpMLSYF3FjkVtXlgoe12u_-UHHNedozKrTuMmp-piWtzINkcCZeF0XuBWdXQ/exec";
        })
        .catch(() => {
            CONFIG.WA = cleanWaNumber(data.social_whatsapp || "628123456789");
            CONFIG.API_URL = "https://script.google.com/macros/s/AKfycbz8NCQgwChpMLSYF3FjkVtXlgoe12u_-UHHNedozKrTuMmp-piWtzINkcCZeF0XuBWdXQ/exec";
        })
        .finally(() => {
            const activePage = document.body.getAttribute('data-page') || 'home';
            generateGlobalHeader(activePage, data);
            generateGlobalFooter(data);
            magicContentLoader(data);
        });
    })
    .catch(err => console.error("Error:", err));
}
// --- Sisanya sama seperti sebelumnya, tapi ubah menus di generateGlobalHeader ---
function generateGlobalHeader(activePage, data) {
    const container = document.getElementById('global-header-container');
    if (!container) return;
    const bookText = data.header_btn_text || "Book Now";
    const menus = [
        { id: 'home', name: 'Home', link: '/' },  // Diubah ke '/' untuk root domain
        { id: 'dining', name: 'Dining', link: 'dining.html' },
        { id: 'meeting', name: 'Meeting', link: 'meeting.html' },
        { id: 'wedding', name: 'Wedding', link: 'wedding.html' },
        { id: 'gallery', name: 'Gallery', link: 'gallery.html' }
    ];
    // Sisanya sama...
}
// --- Fungsi lain tetap sama: generateGlobalFooter, toggleMobileMenu, cleanWaNumber, magicContentLoader, dll. ---
