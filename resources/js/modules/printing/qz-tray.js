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
                    new Error(
                        'QZ Tray no cargo correctamente.'
                    )
                );
            }
        };

        script.onerror = () => {
            reject(
                new Error(
                    'No se pudo cargar QZ Tray.'
                )
            );
        };

        document.head.appendChild(script);
    });

    return qzLoading;
};


// ==========================================================
// CONECTAR CON QZ TRAY
// ==========================================================

const connectQz = async () => {
    const qz = await loadQz();

    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    return qz;
};


// ==========================================================
// DECODIFICAR EL TICKET QUE VIENE DESDE PHP
// ==========================================================

const decodeTicket = (base64 = '') => {
    if (!base64) {
        return '';
    }

    try {
        const binary = atob(base64);

        const bytes = Uint8Array.from(
            binary,
            (character) => character.charCodeAt(0)
        );

        return new TextDecoder('utf-8').decode(bytes);

    } catch (error) {
        console.error(
            'No se pudo decodificar el ticket:',
            error
        );

        return '';
    }
};


// ==========================================================
// IMPRESION CON NAVEGADOR COMO RESPALDO
// ==========================================================

const printWithBrowser = () => {
    window.print();
};


// ==========================================================
// RUMIKA QZ
// ==========================================================

window.RumikaQz = {

    async printFromButton(button) {

        // --------------------------------------------------
        // CONFIGURACION RECIBIDA DESDE BLADE
        // --------------------------------------------------

        const printerName =
            button.dataset.printerName || '';

        const useQz =
            button.dataset.useQz === '1'
            && printerName !== '';

        const ticketBase64 =
            button.dataset.ticket || '';


        // --------------------------------------------------
        // DECODIFICAR TICKET GENERADO POR PHP
        // --------------------------------------------------

        const ticket =
            decodeTicket(ticketBase64);


        console.log(
            '=============================='
        );

        console.log(
            'RUMIKA - QZ TRAY'
        );

        console.log(
            'Impresora:',
            printerName
        );

        console.log(
            'Usar QZ:',
            useQz
        );

        console.log(
            'Ticket recibido desde PHP:'
        );

        console.log(ticket);

        console.log(
            '=============================='
        );


        // --------------------------------------------------
        // VALIDAR TICKET
        // --------------------------------------------------

        if (!ticket) {
            console.error(
                'No existe contenido para imprimir.'
            );

            window.alert(
                'No existe contenido para imprimir.'
            );

            return;
        }


        // --------------------------------------------------
        // SI NO ESTA HABILITADO QZ
        // --------------------------------------------------

        if (!useQz) {
            console.warn(
                'QZ Tray no esta habilitado. ' +
                'Se utilizara la impresion del navegador.'
            );

            printWithBrowser();

            return;
        }


        try {

            // --------------------------------------------------
            // CONECTAR
            // --------------------------------------------------

            const qz =
                await connectQz();


            // --------------------------------------------------
            // BUSCAR IMPRESORA
            // --------------------------------------------------

            const printer =
                await qz.printers.find(
                    printerName
                );


            console.log(
                'Impresora encontrada:',
                printer
            );


            // --------------------------------------------------
            // CONFIGURACION DE IMPRESION
            // --------------------------------------------------

            const config =
                qz.configs.create(
                    printer,
                    {
                        copies: 1,
                        margins: 0,
                        rasterize: false,
                    }
                );


            // --------------------------------------------------
            // ENVIAR TEXTO DIRECTAMENTE A LA IMPRESORA
            // --------------------------------------------------

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
                'Ticket enviado correctamente a QZ Tray.'
            );

        } catch (error) {

            console.error(
                'ERROR QZ TRAY:',
                error
            );


            window.alert(
                `No se pudo imprimir con QZ Tray.\n\n` +
                `Verifica que QZ Tray este abierto.\n` +
                `Impresora: ${printerName}`
            );
        }
    },
};


// ==========================================================
// IMPRESION AUTOMATICA
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
                        'No se encontro el boton ' +
                        '.rm-auto-print-ticket'
                    );

                    return;
                }


                button.click();

            },
            350
        );
    }
);
