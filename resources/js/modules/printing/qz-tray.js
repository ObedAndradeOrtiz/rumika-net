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

function ticketTextFrom(paper) {
    const WIDTH = 48;

    const clean = (value = '') => {
        return String(value)
            .replace(/\s+/g, ' ')
            .trim();
    };

    const money = (value = '') => {
        return clean(value)
            .replace(/^Bs\s*/i, '')
            .trim();
    };

    const firstTwoNames = (value = '') => {
        const text = clean(value);

        return text
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .join(' ');
    };

    const separator = () => '-'.repeat(WIDTH);

    const center = (text) => {
        text = clean(text);

        if (text.length >= WIDTH) {
            return text.substring(0, WIDTH);
        }

        const left = Math.floor((WIDTH - text.length) / 2);

        return ' '.repeat(left) + text;
    };

    const normalRow = (detail, cash, qr) => {
        /*
        48 caracteres aproximadamente:

        26 detalle
        11 efectivo
        11 QR
        */

        detail = clean(detail).substring(0, 26);
        cash = money(cash).substring(0, 11);
        qr = money(qr).substring(0, 11);

        return detail.padEnd(26)
            + cash.padStart(11)
            + qr.padStart(11);
    };

    const totalRow = (detail, cash, qr) => {
        detail = clean(detail).substring(0, 26);
        cash = money(cash).substring(0, 11);
        qr = money(qr).substring(0, 11);

        return detail.padEnd(26)
            + cash.padStart(11)
            + qr.padStart(11);
    };

    let output = '';

    // ==================================================
    // CABECERA
    // ==================================================

    const header = paper.querySelector('.rm-print-header');

    if (header) {
        const title = header.querySelector('strong');

        if (title) {
            output += center(title.textContent) + '\n';
        }

        header.querySelectorAll('span').forEach(span => {
            output += center(span.textContent) + '\n';
        });
    }

    output += separator() + '\n';

    // ==================================================
    // SERVICIOS Y PRODUCTOS
    // ==================================================

    const sections = paper.querySelectorAll('.rm-print-section');

    sections.forEach(section => {
        const titleElement = section.querySelector('h3');

        if (!titleElement) {
            return;
        }

        const title = clean(titleElement.textContent);

        output += '\n';
        output += title.toUpperCase() + '\n';
        output += separator() + '\n';

        const rows = section.querySelectorAll('.rm-print-row');

        rows.forEach(row => {
            const spans = Array.from(
                row.querySelectorAll(':scope > span')
            );

            if (!spans.length) {
                return;
            }

            /*
             * CABECERA:
             * Detalle / Efectivo / QR
             */
            if (row.classList.contains('rm-print-row-head')) {

                if (spans.length === 3) {
                    output += normalRow(
                        'Detalle',
                        'Efec.',
                        'QR'
                    ) + '\n';

                    output += separator() + '\n';
                }

                return;
            }

            /*
             * TOTAL
             */
            if (row.classList.contains('rm-print-row-total')) {

                if (spans.length === 3) {
                    output += separator() + '\n';

                    output += totalRow(
                        spans[0].textContent,
                        spans[1].textContent,
                        spans[2].textContent
                    ) + '\n';
                }

                return;
            }

            /*
             * FILA NORMAL DE SERVICIO / PRODUCTO
             */
            if (spans.length === 3) {

                let detail = spans[0].textContent;

                /*
                 * Si es SERVICIOS, mostramos máximo
                 * los 2 primeros nombres.
                 */
                if (
                    title.toLowerCase().includes('servicio')
                ) {
                    detail = firstTwoNames(detail);
                }

                output += normalRow(
                    detail,
                    spans[1].textContent,
                    spans[2].textContent
                ) + '\n';
            }

            /*
             * GASTOS
             */
            if (spans.length === 4) {
                const detail = clean(spans[0].textContent);
                const amount = clean(spans[1].textContent);
                const responsible = firstTwoNames(
                    spans[2].textContent
                );

                output += detail + '\n';
                output += 'Monto: ' + amount + '\n';
                output += 'Resp.: ' + responsible + '\n';

                if (clean(spans[3].textContent) !== '-') {
                    output += 'Ref.: ' +
                        clean(spans[3].textContent) +
                        '\n';
                }

                output += separator() + '\n';
            }
        });

        const empty = section.querySelector('.rm-print-empty');

        if (empty) {
            output += clean(empty.textContent) + '\n';
        }
    });

    // ==================================================
    // TOTALES
    // ==================================================

    const totals = paper.querySelector('.rm-print-totals');

    if (totals) {
        output += '\n';
        output += 'TOTALES\n';
        output += separator() + '\n';

        totals.querySelectorAll('span, strong').forEach(item => {
            let text = clean(item.textContent);

            /*
             * Separamos:
             *
             * Efectivo Bs 2500.00
             *
             * en:
             *
             * Efectivo                  Bs 2500.00
             */

            const match = text.match(/^(.*?)\s+(Bs\s*-?[\d.,]+)$/i);

            if (match) {
                const label = clean(match[1]);
                const value = clean(match[2]);

                output += label.substring(0, 27).padEnd(27)
                    + value.substring(0, 21).padStart(21)
                    + '\n';
            } else {
                output += text + '\n';
            }
        });
    }

    output += separator() + '\n';
    output += center('Sistema Rumika SaaS') + '\n';
    output += '\n\n\n\n';

    return output;
}

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
