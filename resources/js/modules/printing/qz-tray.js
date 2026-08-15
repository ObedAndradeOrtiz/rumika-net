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
    .filter((row) => !row.classList.contains('rm-print-row-head'))
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

const ticketTextFrom = (paper) => {
    const { title, headerRows } = headerLinesFrom(paper);
    const branchName = branchNameFrom(paper);
    const sections = Array.from(paper.querySelectorAll('.rm-print-section'));
    const pendingSection = sections
        .find((section) => normalizeTicketText(section.querySelector('h3')?.innerText).toLowerCase().includes('saldos'));
    const detailSections = sections
        .filter((section) => section !== pendingSection)
        .map((section) => ({
            title: normalizeTicketText(section.querySelector('h3')?.innerText || 'Detalle').toUpperCase(),
            rows: Array.from(section.querySelectorAll('.rm-print-row'))
                .filter((row) => !row.classList.contains('rm-print-row-head'))
                .map(spanTexts)
                .filter((texts) => texts.length > 0),
        }))
        .filter((section) => section.rows.length > 0);
    const pendingRows = pendingSection
        ? Array.from(pendingSection.querySelectorAll('.rm-print-row'))
            .filter((row) => !row.classList.contains('rm-print-row-head'))
            .map(spanTexts)
        : [];
    const totals = totalsFrom(paper);
    const output = [
        `${esc}@`,
        `${esc}M\x00`,
        `${esc}3\x18`,
        `${esc}a\x01`,
        center(branchName),
        center(title.replace('Rumika - ', '')),
        line(),
        ...headerRows.map(center),
        line(),
        `${esc}a\x00`,
    ];

    if (detailSections.length === 0) {
        output.push('DETALLE', line());
        output.push(center('Sin items'));
    }

    detailSections.forEach((section, sectionIndex) => {
        if (sectionIndex > 0) {
            output.push('');
        }

        output.push(section.title, line());

        section.rows.forEach((texts) => {
            const isCompact = texts.length === 3;
            const [item, totalOrCash, cashOrQr, qrValue] = texts;
            const total = isCompact ? '' : totalOrCash;
            const cash = isCompact ? totalOrCash : cashOrQr;
            const qr = isCompact ? cashOrQr : qrValue;

            wrap(item).forEach((row) => output.push(row));

            if (total && !item.toLowerCase().startsWith('total ')) {
                output.push(right('Total', total));
            }

            if (cash && !cash.endsWith('0.00')) {
                output.push(right('Efectivo', cash));
            }

            if (qr && !qr.endsWith('0.00')) {
                output.push(right('QR', qr));
            }

            output.push(line('-'));
        });
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
        .filter((item) => !item.toLowerCase().startsWith('impresora'))
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
        center('Sistema Rumika SaaS'),
        `${esc}d\x0c`,
        `${gs}V\x00`
    );

    return output.join('\n');
};

const connectQz = async () => {
    const qz = await loadQz();

    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    return qz;
};

const printWithBrowser = () => {
    window.print();
};

const decodeTicket = (base64 = '') => {
    if (!base64) {
        return '';
    }

    try {
        const binary = atob(base64);
        const bytes = Uint8Array.from(
            binary,
            char => char.charCodeAt(0)
        );

        return new TextDecoder('utf-8').decode(bytes);
    } catch (error) {
        console.error('No se pudo decodificar el ticket.', error);

        return '';
    }
};

window.RumikaQz = {
    async printFromButton(button) {
        const modal = button.closest('.rm-print-preview-modal');
        const paper = modal?.querySelector('.rm-print-preview-paper');
        const printerName = button.dataset.printerName || '';
        const useQz = button.dataset.useQz === '1' && printerName !== '';

        if (!paper) {
            printWithBrowser();

            return;
        }

        if (!useQz) {
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

            const rawTicket = decodeTicket(
                button.dataset.ticket || ''
            );

            const printData = rawTicket
                ? rawTicket
                : ticketTextFrom(paper);

            const rawTicket = button.dataset.ticket
                ? atob(button.dataset.ticket)
                : '';

            await qz.print(config, [{
                type: 'raw',
                format: 'plain',
                data: rawTicket || ticketTextFrom(paper),
            }]);
        } catch (error) {
            window.alert(
                `No se pudo imprimir con QZ Tray. Verifica que QZ Tray este abierto y que la impresora "${printerName}" exista.`
            );

            console.error(error);
        }
    },
};

window.addEventListener('rumika-auto-print-ticket', () => {
    window.setTimeout(() => {
        const button = document.querySelector('.rm-auto-print-ticket');

        if (!button) {
            return;
        }

        button.click();
    }, 350);
});


document.addEventListener('livewire:init', () => {
    Livewire.on('imprimir-ticket-caja', async (data) => {
        const payload = Array.isArray(data) ? data[0] : data;

        await imprimirTicketCajaQz(
            payload.texto,
            payload.impresora
        );
    });
});

async function imprimirTicketCajaQz(texto, impresora) {
    try {
        const qz = await connectQz();

        if (! impresora) {
            alert('No hay una impresora configurada.');

            return;
        }

        const impresoras = await qz.printers.find();

        const impresoraEncontrada = impresoras.find(
            nombre =>
                nombre.trim().toLowerCase() ===
                impresora.trim().toLowerCase()
        );

        if (! impresoraEncontrada) {
            alert(
                'La impresora configurada no existe en Windows/QZ.\n\n' +
                'Configurada: ' + impresora +
                '\n\nDetectadas:\n' +
                impresoras.join('\n')
            );

            return;
        }

        const textoLimpio = String(texto ?? '')
            .replace(/\r\n?/g, '\n');

        const config = qz.configs.create(
            impresoraEncontrada,
            {
                copies: 1,
                margins: 0,
                rasterize: false,
            }
        );

        const dataPrint = [
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x40'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x4D\x00'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1D\x21\x00'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x21\x00'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x61\x00'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x33\x18'
            },
            {
                type: 'raw',
                format: 'plain',
                data: textoLimpio
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1B\x64\x03'
            },
            {
                type: 'raw',
                format: 'command',
                data: '\x1D\x56\x01'
            }
        ];

        await qz.print(
            config,
            dataPrint
        );
    } catch (error) {
        console.error(
            'ERROR QZ CAJA:',
            error
        );

        alert(
            'No se pudo imprimir.\n\n' +
            'Verifica que QZ Tray esté abierto y la impresora esté instalada.'
        );
    }
}
