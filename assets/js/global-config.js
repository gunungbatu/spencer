var CONFIG = { API_URL: "", WA: "", MAPS: "" };
async function loadConfig() {
    try {
        let r = await fetch('config.json?t='+Date.now());
        let d = await r.json();
        CONFIG.API_URL = d.api_url; CONFIG.WA = d.hotel_wa; CONFIG.MAPS = d.hotel_maps_link;
        
        // Auto Update Link
        document.querySelectorAll('.wa-link').forEach(el => el.href = `https://wa.me/${d.hotel_wa}`);
        document.querySelectorAll('.maps-link').forEach(el => el.href = d.hotel_maps_link);
        return d;
    } catch(e) { console.error(e); }
}
loadConfig();
