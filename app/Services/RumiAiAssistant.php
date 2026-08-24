<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\InventoryProductBatch;
use App\Models\ProductSale;
use App\Models\TreatmentPayment;
use App\Models\User;
use App\Support\Money;
use App\Support\RumikaAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RumiAiAssistant
{
    private const MODULES = [
        'inicio' => ['label' => 'Inicio', 'route' => 'dashboard'],
        'agenda' => ['label' => 'Agenda', 'route' => 'clinic.agenda'],
        'clientes' => ['label' => 'Clientes', 'route' => 'clinic.clients'],
        'historia_clinica' => ['label' => 'Historia clinica', 'route' => 'clinic.clinical-history'],
        'ventas_productos' => ['label' => 'Ventas', 'route' => 'sales.products'],
        'crm' => ['label' => 'Centro de mensajes', 'route' => 'crm.index'],
        'inventario' => ['label' => 'Inventario', 'route' => 'inventory.index'],
        'inventario_operaciones' => ['label' => 'Operaciones de inventario', 'route' => 'inventory.operations'],
        'caja' => ['label' => 'Caja', 'route' => 'clinic.cashbox'],
        'gastos' => ['label' => 'Gastos', 'route' => 'finance.expenses'],
        'facturacion' => ['label' => 'Facturacion', 'route' => 'finance.invoicing'],
        'deudas' => ['label' => 'Deudas', 'route' => 'finance.debts'],
        'reportes' => ['label' => 'Reportes', 'route' => 'finance.reports'],
        'comisiones' => ['label' => 'Comisiones', 'route' => 'finance.commissions'],
        'resumen_financiero' => ['label' => 'Resumen financiero', 'route' => 'finance.summary'],
        'estadisticas' => ['label' => 'Estadisticas', 'route' => 'statistics.index'],
        'sucursales' => ['label' => 'Sucursales', 'route' => 'settings.commerce'],
        'servicios' => ['label' => 'Servicios', 'route' => 'settings.services'],
        'registros' => ['label' => 'Registros', 'route' => 'settings.records'],
        'bitacora' => ['label' => 'Bitacora', 'route' => 'settings.audit'],
        'usuarios' => ['label' => 'Usuarios y roles', 'route' => 'settings.users'],
        'roles' => ['label' => 'Usuarios y roles', 'route' => 'settings.users'],
        'mi_sistema' => ['label' => 'Mi sistema', 'route' => 'settings.system'],
    ];

    public function answer(User $user, string $question): array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return $this->response('Primero necesitas estar asociado a una empresa para que pueda ayudarte dentro de Rumika.');
        }

        $branch = $this->activeBranch($user, $company);
        $question = trim($question);
        $normalized = Str::of($question)->lower()->ascii()->toString();

        $local = $this->localAnswer($user, $company, $branch, $normalized);

        if ($local) {
            return $local;
        }

        $context = $this->allowedContext($user, $company, $branch);
        $ai = $this->askConfiguredProvider($question, $context);

        if ($ai) {
            return $this->response($ai, $this->suggestedActions($user, $normalized));
        }

        return $this->response(
            'Puedo ayudarte a ubicar pantallas, abrir acciones seguras y resumir datos segun tu rol. Prueba con: "donde cierro caja", "resumen de hoy" o "que puedo hacer con mi rol".',
            $this->suggestedActions($user, $normalized)
        );
    }

    public function action(string $key, User $user): ?array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return null;
        }

        $map = [
            'open_branch' => ['type' => 'click', 'selector' => '.rm-branch-switcher-button'],
            'open_more' => ['type' => 'click', 'selector' => '[data-mobile-more-toggle]'],
            'open_quick_cashbox' => ['type' => 'click', 'selector' => '.rm-top-wallet-button', 'module' => 'caja'],
            'new_appointment' => ['type' => 'safe_route_action', 'route' => 'clinic.agenda', 'query' => 'new_appointment', 'selector' => '[data-rumi-action="new-appointment"]', 'module' => 'agenda', 'action' => 'create'],
            'new_client' => ['type' => 'safe_route_action', 'route' => 'clinic.clients', 'query' => 'new_client', 'selector' => '[data-rumi-action="new-client"]', 'module' => 'clientes', 'action' => 'create'],
        ];

        foreach (self::MODULES as $module => $data) {
            $map['go_' . $module] = ['type' => 'route', 'route' => $data['route'], 'module' => $module];
        }

        $action = $map[$key] ?? null;

        if (! $action) {
            return null;
        }

        $module = $action['module'] ?? null;
        $neededAction = $action['action'] ?? 'view';

        if ($module && ! $this->can($user, $module, $neededAction, $company)) {
            return [
                'type' => 'message',
                'message' => 'Tu rol no tiene permiso para ejecutar esa accion.',
            ];
        }

        if (($action['type'] ?? null) === 'safe_route_action') {
            if (request()->routeIs($action['route'])) {
                return ['type' => 'click', 'selector' => $action['selector']];
            }

            return [
                'type' => 'route',
                'url' => route($action['route'], ['rumi_action' => $action['query']]),
            ];
        }

        if (($action['type'] ?? null) === 'route') {
            return ['type' => 'route', 'url' => route($action['route'])];
        }

        return $action;
    }

    private function localAnswer(User $user, Company $company, ?Branch $branch, string $normalized): ?array
    {
        if ($this->isSalesQuestion($normalized)) {
            return $this->salesSummary($user, $company, $branch, $normalized);
        }

        if (str_contains($normalized, 'resumen') || str_contains($normalized, 'hoy') || str_contains($normalized, 'como vamos')) {
            return $this->todaySummary($user, $company, $branch);
        }

        if (str_contains($normalized, 'rol') || str_contains($normalized, 'permiso') || str_contains($normalized, 'puedo hacer')) {
            return $this->roleSummary($user, $company);
        }

        $help = $this->helpAnswer($user, $company, $normalized);

        if ($help) {
            return $help;
        }

        $navigation = [
            'agenda' => ['agenda', 'cita', 'agendar'],
            'clientes' => ['cliente', 'paciente'],
            'historia_clinica' => ['historia', 'ficha', 'receta', 'archivo clinico'],
            'caja' => ['caja', 'cobro', 'ticket', 'cierre'],
            'ventas_productos' => ['venta', 'vender producto', 'farmacia'],
            'reportes' => ['reporte', 'pdf', 'gerente'],
            'gastos' => ['gasto', 'egreso'],
            'inventario' => ['inventario', 'stock', 'producto'],
            'inventario_operaciones' => ['operacion', 'entrada', 'salida', 'traspaso', 'desecho'],
            'crm' => ['whatsapp', 'mensaje', 'crm', 'bandeja'],
            'servicios' => ['servicio', 'tratamiento', 'paquete'],
            'usuarios' => ['usuario', 'personal'],
            'roles' => ['rol', 'roles'],
            'mi_sistema' => ['plan', 'mi sistema', 'pago mensual'],
        ];

        foreach ($navigation as $module => $words) {
            foreach ($words as $word) {
                if (str_contains($normalized, $word)) {
                    return $this->navigationAnswer($user, $company, $module);
                }
            }
        }

        return null;
    }

    private function helpAnswer(User $user, Company $company, string $normalized): ?array
    {
        $topics = $this->helpTopics();
        $priorityModule = null;

        if (str_contains($normalized, 'dar de baja')
            || str_contains($normalized, 'baja')
            || str_contains($normalized, 'desecho')
            || str_contains($normalized, 'ajuste')
            || str_contains($normalized, 'entrada')
            || str_contains($normalized, 'salida')
            || str_contains($normalized, 'traspaso')
            || str_contains($normalized, 'gabinete')) {
            $priorityModule = 'inventario_operaciones';
        }

        if (str_contains($normalized, 'vender producto')
            || str_contains($normalized, 'venta directa')
            || str_contains($normalized, 'ventas directas')
            || str_contains($normalized, 'farmacia')
            || str_contains($normalized, 'comprador')
            || str_contains($normalized, 'consumidor final')) {
            $priorityModule = 'ventas_productos';
        }

        if ($priorityModule && isset($topics[$priorityModule])) {
            $topic = $topics[$priorityModule];

            if (! $this->can($user, $priorityModule, company: $company)) {
                return $this->response("Tu rol no tiene permiso para ver {$topic['label']}. Si necesitas usarlo, pide a administracion que te habilite esa vista en Usuarios y roles.");
            }

            return $this->response(
                $topic['text'],
                $this->topicActions($user, $company, $priorityModule)
            );
        }

        foreach ($topics as $module => $topic) {
            foreach ($topic['keywords'] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    if (! $this->can($user, $module, company: $company)) {
                        return $this->response("Tu rol no tiene permiso para ver {$topic['label']}. Si necesitas usarlo, pide a administracion que te habilite esa vista en Usuarios y roles.");
                    }

                    return $this->response(
                        $topic['text'],
                        $this->topicActions($user, $company, $module)
                    );
                }
            }
        }

        if (str_contains($normalized, 'ayuda') || str_contains($normalized, 'guia') || str_contains($normalized, 'como uso') || str_contains($normalized, 'como funciona')) {
            return $this->response(
                "Puedo guiarte por modulo. Preguntame por ejemplo:\n"
                . "- Como funciona la agenda\n"
                . "- Como doy de baja un producto\n"
                . "- Que es un ajuste de inventario\n"
                . "- Como cierro caja\n"
                . "- Como creo roles y permisos\n"
                . "- Donde veo reportes o deudas",
                $this->suggestedActions($user, $normalized)
            );
        }

        return null;
    }

    private function helpTopics(): array
    {
        return [
            'agenda' => [
                'label' => 'Agenda',
                'keywords' => ['agenda', 'cita', 'agendar', 'asistio', 'no asistio', 'reagendar', 'boton cobrar', 'botones de agenda', 'agregar servicio'],
                'text' => "Agenda sirve para ordenar la atencion diaria por sucursal.\n\n"
                    . "Botones principales:\n"
                    . "- Asistio: marca que el cliente llego. Sirve para estadisticas de asistencia.\n"
                    . "- No asistio: registra inasistencia sin borrar la cita original.\n"
                    . "- Cobrar: registra pagos de tratamientos y productos. Puede dividir efectivo, QR y pagos adicionales.\n"
                    . "- Reagendar: crea una nueva cita para otra fecha sin mover el registro original.\n"
                    . "- Agregar servicio: suma otro tratamiento a la atencion. Puedes seleccionar quien lo agrego.\n"
                    . "- Historia clinica: abre fichas, archivos, recetas y seguimiento del paciente si tu rol tiene permiso.\n"
                    . "- Ver historial: muestra servicios, productos, pagos y deudas del cliente.\n"
                    . "- Eliminar: solo administradores autorizados deben usarlo cuando la cita fue creada por error.\n\n"
                    . "Recomendacion: primero crea o busca el cliente, selecciona servicios, asigna profesional si corresponde y luego marca asistencia/cobro segun avance la atencion.",
            ],
            'inventario' => [
                'label' => 'Inventario',
                'keywords' => ['inventario', 'stock', 'producto', 'lote', 'vencimiento', 'marca', 'proveedor', 'area de uso', 'zona', 'productos'],
                'text' => "Inventario controla productos por sucursal, zona, lote, marca y proveedor.\n\n"
                    . "Ideas clave:\n"
                    . "- Producto: es el item general del catalogo.\n"
                    . "- Lote: separa existencias por vencimiento o ingreso. Si no colocas lote, Rumika puede generarlo.\n"
                    . "- Zona o area de uso: indica donde se usa o guarda el producto, por ejemplo gabinete, recepcion, venta o farmacia.\n"
                    . "- Stock por sucursal: cada sucursal maneja su propio stock. Crear el mismo producto en otra sucursal no debe copiar cantidades.\n"
                    . "- Imagen: ayuda a confirmar visualmente el producto antes de vender.\n\n"
                    . "Para vender correctamente, registra entradas o apertura de inventario. Si el stock esta en 0, Rumika puede permitir venta de emergencia y pedir motivo de faltante.",
            ],
            'inventario_operaciones' => [
                'label' => 'Operaciones de inventario',
                'keywords' => ['entrada', 'salida', 'traspaso', 'desecho', 'baja', 'dar de baja', 'ajuste', 'operacion', 'movimiento', 'gabinete'],
                'text' => "Operaciones de inventario registra todo movimiento que cambia cantidades.\n\n"
                    . "- Entrada: aumenta stock por compra, reposicion o carga manual.\n"
                    . "- Salida: reduce stock por uso interno autorizado.\n"
                    . "- Gabinete: registra consumo dentro de atenciones o procedimientos.\n"
                    . "- Traspaso: mueve productos entre sucursales del mismo negocio/rubro.\n"
                    . "- Desecho o baja: reduce stock por vencimiento, defecto, perdida o producto inutilizable. Siempre escribe un motivo claro.\n"
                    . "- Ajuste: corrige diferencias de conteo. Usalo solo cuando el conteo fisico no coincide con el sistema.\n\n"
                    . "Buena practica: no edites cantidades directo en el producto; usa entradas, salidas, bajas o ajustes para mantener historial.",
            ],
            'caja' => [
                'label' => 'Caja',
                'keywords' => ['caja', 'abrir caja', 'cerrar caja', 'ticket', 'imprimir', 'turno', 'monto inicial', 'qr', 'efectivo', 'cobro'],
                'text' => "Caja resume lo cobrado en una sucursal y turno.\n\n"
                    . "- Abrir caja: inicia el turno con un monto inicial.\n"
                    . "- Cerrar caja: cierra desde la apertura hasta ese momento. Despues puedes abrir otro turno.\n"
                    . "- Imprimir: imprime o reimprime tickets/cierres guardados sin cerrar caja.\n"
                    . "- Efectivo y QR: se separan para cuadrar caja real.\n"
                    . "- Gastos de caja: descuentan del efectivo del dia si el gasto salio de caja.\n\n"
                    . "Si hay impresora configurada en la sucursal, Rumika puede enviar tickets por QZ Tray. Si no, puedes revisar o reimprimir desde tickets guardados.",
            ],
            'ventas_productos' => [
                'label' => 'Ventas directas',
                'keywords' => ['venta directa', 'ventas directas', 'vender producto', 'farmacia', 'comprador', 'nit', 'consumidor final', 'catalogo'],
                'text' => "Ventas directas sirve para vender productos sin agenda ni paciente clinico.\n\n"
                    . "- Comprador: puede ser consumidor final o una persona con nombre, NIT, telefono o email.\n"
                    . "- Factura: pide NIT/nombre solo si el comprador solicita factura.\n"
                    . "- Productos: busca por nombre, codigo, marca o zona y agrega cantidad/precio.\n"
                    . "- Sin stock: si se permite venta de emergencia, marca motivo de faltante para revisarlo despues.\n"
                    . "- Ticket: al confirmar venta puede imprimirse si la sucursal tiene impresora activa.\n\n"
                    . "Este modulo es util para farmacia, tienda, spa o clinica cuando la venta no depende de una cita.",
            ],
            'clientes' => [
                'label' => 'Clientes',
                'keywords' => ['cliente', 'clientes', 'paciente', 'telefono', 'ci', 'documento', 'comprador'],
                'text' => "Clientes guarda la informacion general de personas de la empresa, no de una sola sucursal.\n\n"
                    . "- Puedes buscar por nombre, CI/documento o telefono.\n"
                    . "- Un cliente puede tener varios telefonos y uno marcado como principal.\n"
                    . "- El numero se valida segun pais cuando corresponde.\n"
                    . "- Historial muestra citas, servicios, productos comprados, deudas y pagos pendientes.\n"
                    . "- Inactivar evita usar ese registro sin borrar su historial.\n\n"
                    . "Para clientes de venta directa, Rumika puede guardar datos basicos si tiene NIT o telefono.",
            ],
            'historia_clinica' => [
                'label' => 'Historia clinica',
                'keywords' => ['historia clinica', 'ficha', 'receta', 'archivo clinico', 'plantilla', 'doctor', 'profesional', 'documento clinico', 'pdf', 'imagen'],
                'text' => "Historia clinica concentra fichas, documentos, imagenes, PDF, recetas y accesos por profesional.\n\n"
                    . "- Fichas: registra evolucion, control, ficha inicial o notas clinicas usando plantillas.\n"
                    . "- Archivos: guarda imagenes o PDF del paciente, asociados a una cita/tratamiento si corresponde.\n"
                    . "- Recetas: crea indicaciones o recetas para el paciente.\n"
                    . "- Plantillas: cada empresa puede crear sus propios campos y texto base.\n"
                    . "- Accesos: un doctor puede ver solo pacientes asignados, salvo que administracion autorice mas.\n\n"
                    . "Si entras desde una cita o cliente especifico, lo correcto es trabajar solo con ese paciente.",
            ],
            'servicios' => [
                'label' => 'Servicios',
                'keywords' => ['servicio', 'tratamiento', 'paquete', 'promocion', 'precio de servicio'],
                'text' => "Servicios define lo que se agenda y cobra.\n\n"
                    . "- Servicio o tratamiento: nombre, duracion, precio base y estado.\n"
                    . "- Paquete: agrupa varios servicios con valor general y vigencia.\n"
                    . "- Precio: el cobro puede ajustar el precio si el negocio lo permite.\n"
                    . "- Disponible: solo lo activo deberia aparecer para seleccionar.\n\n"
                    . "Usa paquetes cuando vendas promociones o varias sesiones bajo una misma oferta.",
            ],
            'gastos' => [
                'label' => 'Gastos',
                'keywords' => ['gasto', 'gastos', 'egreso', 'responsable', 'pago personal', 'adelanto', 'liquidacion'],
                'text' => "Gastos registra salidas de dinero por rango de fechas.\n\n"
                    . "- Gasto de caja: descuenta de caja si salio del efectivo del turno/dia.\n"
                    . "- Gasto externo: queda como egreso general y no afecta caja.\n"
                    . "- Gasto de personal: se asocia a un trabajador cuando el tipo lo requiere.\n"
                    . "- Responsable: para gastos normales, registra quien hizo o autorizo el gasto.\n\n"
                    . "Para control mensual, usa filtros desde/hasta y revisa el historial por tipo, responsable o personal.",
            ],
            'deudas' => [
                'label' => 'Deudas',
                'keywords' => ['deuda', 'deudas', 'saldo pendiente', 'pendiente de pago', 'abono'],
                'text' => "Deudas muestra saldos pendientes de servicios y productos.\n\n"
                    . "- Servicio pendiente: aparece cuando el cliente no pago todo el tratamiento.\n"
                    . "- Producto pendiente: aparece cuando se llevo productos a cuenta.\n"
                    . "- Abono: puedes registrar pagos parciales y se descuenta del saldo.\n"
                    . "- Al cobrar en agenda, Rumika avisa si el cliente tiene pendientes.\n\n"
                    . "Esto ayuda a no perder cuentas por cobrar y separar lo pagado en efectivo/QR.",
            ],
            'reportes' => [
                'label' => 'Reportes',
                'keywords' => ['reporte', 'reportes', 'pdf', 'gerente', 'ingresos por sucursal', 'general'],
                'text' => "Reportes concentra informacion gerencial por fecha y sucursal.\n\n"
                    . "Puedes revisar:\n"
                    . "- Ingresos de servicios y productos.\n"
                    . "- Gastos y neto.\n"
                    . "- Deudas y cobros pendientes.\n"
                    . "- Asistencia.\n"
                    . "- Comisiones y metas si estan configuradas.\n\n"
                    . "Usa desde/hasta para comparar periodos y exporta PDF cuando necesites entregar resumen a gerencia.",
            ],
            'comisiones' => [
                'label' => 'Comisiones',
                'keywords' => ['comision', 'comisiones', 'meta', 'metas', 'vendedor', 'profesional que agrego', 'minimo semanal', 'minimo mensual'],
                'text' => "Comisiones permite controlar metas y pagos variables por personal.\n\n"
                    . "- Metas: puedes definir semanal, quincenal, mensual o personalizado.\n"
                    . "- Productos: cada sucursal puede tener porcentaje, minimo de venta y productos que aplican.\n"
                    . "- Servicios agregados: registra quien agrego un servicio extra para calcular si corresponde comision.\n"
                    . "- Minimos: ayudan a evitar comisiones por ventas debajo del precio autorizado.\n\n"
                    . "Este modulo es administrativo y sirve para revisar rendimiento y monto a pagar por periodo.",
            ],
            'crm' => [
                'label' => 'Centro de mensajes',
                'keywords' => ['whatsapp', 'centro de mensajes', 'mensaje', 'crm', 'bandeja', 'canal', 'plantilla whatsapp'],
                'text' => "Centro de mensajes conecta la atencion por WhatsApp con Rumika.\n\n"
                    . "- Bandeja: revisa conversaciones y responde desde el sistema.\n"
                    . "- Agendar: desde un chat puedes crear una cita si el cliente quiere reservar.\n"
                    . "- Canales: cada empresa configura sus numeros de WhatsApp Business.\n"
                    . "- Usuarios: puedes limitar que numero ve cada persona.\n"
                    . "- Mensajes predeterminados: ayudan a responder rapido confirmaciones, ubicacion o reprogramaciones.\n\n"
                    . "El verify token lo genera Rumika y se copia en Meta/Facebook al configurar el webhook.",
            ],
            'usuarios' => [
                'label' => 'Usuarios y roles',
                'keywords' => ['usuario', 'usuarios', 'personal', 'roles', 'permisos', 'autorizar', 'deshabilitar ingreso'],
                'text' => "Usuarios y roles controla quien entra y que puede hacer.\n\n"
                    . "- Usuarios: crea personal, asigna sucursales, rol, foto, telefono y acceso al sistema.\n"
                    . "- Deshabilitar ingreso: inactiva el acceso sin borrar historial.\n"
                    . "- Roles: cada empresa puede crear sus propios roles.\n"
                    . "- Permisos: puedes activar ver, crear, editar o eliminar por pantalla.\n"
                    . "- WhatsApp: asigna que canales puede usar cada usuario.\n\n"
                    . "Buena practica: recepcion, doctor/profesional, caja y administracion deben tener permisos separados.",
            ],
            'sucursales' => [
                'label' => 'Sucursales',
                'keywords' => ['sucursal', 'sucursales', 'negocio', 'pais', 'moneda', 'impresora'],
                'text' => "Sucursales separa operaciones, caja, agenda e inventario.\n\n"
                    . "- Tipo de negocio: define que modulos se muestran para esa sucursal.\n"
                    . "- Pais y moneda: cambia simbolos como Bs, CLP, PEN o ARS donde corresponda.\n"
                    . "- Logo/icono: ayuda a identificar la sucursal en pantalla.\n"
                    . "- Impresora: activa QZ Tray y guarda el nombre de impresora si usara tickets.\n\n"
                    . "Los clientes son generales de la empresa, pero agenda, caja, inventario y ventas operan por sucursal.",
            ],
            'estadisticas' => [
                'label' => 'Estadisticas',
                'keywords' => ['estadistica', 'estadisticas', 'asistencia', 'profesional con mas consultas', 'quien atiende mas', 'doctor con mas consultas', 'ventas por vendedor'],
                'text' => "Estadisticas ayuda a medir rendimiento del negocio.\n\n"
                    . "Puedes ver:\n"
                    . "- Asistencia: agendados, asistidos, no asistidos y pendientes.\n"
                    . "- Ingresos y egresos por rango.\n"
                    . "- Ventas: vendedores, productos y servicios con mejor movimiento.\n"
                    . "- Profesionales: quien atendio mas consultas y su porcentaje.\n"
                    . "- Vista anual para comparar meses.\n\n"
                    . "Usa filtros de fecha y sucursal para revisar una sede o toda la empresa.",
            ],
            'mi_sistema' => [
                'label' => 'Mi sistema',
                'keywords' => ['mi sistema', 'plan', 'planes', 'vencimiento', 'pago mensual', 'renovar', 'bloqueado'],
                'text' => "Mi sistema muestra el estado de la empresa dentro de Rumika.\n\n"
                    . "- Plan actual: indica si la empresa esta en free, basico, intermedio o completo.\n"
                    . "- Fechas de pago: ayuda a saber cuando vence o cuando fue renovado.\n"
                    . "- Limites: muestra modulos y restricciones del plan.\n"
                    . "- Solicitud de plan: si necesitas mas capacidad, puedes solicitar cambio de plan.\n\n"
                    . "Si el plan vencio, algunas acciones pueden bloquearse hasta renovar el acceso.",
            ],
        ];
    }

    private function topicActions(User $user, Company $company, string $module): array
    {
        $actions = [];

        if (isset(self::MODULES[$module]) && $this->can($user, $module, company: $company)) {
            $actions[] = $this->actionButton('Abrir ' . self::MODULES[$module]['label'], 'go_' . $module);
        }

        if ($module === 'inventario' && $this->can($user, 'inventario_operaciones', company: $company)) {
            $actions[] = $this->actionButton('Operaciones', 'go_inventario_operaciones');
        }

        if ($module === 'agenda' && $this->can($user, 'agenda', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nueva cita', 'new_appointment');
        }

        if ($module === 'clientes' && $this->can($user, 'clientes', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nuevo cliente', 'new_client');
        }

        if ($module === 'caja' && $this->can($user, 'caja', company: $company)) {
            $actions[] = $this->actionButton('Caja rapida', 'open_quick_cashbox');
        }

        if ($module !== 'usuarios' && $this->can($user, 'usuarios', company: $company)) {
            $actions[] = $this->actionButton('Permisos', 'go_usuarios');
        }

        return array_slice($actions, 0, 4);
    }

    private function isSalesQuestion(string $normalized): bool
    {
        $hasSalesWord = str_contains($normalized, 'vendi')
            || str_contains($normalized, 'vendido')
            || str_contains($normalized, 'ventas')
            || str_contains($normalized, 'ingreso')
            || str_contains($normalized, 'facture')
            || str_contains($normalized, 'facturado');

        $hasPeriodWord = str_contains($normalized, 'mes')
            || str_contains($normalized, 'semana')
            || str_contains($normalized, 'hoy')
            || str_contains($normalized, 'ayer');

        return $hasSalesWord && $hasPeriodWord;
    }

    private function salesSummary(User $user, Company $company, ?Branch $branch, string $normalized): array
    {
        if (! $this->can($user, 'caja', company: $company)
            && ! $this->can($user, 'reportes', company: $company)
            && ! $this->can($user, 'resumen_financiero', company: $company)
            && ! $this->can($user, 'estadisticas', company: $company)) {
            return $this->response('Tu rol no tiene permiso para ver montos de ventas o ingresos.');
        }

        if (! $branch) {
            return $this->response('No encontre una sucursal activa para revisar ventas.');
        }

        [$from, $to, $label] = $this->periodFromQuestion($normalized);
        $useAllBranches = str_contains($normalized, 'todas') || str_contains($normalized, 'general') || str_contains($normalized, 'empresa');
        $branchIds = $useAllBranches && $this->can($user, 'reportes', company: $company)
            ? $company->branches()->pluck('id')->all()
            : [$branch->id];

        $serviceIncome = (float) TreatmentPayment::query()
            ->where('company_id', $company->id)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $productIncome = (float) ProductSale::query()
            ->where('company_id', $company->id)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('sold_at', [$from, $to])
            ->sum('paid_amount');

        $total = $serviceIncome + $productIncome;
        $scope = count($branchIds) > 1 ? 'todas las sucursales' : $branch->name;

        return $this->response(
            "Ventas de {$label} en {$scope}:\n"
            . 'Servicios: ' . Money::symbol() . ' ' . number_format($serviceIncome, 2) . "\n"
            . 'Productos: ' . Money::symbol() . ' ' . number_format($productIncome, 2) . "\n"
            . 'Total vendido: ' . Money::symbol() . ' ' . number_format($total, 2),
            [
                $this->actionButton('Abrir caja', 'open_quick_cashbox'),
                $this->actionButton('Ver reportes', 'go_reportes'),
                $this->actionButton('Ver estadisticas', 'go_estadisticas'),
            ]
        );
    }

    private function periodFromQuestion(string $normalized): array
    {
        $now = now();

        if (str_contains($normalized, 'ayer')) {
            $date = $now->copy()->subDay();

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay(), 'ayer'];
        }

        if (str_contains($normalized, 'hoy')) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hoy'];
        }

        if (str_contains($normalized, 'semana')) {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'esta semana'];
        }

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'este mes'];
    }

    private function todaySummary(User $user, Company $company, ?Branch $branch): array
    {
        if (! $branch) {
            return $this->response('No encontre una sucursal activa para revisar el resumen.');
        }

        $today = now();
        $dayRange = [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
        $parts = ["Sucursal activa: {$branch->name}."];
        $actions = [];

        if ($this->can($user, 'agenda', company: $company)) {
            $appointments = Appointment::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('scheduled_at', $dayRange)
                ->get();
            $parts[] = 'Agenda: ' . $appointments->count() . ' citas, ' . $appointments->where('attended', true)->count() . ' asistidas y ' . $appointments->where('status', 'no_show')->count() . ' no asistidas.';
            $actions[] = $this->actionButton('Abrir agenda', 'go_agenda');
        }

        if ($this->can($user, 'caja', company: $company)) {
            $serviceIncome = (float) TreatmentPayment::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('paid_at', $dayRange)
                ->sum('amount');
            $productIncome = (float) ProductSale::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('sold_at', $dayRange)
                ->sum('paid_amount');
            $parts[] = 'Caja: ' . Money::symbol() . ' ' . number_format($serviceIncome + $productIncome, 2) . ' ingresados entre servicios y productos.';
            $actions[] = $this->actionButton('Abrir caja', 'open_quick_cashbox');
        }

        if ($this->can($user, 'gastos', company: $company)) {
            $expenses = (float) Expense::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereDate('spent_at', $today->toDateString())
                ->sum('amount');
            $parts[] = 'Gastos de hoy: ' . Money::symbol() . ' ' . number_format($expenses, 2) . '.';
        }

        if ($this->can($user, 'inventario', company: $company)) {
            $expiring = InventoryProductBatch::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->where('status', 'active')
                ->where('current_quantity', '>', 0)
                ->whereBetween('expires_at', [$today->copy()->startOfDay(), $today->copy()->addDays(30)->endOfDay()])
                ->count();
            $parts[] = "Inventario: {$expiring} lotes vencen en los proximos 30 dias.";
            $actions[] = $this->actionButton('Ver inventario', 'go_inventario');
        }

        if (count($parts) === 1) {
            $parts[] = 'Tu rol no tiene permisos suficientes para ver indicadores operativos.';
        }

        return $this->response(implode("\n", $parts), $actions);
    }

    private function roleSummary(User $user, Company $company): array
    {
        $allowed = collect(self::MODULES)
            ->filter(fn (array $data, string $module) => $this->can($user, $module, company: $company))
            ->map(fn (array $data) => $data['label'])
            ->unique()
            ->values();

        if ($allowed->isEmpty()) {
            return $this->response('Tu rol no tiene vistas activas en este momento. Pide a administracion que revise tus permisos.');
        }

        return $this->response(
            'Con tu rol puedes ver: ' . $allowed->join(', ') . '. Si necesitas otra vista, administracion puede activarla desde Usuarios y roles.',
            [
                $this->actionButton('Usuarios y roles', 'go_usuarios'),
                $this->actionButton('Mas opciones', 'open_more'),
            ]
        );
    }

    private function navigationAnswer(User $user, Company $company, string $module): array
    {
        $data = self::MODULES[$module] ?? null;

        if (! $data) {
            return $this->response('Todavia no tengo ubicada esa pantalla.');
        }

        if (! $this->can($user, $module, company: $company)) {
            return $this->response("Tu rol no tiene permiso para abrir {$data['label']}. Puedes pedir acceso desde administracion.");
        }

        $actions = [$this->actionButton('Abrir ' . $data['label'], 'go_' . $module)];

        if ($module === 'agenda' && $this->can($user, 'agenda', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nueva cita', 'new_appointment');
        }

        if ($module === 'clientes' && $this->can($user, 'clientes', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nuevo cliente', 'new_client');
        }

        return $this->response("Para trabajar con {$data['label']}, entra a esa pantalla desde el menu. Puedo abrirla por ti.", $actions);
    }

    private function allowedContext(User $user, Company $company, ?Branch $branch): array
    {
        return [
            'empresa' => $company->name,
            'sucursal_activa' => $branch?->name,
            'rol' => $user->companies()->where('companies.id', $company->id)->value('company_user.role') ?: 'personal',
            'modulos_permitidos' => collect(self::MODULES)
                ->filter(fn (array $data, string $module) => $this->can($user, $module, company: $company))
                ->map(fn (array $data) => $data['label'])
                ->unique()
                ->values()
                ->all(),
        ];
    }

    private function askConfiguredProvider(string $question, array $context): ?string
    {
        $endpoint = config('services.rumi_ai.endpoint');

        if ($endpoint) {
            try {
                $response = Http::timeout(20)
                    ->withToken((string) config('services.rumi_ai.token'))
                    ->post($endpoint, [
                        'question' => $question,
                        'context' => $context,
                        'rules' => [
                            'language' => 'es',
                            'no_sensitive_data_without_permission' => true,
                            'no_database_writes' => true,
                        ],
                    ]);

                if ($response->successful()) {
                    return trim((string) ($response->json('answer') ?? $response->json('message')));
                }

                Log::warning('Rumi endpoint error', ['status' => $response->status(), 'body' => $response->body()]);
            } catch (\Throwable $e) {
                Log::warning('Rumi endpoint exception', ['message' => $e->getMessage()]);
            }
        }

        $apiKey = config('services.google_ai.key');

        if (! $apiKey) {
            return null;
        }

        try {
            $model = config('services.google_ai.model', 'gemini-2.0-flash');
            $prompt = "Eres Rumi, asistente interno de Rumika SaaS. Responde en espanol, breve y claro. Solo usa el contexto permitido. No inventes datos. No digas que puedes editar, eliminar, cobrar o cerrar caja. Contexto permitido: "
                . json_encode($context, JSON_UNESCAPED_UNICODE)
                . "\nPregunta: {$question}";

            $response = Http::timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ]],
                ]);

            if (! $response->successful()) {
                Log::warning('Gemini Rumi error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));
        } catch (\Throwable $e) {
            Log::warning('Gemini Rumi exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function suggestedActions(User $user, string $normalized): array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return [];
        }

        $actions = [
            $this->actionButton('Cambiar sucursal', 'open_branch'),
            $this->actionButton('Mas opciones', 'open_more'),
        ];

        foreach (['agenda', 'clientes', 'caja', 'reportes'] as $module) {
            if ($this->can($user, $module, company: $company)) {
                $actions[] = $this->actionButton(self::MODULES[$module]['label'], 'go_' . $module);
            }
        }

        return array_slice($actions, 0, 5);
    }

    private function response(string $answer, array $actions = []): array
    {
        return [
            'answer' => trim($answer),
            'actions' => array_values(array_filter($actions)),
        ];
    }

    private function actionButton(string $label, string $key): array
    {
        return ['label' => $label, 'key' => $key];
    }

    private function can(User $user, string $module, string $action = 'view', ?Company $company = null): bool
    {
        if ($module === 'mi_sistema') {
            return true;
        }

        return RumikaAccess::can($user, $module, $action, company: $company);
    }

    private function activeBranch(User $user, Company $company): ?Branch
    {
        $branches = $user->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id')) ?? $branches->first();
    }
}
