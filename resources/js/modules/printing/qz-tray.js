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

const paperWidth = 42;
const esc = '\x1B';
const gs = '\x1D';

const normalizeTicketText = (value = '') => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\x20-\x7E\n]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

const line = (char = '-') => char.repeat(paperWidth);

const center = (value = '') => {
    const text = normalizeTicketText(value).slice(0, paperWidth);
    const left = Math.max(0, Math.floor((paperWidth - text.length) / 2));

    return `${' '.repeat(left)}${text}`.padEnd(paperWidth, ' ');
};

const right = (label, value) => {
    const leftText = normalizeTicketText(label);
    const rightText = normalizeTicketText(value);
    const spaces = Math.max(1, paperWidth - leftText.length - rightText.length);

    return `${leftText}${' '.repeat(spaces)}${rightText}`;
};

const wrap = (value = '', width = paperWidth) => {
    const words = normalizeTicketText(value).split(' ').filter(Boolean);
    const rows = [];
    let current = '';

    words.forEach((word) => {
        if (word.length > width) {
            if (current) {
                rows.push(current);
                current = '';
            }

            rows.push(word.slice(0, width));
            return;
        }

        const next = current ? `${current} ${word}` : word;

        if (next.length > width) {
            rows.push(current);
            current = word;
        } else {
            current = next;
        }
    });

    if (current) {
        rows.push(current);
    }

    return rows.length ? rows : [''];
};

const spanTexts = (element) => Array
    .from(element.querySelectorAll(':scope > span'))
    .map((span) => normalizeTicketText(span.innerText));

const ticketRowsFrom = (paper, selector) => Array
    .from(paper.querySelectorAll(selector))
    .filter((row) => ! row.classList.contains('rm-print-row-head'))
    .map(spanTexts)
    .filter((texts) => texts.length > 0);

const headerLinesFrom = (paper) => {
    const header = paper.querySelector('.rm-print-header');
    const headerRows = header ? spanTexts(header) : [];
    const title = normalizeTicketText(header?.querySelector('strong')?.innerText || 'Rumika SaaS');

    return { title, headerRows };
};

const totalsFrom = (paper) => {
    const totals = paper.querySelector('.rm-print-totals');

    return totals ? spanTexts(totals) : [];
};

const branchNameFrom = (paper) => {
    const subtitle = paper
        .closest('.rm-print-preview-modal')
        ?.querySelector('.rm-modal-subtitle')
        ?.innerText || '';
    const [branch] = subtitle.split(' - ');

    return normalizeTicketText(branch || 'Rumika');
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

        if (!paper || !useQz) {
            printWithBrowser();
            return;
        }

        try {

            const qz = await connectQz();

            const printer = await qz.printers.find(printerName);

            const config = qz.configs.create(printer, {
                copies: 1,
                margins: 0,
                rasterize: false,
            });

            const ticket = ticketTextFrom(paper);

            console.log('TICKET A IMPRIMIR:');
            console.log(ticket);

            await qz.print(config, [{
                type: 'raw',
                format: 'plain',
                data: ticket,
            }]);

        } catch (error) {

            window.alert(
                `No se pudo imprimir con QZ Tray. ` +
                `Verifica que QZ Tray esté abierto y que la impresora "${printerName}" exista.`
            );

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
