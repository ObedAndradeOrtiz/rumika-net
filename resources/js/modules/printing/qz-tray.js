const qzCdn =
    'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js';

let qzLoading = null;


// ==========================================================
// CARGAR QZ
// ==========================================================

const loadQz = () => {

    if (window.qz) {
        return Promise.resolve(
            window.qz
        );
    }


    if (qzLoading) {
        return qzLoading;
    }


    qzLoading = new Promise(
        (resolve, reject) => {

            const script =
                document.createElement(
                    'script'
                );


            script.src = qzCdn;

            script.async = true;


            script.onload = () => {

                if (window.qz) {

                    resolve(
                        window.qz
                    );

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


            document.head.appendChild(
                script
            );
        }
    );


    return qzLoading;
};


// ==========================================================
// CONECTAR QZ
// ==========================================================

const connectQz = async () => {

    const qz =
        await loadQz();


    if (
        !qz.websocket.isActive()
    ) {

        await qz.websocket.connect();
    }


    return qz;
};


// ==========================================================
// DECODIFICAR BASE64
// ==========================================================

const decodeTicket = (
    base64
) => {

    try {

        const binary =
            atob(base64);


        const bytes =
            Uint8Array.from(
                binary,
                character =>
                    character.charCodeAt(0)
            );


        return new TextDecoder(
            'utf-8'
        ).decode(bytes);

    } catch (error) {

        console.error(
            'No se pudo decodificar el ticket:',
            error
        );


        return '';
    }
};


// ==========================================================
// IMPRESION
// ==========================================================

window.RumikaQz = {

    async printFromButton(
        button
    ) {

        const printerName =
            button.dataset.printerName
            || '';


        const useQz =
            button.dataset.useQz
                === '1'
            &&
            printerName !== '';


        const ticketBase64 =
            button.dataset.ticket
            || '';


        console.log(
            '=============================='
        );

        console.log(
            'RUMIKA QZ'
        );

        console.log(
            'Impresora:',
            printerName
        );

        console.log(
            'Usar QZ:',
            useQz
        );


        /*
        |--------------------------------------------------------------------------
        | TICKET YA GENERADO POR PHP
        |--------------------------------------------------------------------------
        */

        const ticket =
            decodeTicket(
                ticketBase64
            );


        console.log(
            'TICKET GENERADO POR PHP:'
        );

        console.log(ticket);

        console.log(
            '=============================='
        );


        if (!ticket) {

            alert(
                'No existe contenido para imprimir.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | SI NO ESTA CONFIGURADO QZ
        |--------------------------------------------------------------------------
        */

        if (!useQz) {

            console.warn(
                'QZ no esta habilitado.'
            );

            window.print();

            return;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | CONECTAR
            |--------------------------------------------------------------------------
            */

            const qz =
                await connectQz();


            /*
            |--------------------------------------------------------------------------
            | BUSCAR IMPRESORA
            |--------------------------------------------------------------------------
            */

            const printer =
                await qz.printers.find(
                    printerName
                );


            console.log(
                'Impresora encontrada:',
                printer
            );


            /*
            |--------------------------------------------------------------------------
            | CONFIGURACION
            |--------------------------------------------------------------------------
            */

            const config =
                qz.configs.create(
                    printer,
                    {
                        copies: 1,
                        margins: 0,
                        rasterize: false,
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | MANDAR DIRECTAMENTE EL TEXTO GENERADO EN PHP
            |--------------------------------------------------------------------------
            */

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


            alert(
                `No se pudo imprimir con QZ Tray.\n\n`
                +
                `Impresora: ${printerName}`
            );
        }
    }
};


// ==========================================================
// AUTO PRINT
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
                    return;
                }


                button.click();

            },
            350
        );
    }
);
