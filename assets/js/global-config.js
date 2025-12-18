/**
 * GLOBAL CONFIGURATION & SYSTEM MANAGER - SPENCER GREEN
 */

var CONFIG = { 
    API_URL: "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec", 
    WA: "6281130700206" 
};

// ... (Bagian globalCSS tetap sama seperti sebelumnya) ...

document.addEventListener('DOMContentLoaded', function() {
    loadAllSystemData();
});

async function loadAllSystemData() {
    // 1. Coba ambil Config Sistem (URL API & WA)
    try {
        // Path '../../' digunakan jika JS berada di assets/js/ untuk memanggil get_config.php di root
        const sysRes = await fetch('get_config.php'); 
        const sysData = await sysRes.json();
        if(sysData.api_url) CONFIG.API_URL = sysData.api_url;
        if(sysData.hotel_wa) CONFIG.WA = cleanWaNumber(sysData.hotel_wa);
    } catch (e) {
        console.warn("Gagal memuat Config Sistem, menggunakan default.", e);
    }

    // 2. Ambil Data Konten Visual (Ini yang mengisi gambar, teks, dan review)
    try {
        const dataRes = await fetch('data.json?t=' + new Date().getTime());
        const data = await dataRes.json();
        
        const activePage = document.body.getAttribute('data-page') || 'home';
        
        // Jalankan semua fungsi generator
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        injectBookingModal();
        magicContentLoader(data); // Fungsi ini yang mengisi h1, p, dan img
        
        // Pemicu khusus untuk Review di Index
        if(activePage === 'home') renderReviews(); 
    } catch (e) {
        console.error("Gagal memuat Konten Visual (data.json).", e);
    }
}

// ... (Fungsi magicContentLoader, generateGlobalHeader, dll tetap sama) ...
