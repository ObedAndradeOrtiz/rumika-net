const qzCdn = 'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js';

let qzLoading = null;

// ==========================================================
// CARGAR QZ TRAY
// ==========================================================

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

        script.onload = () => {
            if (window.qz) {
                resolve(window.qz);
            } else {
                reject(
                    new Error('QZ Tray no cargo correctamente.')
                );
            }
        };

        script.onerror = () => {
            reject(
                new Error('No se pudo cargar QZ Tray.')
            );
        };

        document.head.appendChild(script);
    });

    return qzLoading;
};


// ==========================================================
// CONFIGURACION DEL TICKET
// ==========================================================

// Para 80 mm normalmente 42 funciona bien.
// Si ves que sobra mucho espacio puedes probar 46 o 48.
const paperWidth = 42;


// ==========================================================
// LIMPIAR TEXTO
// ==========================================================

const normalizeTicketText = (value = '') => {
    return String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\x20-\x7E\n]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
};


// ==========================================================
// LINEA
// ==========================================================

const line = (char = '-') => {
    return char.repeat(paperWidth);
};


// ==========================================================
// CENTRAR TEXTO
// ==========================================================

const center = (value = '') => {
    const text = normalizeTicketText(value)
        .slice(0, paperWidth);

    const left = Math.max(
        0,
        Math.floor(
            (paperWidth - text.length) / 2
        )
    );

    return `${' '.repeat(left)}${text}`;
};


// ==========================================================
// OBTENER DOS PRIMEROS NOMBRES
// ==========================================================

const firstTwoNames = (value = '') => {
    return normalizeTicketText(value)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .join(' ');
};


// ==========================================================
// CONVERTIR TEXTO MONETARIO A NUMERO
// ==========================================================

const parseMoney = (value = '') => {
    let text = normalizeTicketText(value)
        .replace(/Bs/gi, '')
        .replace(/\s/g, '')
        .trim();

    /*
     * Ejemplos:
     *
     * 150.00
     * 1,250.00
     * 250
     */

    text = text.replace(/,/g, '');

    const number = Number.parseFloat(text);

    return Number.isFinite(number)
        ? number
        : 0;
};


// ==========================================================
// FORMATEAR DINERO
// ==========================================================

const formatMoney = (value = 0) => {
    return `Bs ${Number(value || 0).toFixed(2)}`;
};


// ==========================================================
// NOMBRE A LA IZQUIERDA + PRECIO A LA DERECHA
// ==========================================================

const nameAndPrice = (name, price) => {
    const priceText = formatMoney(price);

    /*
     * Dejamos al menos un espacio
     * entre nombre y precio.
     */

    const maxNameWidth = Math.max(
        1,
        paperWidth - priceText.length - 1
    );

    let nameText = normalizeTicketText(name)
        .substring(0, maxNameWidth);

    const spaces = Math.max(
        1,
        paperWidth -
            nameText.length -
            priceText.length
    );

    return (
        nameText +
        ' '.repeat(spaces) +
        priceText
    );
};


// ==========================================================
// CONECTAR QZ
// ==========================================================

const connectQz = async () => {
    const qz = await loadQz();

    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    return qz;
};


// ==========================================================
// IMPRESION DEL NAVEGADOR
// ==========================================================

const printWithBrowser = () => {
    window.print();
};


// ==========================================================
// CREAR EL TEXTO DEL TICKET
// ==========================================================

