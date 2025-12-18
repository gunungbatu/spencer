async function loadGlobalData() {
    // 1. Ambil System Config (API & WA) secara paralel
    try {
        const configRes = await fetch('get_config.php');
        const sysConfig = await configRes.json();
        CONFIG.API_URL = sysConfig.api_url || "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec"; // Fallback URL
        CONFIG.WA = cleanWaNumber(sysConfig.hotel_wa) || "6281130700206";
    } catch (err) {
        console.warn("System Config gagal dimuat, menggunakan fallback.", err);
    }

    // 2. Ambil Data Visual (Teks & Gambar) - Ini yang membuat website tidak "kosong"
    try {
        const dataRes = await fetch('data.json?t=' + new Date().getTime());
        const data = await dataRes.json();
        
        const activePage = document.body.getAttribute('data-page') || 'home';
        generateGlobalHeader(activePage, data);
        generateGlobalFooter(data);
        magicContentLoader(data);
    } catch (err) {
        console.error("Data visual gagal dimuat. Pastikan data.json tersedia.", err);
    }
}
