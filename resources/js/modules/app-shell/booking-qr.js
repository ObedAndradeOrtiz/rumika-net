import QRCode from 'qrcode';

const drawLogo = async (canvas, logoUrl) => {
    if (!logoUrl) {
        return;
    }

    const ctx = canvas.getContext('2d');
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.src = logoUrl;

    await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
    }).catch(() => null);

    if (!image.complete || !image.naturalWidth) {
        return;
    }

    const size = Math.round(canvas.width * 0.22);
    const x = Math.round((canvas.width - size) / 2);
    const y = Math.round((canvas.height - size) / 2);
    const radius = Math.round(size * 0.24);

    ctx.save();
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.roundRect(x - 8, y - 8, size + 16, size + 16, radius + 8);
    ctx.fill();
    ctx.clip();
    ctx.drawImage(image, x, y, size, size);
    ctx.restore();
};

const renderQr = async (card) => {
    const canvas = card.querySelector('canvas');
    const url = card.dataset.qrUrl;

    if (!canvas || !url) {
        return;
    }

    await QRCode.toCanvas(canvas, url, {
        errorCorrectionLevel: 'H',
        margin: 2,
        width: Number(canvas.getAttribute('width') || 220),
        color: {
            dark: '#0f766e',
            light: '#ffffff',
        },
    });

    await drawLogo(canvas, card.dataset.qrLogo);
};

const safeFileName = (name) => (name || 'rumika-qr')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase();

const bootBookingQr = () => {
    document.querySelectorAll('[data-booking-qr]').forEach((card) => {
        if (card.dataset.qrReady !== 'true' || card.dataset.qrRenderedUrl !== card.dataset.qrUrl) {
            card.dataset.qrReady = 'true';
            card.dataset.qrRenderedUrl = card.dataset.qrUrl || '';
            renderQr(card);
        }

        const button = card.querySelector('[data-qr-download]');
        if (button && button.dataset.qrDownloadReady !== 'true') {
            button.dataset.qrDownloadReady = 'true';
            button.addEventListener('click', async () => {
                await renderQr(card);
                const link = document.createElement('a');
                link.download = `${safeFileName(card.dataset.qrName)}-qr-rumika.png`;
                link.href = card.querySelector('canvas').toDataURL('image/png');
                link.click();
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', bootBookingQr);
document.addEventListener('livewire:navigated', bootBookingQr);
document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', bootBookingQr);
});
