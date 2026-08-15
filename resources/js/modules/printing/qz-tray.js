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


function ticketTextFrom(paper) {
    const LINE_WIDTH = 48;

    const center = (text) => {
        text = String(text || '');
        if (text.length >= LINE_WIDTH) return text.substring(0, LINE_WIDTH);

        const spaces = Math.floor((LINE_WIDTH - text.length) / 2);

        return ' '.repeat(spaces) + text;
    };

    const line = (char = '-') => char.repeat(LINE_WIDTH);

    const clean = (text) => {
        return String(text || '')
            .replace(/\s+/g, ' ')
            .trim();
    };

    const firstTwoNames = (text) => {
    return clean(text)
        .split(' ')
        .slice(0, 2)
        .join(' ');
};

    const column3 = (detail, cash, qr) => {
        detail = firstTwoNames(detail);

        // 24 + 12 + 12 = 48 caracteres
        return detail.substring(0, 24).padEnd(24)
            + clean(cash).substring(0, 12).padStart(12)
            + clean(qr).substring(0, 12).padStart(12);
    };

    let output = '';

    // ============================
    // CABECERA
    // ============================

    const header = paper.querySelector('.rm-print-header');

    if (header) {
        const strong = header.querySelector('strong');

        if (strong) {
            output += center(clean(strong.textContent)) + '\n';
        }

        header.querySelectorAll('span').forEach(span => {
            output += center(clean(span.textContent)) + '\n';
        });
    }

    output += line('=') + '\n';

    // ============================
    // SERVICIOS Y PRODUCTOS
    // ============================

    paper.querySelectorAll('.rm-print-section').forEach(section => {

        const title = section.querySelector('h3');

        if (title) {
            output += '\n';
            output += clean(title.textContent).toUpperCase() + '\n';
            output += line('-') + '\n';
        }

        const rows = section.querySelectorAll('.rm-print-row');

        rows.forEach(row => {
            const spans = Array.from(row.querySelectorAll(':scope > span'));

            if (!spans.length) {
                return;
            }

            if (row.classList.contains('rm-print-row-head')) {
                if (spans.length === 3) {
                    output += column3(
                        spans[0].textContent,
                        spans[1].textContent,
                        spans[2].textContent
                    ) + '\n';

                    output += line('-') + '\n';
                }

                return;
            }

            if (spans.length === 3) {
                output += column3(
                    spans[0].textContent,
                    spans[1].textContent,
                    spans[2].textContent
                ) + '\n';
            }

            // Gastos tiene 4 columnas
            if (spans.length === 4) {
                const name = clean(spans[0].textContent);
                const amount = clean(spans[1].textContent);
                const responsible = firstTwoNames(spans[2].textContent);
                const reference = clean(spans[3].textContent);

                output += name + '\n';
                output += 'Monto: '.padEnd(15) + amount + '\n';
                output += 'Responsable: '.padEnd(15) + responsible + '\n';
                output += 'Ref.: '.padEnd(15) + reference + '\n';
                output += line('-') + '\n';
            }
        });
    });

    // ============================
    // TOTALES
    // ============================

    const totals = paper.querySelector('.rm-print-totals');

    if (totals) {
        output += '\n';
        output += line('=') + '\n';
        output += 'RESUMEN\n';
        output += line('-') + '\n';

        totals.querySelectorAll('span, strong').forEach(item => {
            output += clean(item.textContent) + '\n';
        });
    }

    output += line('=') + '\n';
    output += center('RUMIKA') + '\n';
    output += center('Gracias') + '\n';

    // Espacio para cortar papel
    output += '\n\n\n\n';

    return output;
}

window.addEventListener('rumika-auto-print-ticket', () => {
    window.setTimeout(() => {
        const button = document.querySelector('.rm-auto-print-ticket');

        if (! button) {
            return;
        }

        button.click();
    }, 350);
});