function ticketTextFrom(paper) {

    let output = '';


    // ======================================================
    // CABECERA
    // ======================================================

    const header = paper.querySelector(
        '.rm-print-header'
    );

    if (header) {

        const title = header.querySelector(
            'strong'
        );

        if (title) {
            output += center(
                title.textContent
            ) + '\n';
        }


        header
            .querySelectorAll('span')
            .forEach((span) => {

                const text =
                    normalizeTicketText(
                        span.textContent
                    );

                if (!text) {
                    return;
                }

                output += center(text) + '\n';
            });
    }


    output += line('-') + '\n';


    // ======================================================
    // SECCIONES
    // ======================================================

    const sections = paper.querySelectorAll(
        '.rm-print-section'
    );


    sections.forEach((section) => {

        const titleElement =
            section.querySelector('h3');


        if (!titleElement) {
            return;
        }


        const sectionTitle =
            normalizeTicketText(
                titleElement.textContent
            );


        const sectionTitleLower =
            sectionTitle.toLowerCase();


        // ==================================================
        // GASTOS
        // ==================================================

        if (
            sectionTitleLower.includes('gastos')
        ) {

            output += '\n';

            output +=
                sectionTitle.toUpperCase() +
                '\n';

            output += line('-') + '\n';


            const rows =
                section.querySelectorAll(
                    '.rm-print-row'
                );


            rows.forEach((row) => {

                if (
                    row.classList.contains(
                        'rm-print-row-head'
                    )
                ) {
                    return;
                }


                const spans = Array.from(
                    row.querySelectorAll(
                        ':scope > span'
                    )
                );


                if (spans.length < 2) {
                    return;
                }


                const name =
                    normalizeTicketText(
                        spans[0].textContent
                    );


                const amount =
                    normalizeTicketText(
                        spans[1].textContent
                    );


                output += name + '\n';

                output +=
                    `Monto: ${amount}` +
                    '\n';


                if (spans[2]) {

                    const responsible =
                        normalizeTicketText(
                            spans[2].textContent
                        );


                    if (responsible) {

                        output +=
                            `Resp: ${firstTwoNames(
                                responsible
                            )}` +
                            '\n';
                    }
                }


                output += '\n';
            });


            return;
        }


        // ==================================================
        // SERVICIOS / PRODUCTOS
        // ==================================================

        output += '\n';

        output +=
            sectionTitle.toUpperCase() +
            '\n';

        output += line('-') + '\n';


        const rows =
            section.querySelectorAll(
                '.rm-print-row'
            );


        rows.forEach((row) => {

            /*
             * Ignoramos:
             *
             * Detalle | Efectivo | QR
             */

            if (
                row.classList.contains(
                    'rm-print-row-head'
                )
            ) {
                return;
            }


            const spans = Array.from(
                row.querySelectorAll(
                    ':scope > span'
                )
            );


            /*
             * Para servicios y productos:
             *
             * span 0 = nombre
             * span 1 = efectivo
             * span 2 = QR
             */

            if (spans.length !== 3) {
                return;
            }


            let name =
                normalizeTicketText(
                    spans[0].textContent
                );


            const cash =
                parseMoney(
                    spans[1].textContent
                );


            const qr =
                parseMoney(
                    spans[2].textContent
                );


            /*
             * PRECIO FINAL
             *
             * efectivo + QR
             */

            const total = cash + qr;


            // ==================================================
            // TOTAL DE LA SECCION
            // ==================================================

            if (
                row.classList.contains(
                    'rm-print-row-total'
                )
            ) {

                output += line('-') + '\n';

                output += nameAndPrice(
                    name,
                    total
                ) + '\n';

                return;
            }


            // ==================================================
            // PACIENTE
            // ==================================================

            if (
                sectionTitleLower.includes(
                    'servicio'
                )
            ) {

                /*
                 * Ejemplo:
                 *
                 * ANGEL MARCELO ZAMORANO MERCADO
                 *
                 * se convierte en:
                 *
                 * ANGEL MARCELO
                 */

                name = firstTwoNames(name);
            }


            // ==================================================
            // IMPRIMIR REGISTRO
            // ==================================================

            output += nameAndPrice(
                name,
                total
            ) + '\n';
        });


        // ==================================================
        // SECCION VACIA
        // ==================================================

        const empty =
            section.querySelector(
                '.rm-print-empty'
            );


        if (empty) {

            output +=
                normalizeTicketText(
                    empty.textContent
                ) +
                '\n';
        }
    });


    // ======================================================
    // TOTALES GENERALES
    // ======================================================

    const totals =
        paper.querySelector(
            '.rm-print-totals'
        );


    if (totals) {

        output += '\n';

        output += 'TOTALES\n';

        output += line('-') + '\n';


        totals
            .querySelectorAll(
                'span, strong'
            )
            .forEach((item) => {

                const text =
                    normalizeTicketText(
                        item.textContent
                    );


                if (!text) {
                    return;
                }


                /*
                 * Intenta separar:
                 *
                 * Efectivo Bs 2500.00
                 *
                 * en:
                 *
                 * Efectivo            Bs 2500.00
                 */

                const match = text.match(
                    /^(.*?)\s+(Bs\s*-?[\d,.]+)$/i
                );


                if (match) {

                    const label =
                        normalizeTicketText(
                            match[1]
                        );


                    const value =
                        normalizeTicketText(
                            match[2]
                        );


                    const spaces =
                        Math.max(
                            1,
                            paperWidth -
                                label.length -
                                value.length
                        );


                    output +=
                        label +
                        ' '.repeat(spaces) +
                        value +
                        '\n';

                } else {

                    output += text + '\n';
                }
            });
    }


    // ======================================================
    // PIE
    // ======================================================

    output += line('-') + '\n';

    output +=
        center(
            'Sistema Rumika SaaS'
        ) +
        '\n';


    /*
     * Espacio antes de cortar
     */

    output += '\n\n\n\n';


    return output;
}


