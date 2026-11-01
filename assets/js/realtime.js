/**
 * Memuat ulang halaman ketika data pesanan atau katalog berubah.
 */

document.addEventListener('DOMContentLoaded', function () {
    const url = document.body.dataset.realtimeUrl;
    if (!url) return;

    let currentSignature = null;
    let checking = false;

    async function checkForUpdates() {
        if (checking || document.visibilityState === 'hidden') return;
        checking = true;

        try {
            const response = await fetch(url, {
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Status pembaruan tidak tersedia.');

            const data = await response.json();
            if (typeof data.signature !== 'string') throw new Error('Signature pembaruan tidak valid.');

            if (currentSignature === null) {
                currentSignature = data.signature;
            } else if (currentSignature !== data.signature) {
                window.location.reload();
                return;
            }
        } catch (error) {
            console.error('Pemeriksaan pembaruan realtime gagal:', error);
        } finally {
            checking = false;
        }
    }

    checkForUpdates();
    window.setInterval(checkForUpdates, 5000);
});
