const qzCdn = 'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js';
let qzLoading = null;

const loadQz = () => {
    if (window.qz) {
        return Promise.resolve(window.qz);
    }

    if (qzLoading) {
        return qzLoading;
    }

    qzLoading = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = qzCdn;
        script.async = true;
        script.onload = () => window.qz ? resolve(window.qz) : reject(new Error('QZ Tray no cargo correctamente.'));
        script.onerror = () => reject(new Error('No se pudo cargar QZ Tray.'));
        document.head.appendChild(script);
    });

    return qzLoading;
};

const ticketTextFrom = (paper) => {
    return paper.innerText
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .join('\n')
        .concat('\n\n\n');
};

const connectQz = async () => {
    const qz = await loadQz();

    if (! qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    return qz;
};

const printWithBrowser = () => {
    window.print();
};

window.RumikaQz = {
    async printFromButton(button) {
        const modal = button.closest('.rm-print-preview-modal');
        const paper = modal?.querySelector('.rm-print-preview-paper');
        const printerName = button.dataset.printerName || '';
        const useQz = button.dataset.useQz === '1' && printerName !== '';

        if (! paper) {
            printWithBrowser();

            return;
        }

        if (! useQz) {
            printWithBrowser();

            return;
        }

        try {
            const qz = await connectQz();
            const printer = await qz.printers.find(printerName);
            const config = qz.configs.create(printer, {
                copies: 1,
                margins: 0,
            });

            await qz.print(config, [{
                type: 'raw',
                format: 'plain',
                data: ticketTextFrom(paper),
            }]);
        } catch (error) {
            window.alert(`No se pudo imprimir con QZ Tray. Verifica que QZ Tray este abierto y que la impresora "${printerName}" exista.`);
            console.error(error);
        }
    },
};

window.addEventListener('rumika-auto-print-ticket', () => {
    window.setTimeout(() => {
        const button = document.querySelector('.rm-auto-print-ticket');

        if (! button) {
            return;
        }

        button.click();
    }, 350);
});
