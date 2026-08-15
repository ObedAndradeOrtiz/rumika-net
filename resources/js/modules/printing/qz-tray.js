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

    return `${' '.repeat(left)}${text}`;
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

const ticketTextFrom = (paper) => {
    const { title, headerRows } = headerLinesFrom(paper);
    const sections = Array.from(paper.querySelectorAll('.rm-print-section'));
    const detailRows = sections[0]
        ? Array.from(sections[0].querySelectorAll('.rm-print-row'))
            .filter((row) => ! row.classList.contains('rm-print-row-head'))
            .map(spanTexts)
            .filter((texts) => texts.length > 0)
        : [];
    const pendingSection = sections
        .find((section) => normalizeTicketText(section.querySelector('h3')?.innerText).toLowerCase().includes('saldos'));
    const pendingRows = pendingSection
        ? Array.from(pendingSection.querySelectorAll('.rm-print-row'))
            .filter((row) => ! row.classList.contains('rm-print-row-head'))
            .map(spanTexts)
        : [];
    const totals = totalsFrom(paper);
    const output = [
        `${esc}@`,
        `${esc}a\x01`,
        `${esc}!\x10`,
        center('Rumika SaaS'),
        `${esc}!\x00`,
        center(title.replace('Rumika - ', '')),
        line(),
        ...headerRows.map(center),
        line(),
        `${esc}a\x00`,
        'DETALLE',
        line(),
    ];

    if (detailRows.length === 0) {
        output.push(center('Sin items'));
    }

    detailRows.forEach(([item, total, cash, qr]) => {
        wrap(item).forEach((row) => output.push(row));
        output.push(right('Total', total));

        if (! cash.endsWith('0.00')) {
            output.push(right('Efectivo', cash));
        }

        if (! qr.endsWith('0.00')) {
            output.push(right('QR', qr));
        }

        output.push(line('-'));
    });

    if (pendingRows.length > 0) {
        output.push('', 'SALDOS PENDIENTES', line());
        pendingRows.forEach(([item, total, paid, balance]) => {
            wrap(item).forEach((row) => output.push(row));
            output.push(right('Total', total));
            output.push(right('Pagado', paid));
            output.push(right('Saldo', balance));
            output.push(line('-'));
        });
    }

    output.push('', 'TOTALES', line());
    totals
        .filter((item) => ! item.toLowerCase().startsWith('impresora'))
        .forEach((item) => {
            const parts = item.match(/^(.*?)(Bs\s.*)$/i);

            if (parts) {
                output.push(right(parts[1].trim(), parts[2].trim()));
            } else {
                output.push(center(item));
            }
        });

    output.push(
        line(),
        `${esc}a\x01`,
        center('Gracias por su visita'),
        center('Rumika SaaS'),
        '',
        '',
        '',
        `${gs}V\x00`
    );

    return output.join('\n');
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
                rasterize: false,
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
