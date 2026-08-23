<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\TreatmentPayment;

class PaymentTicketBuilder
{
    public static function payload(TreatmentPayment $payment, Branch $branch): array
    {
        $payment->loadMissing([
            'company',
            'client',
            'splits',
            'items',
            'chargePayments.charge',
            'performedBy',
            'receivedBy',
        ]);

        $cashLeft = (float) $payment->splits->where('method', 'cash')->sum('amount');
        $qrLeft = (float) $payment->splits->where('method', 'qr')->sum('amount');
        $chargePaymentsByCharge = $payment->chargePayments->keyBy('client_charge_id');
        $rows = [];

        foreach ($payment->items->sortBy(fn ($item) => $item->type === 'service' ? 0 : 1) as $item) {
            $total = (float) $item->total;
            $cash = min($cashLeft, $total);
            $cashLeft -= $cash;
            $qr = min($qrLeft, $total - $cash);
            $qrLeft -= $qr;
            $chargePayment = $item->client_charge_id ? $chargePaymentsByCharge->get($item->client_charge_id) : null;
            $charge = $chargePayment?->charge;
            $isDebtPayment = str_starts_with($item->name, 'Abono ');

            $rows[] = [
                'type' => $isDebtPayment ? 'Abono pendiente' : ($item->type === 'product' ? 'Producto' : 'Servicio'),
                'name' => $isDebtPayment ? str_replace('Abono ', '', $item->name) : $item->name,
                'quantity' => (float) $item->quantity,
                'charged_total' => (float) ($item->charged_total ?: $item->total),
                'total' => $total,
                'cash' => $cash,
                'qr' => $qr,
                'remaining_balance' => $charge ? (float) $charge->balance_amount : 0.0,
            ];
        }

        $outstandingCharges = $payment->company
            ->clientCharges()
            ->where('branch_id', $branch->id)
            ->where('client_id', $payment->client_id)
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance_amount', '>', 0)
            ->orderBy('charged_at')
            ->get()
            ->map(fn ($charge) => [
                'type' => $charge->type === 'product' ? 'Producto' : 'Tratamiento',
                'name' => $charge->name,
                'total' => (float) $charge->total_amount,
                'paid' => (float) $charge->paid_amount,
                'balance' => (float) $charge->balance_amount,
            ])
            ->values()
            ->all();

        return [
            'title' => 'Ticket de cobro',
            'branch' => $branch->name,
            'country_code' => $branch->country_code ?? 'BO',
            'currency_code' => $branch->currency_code ?? 'BOB',
            'currency_symbol' => $branch->currency_symbol ?? 'Bs',
            'business_date' => $payment->paid_at->format('d/m/Y H:i'),
            'client' => $payment->client?->full_name ?? 'Cliente',
            'performed_by' => $payment->performedBy?->name ?? 'Sin profesional',
            'received_by' => $payment->receivedBy?->name ?? 'Sin cajero',
            'printer_enabled' => (bool) $branch->uses_ticket_printer,
            'printer_name' => $branch->printer_name,
            'printer_bridge_url' => $branch->printer_bridge_url,
            'rows' => $rows,
            'outstanding_charges' => $outstandingCharges,
            'totals' => [
                'cash' => (float) $payment->splits->where('method', 'cash')->sum('amount'),
                'qr' => (float) $payment->splits->where('method', 'qr')->sum('amount'),
                'total' => (float) $payment->amount,
            ],
            'created_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