// ==========================================================
// OBJETO GLOBAL RUMIKA QZ
// ==========================================================

window.RumikaQz = {

    async printFromButton(button) {

        // ==================================================
        // BUSCAR MODAL
        // ==================================================

        const modal = button.closest(
            '.rm-print-preview-modal'
        );


        // ==================================================
        // BUSCAR TICKET
        // ==================================================

        const paper =
            modal?.querySelector(
                '.rm-print-preview-paper'
            );


        // ==================================================
        // CONFIGURACION DEL BOTON
        // ==================================================

        const printerName =
            button.dataset.printerName || '';


        const useQz =
            button.dataset.useQz === '1' &&
            printerName !== '';


        // ==================================================
        // DEBUG
        // ==================================================

        console.log(
            '================================'
        );

        console.log(
            'RUMIKA - IMPRESION'
        );

        console.log(
            'printerName:',
            printerName
        );

        console.log(
            'data-use-qz:',
            button.dataset.useQz
        );

        console.log(
            'useQz:',
            useQz
        );

        console.log(
            'paper:',
            paper
        );

        console.log(
            '================================'
        );


        // ==================================================
        // NO HAY TICKET
        // ==================================================

        if (!paper) {

            console.warn(
                'No se encontro .rm-print-preview-paper'
            );

            printWithBrowser();

            return;
        }


        // ==================================================
        // NO ESTA HABILITADO QZ
        // ==================================================

        if (!useQz) {

            console.warn(
                'QZ NO ESTA HABILITADO.'
            );

            console.warn(
                'Se utilizara window.print()'
            );

            printWithBrowser();

            return;
        }


        try {

            // ==================================================
            // CONECTAR
            // ==================================================

            const qz =
                await connectQz();


            // ==================================================
            // BUSCAR IMPRESORA
            // ==================================================

            const printer =
                await qz.printers.find(
                    printerName
                );


            console.log(
                'Impresora encontrada:',
                printer
            );


            // ==================================================
            // CONFIGURACION
            // ==================================================

            const config =
                qz.configs.create(
                    printer,
                    {
                        copies: 1,
                        margins: 0,
                        rasterize: false,
                    }
                );


            // ==================================================
            // GENERAR TICKET
            // ==================================================

            const ticket =
                ticketTextFrom(
                    paper
                );


            // ==================================================
            // VER EXACTAMENTE LO QUE SE IMPRIMIRA
            // ==================================================

            console.log(
                '================================'
            );

            console.log(
                'TICKET ENVIADO A QZ'
            );

            console.log(
                '================================'
            );

            console.log(ticket);

            console.log(
                '================================'
            );


            // ==================================================
            // IMPRIMIR
            // ==================================================

            await qz.print(
                config,
                [
                    {
                        type: 'raw',
                        format: 'plain',
                        data: ticket,
                    }
                ]
            );


            console.log(
                'Ticket enviado correctamente.'
            );

        } catch (error) {

            console.error(
                'ERROR QZ:',
                error
            );


            window.alert(
                `No se pudo imprimir con QZ Tray.\n\n` +
                `Verifica que QZ Tray este abierto y que la impresora "${printerName}" exista.`
            );
        }
    },
};


// ==========================================================
// AUTO IMPRESION
// ==========================================================

window.addEventListener(
    'rumika-auto-print-ticket',
    () => {

        window.setTimeout(
            () => {

                const button =
                    document.querySelector(
                        '.rm-auto-print-ticket'
                    );


                if (!button) {

                    console.warn(
                        'No se encontro .rm-auto-print-ticket'
                    );

                    return;
                }


                button.click();

            },
            350
        );
    }
);
