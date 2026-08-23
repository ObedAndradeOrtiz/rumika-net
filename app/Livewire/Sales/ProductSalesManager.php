<?php

namespace App\Livewire\Sales;

use App\Models\Branch;
use App\Models\Buyer;
use App\Models\CashboxTicket;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\ProductSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductSalesManager extends Component
{
    public string $tab = 'sale';
    public string $buyerSearch = '';
    public string $buyerDirectorySearch = '';
    public string $buyerName = '';
    public string $buyerNit = '';
    public string $buyerPhone = '';
    public string $buyerEmail = '';
    public string $productSearch = '';
    public array $lines = [];
    public string $cashAmount = '0';
    public string $qrAmount = '0';
    public ?int $soldByUserId = null;
    public bool $invoiceRequested = false;
    public string $reference = '';
    public string $notes = '';
    public string $message = '';
    public bool $showTicketPreview = false;
    public array $ticketPreview = [];

    public function mount(): void
    {
        $this->soldByUserId = Auth::id();
    }

    public function selectBuyer(int $buyerId): void
    {
        $buyer = $this->company()->buyers()->whereKey($buyerId)->firstOrFail();
        $this->buyerName = $buyer->full_name ?? '';
        $this->buyerNit = $buyer->nit ?? '';
        $this->buyerPhone = $buyer->phone ?? '';
        $this->buyerEmail = $buyer->email ?? '';
        $this->buyerSearch = '';
    }

    public function useBuyerForSale(int $buyerId): void
    {
        $this->selectBuyer($buyerId);
        $this->tab = 'sale';
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['sale', 'buyers'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function addProduct(int $productId): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $product = $company->inventoryProducts()
            ->with(['brand', 'useArea'])
            ->where('status', 'active')
            ->whereKey($productId)
            ->firstOrFail();
        $batch = $company->inventoryBatches()
            ->where('branch_id', $branch->id)
            ->where('inventory_product_id', $product->id)
            ->where('status', 'available')
            ->orderByRaw('CASE WHEN current_quantity > 0 THEN 0 ELSE 1 END')
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->first();

        $this->lines[] = [
            'product_id' => $product->id,
            'batch_id' => $batch?->id,
            'name' => $product->name,
            'code' => $product->code,
            'brand' => $product->brand?->name ?? 'Sin marca',
            'area' => $product->useArea?->name ?? 'Sin area',
            'lot' => $batch?->lot_code ?? 'Sin lote',
            'available' => (float) ($batch?->current_quantity ?? 0),
            'quantity' => '1',
            'unit_price' => (string) ((float) ($product->purchase_cost ?: 0)),
            'missing_reason' => '',
        ];

        $this->productSearch = '';
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function saveSale(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $staffIds = $company->users()->pluck('users.id')->all();

        $validated = $this->validate([
            'buyerName' => ['nullable', 'string', 'max:180'],
            'buyerNit' => ['nullable', 'string', 'max:40'],
            'buyerPhone' => ['nullable', 'string', 'max:40'],
            'buyerEmail' => ['nullable', 'email', 'max:180'],
            'soldByUserId' => ['required', Rule::in($staffIds)],
            'cashAmount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'qrAmount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'invoiceRequested' => ['boolean'],
            'reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('inventory_products', 'id')->where('company_id', $company->id)],
            'lines.*.batch_id' => ['nullable', Rule::exists('inventory_product_batches', 'id')->where('company_id', $company->id)->where('branch_id', $branch->id)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'lines.*.missing_reason' => ['nullable', 'string', 'max:180'],
        ]);

        $subtotal = collect($validated['lines'])->sum(fn (array $line) => round((float) $line['quantity'] * (float) $line['unit_price'], 2));
        $cash = round((float) $validated['cashAmount'], 2);
        $qr = round((float) $validated['qrAmount'], 2);
        $paid = round($cash + $qr, 2);

        if ($subtotal <= 0) {
            $this->addError('lines', 'Agrega al menos un producto con precio.');

            return;
        }

        if (abs($paid - $subtotal) > 0.01) {
            $this->addError('cashAmount', 'El total pagado debe ser igual al total de la venta.');

            return;
        }

        if (($validated['invoiceRequested'] ?? false) && trim((string) $validated['buyerNit']) === '') {
            $this->addError('buyerNit', 'Ingresa el NIT para facturar.');

            return;
        }

        if (($validated['invoiceRequested'] ?? false) && trim((string) $validated['buyerName']) === '') {
            $this->addError('buyerName', 'Ingresa el nombre o razon social para facturar.');

            return;
        }

        foreach ($validated['lines'] as $index => $line) {
            $quantity = round((float) $line['quantity'], 2);
            $batch = $line['batch_id']
                ? $company->inventoryBatches()
                    ->where('branch_id', $branch->id)
                    ->whereKey($line['batch_id'])
                    ->first()
                : null;
            $available = max(0, (float) ($batch?->current_quantity ?? 0));

            if ($quantity > $available && trim((string) ($line['missing_reason'] ?? '')) === '') {
                $this->addError("lines.$index.missing_reason", 'Coloca el motivo del faltante para vender sin stock.');

                return;
            }
        }

        $sale = DB::transaction(function () use ($company, $branch, $validated, $subtotal, $cash, $qr, $paid) {
            $buyer = $this->buyerForSale($company);
            $sale = ProductSale::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'buyer_id' => $buyer?->id,
                'sold_by_user_id' => $validated['soldByUserId'],
                'received_by_user_id' => Auth::id(),
                'buyer_name' => $this->buyerName ?: ($buyer?->full_name ?: 'Consumidor final'),
                'buyer_nit' => $this->buyerNit ?: null,
                'buyer_phone' => $this->buyerPhone ?: null,
                'buyer_email' => $this->buyerEmail ?: null,
                'subtotal' => $subtotal,
                'paid_amount' => $paid,
                'cash_amount' => $cash,
                'qr_amount' => $qr,
                'method' => $cash > 0 && $qr > 0 ? 'mixed' : ($qr > 0 ? 'qr' : 'cash'),
                'invoice_requested' => $validated['invoiceRequested'],
                'invoice_status' => $validated['invoiceRequested'] ? 'pending' : 'not_requested',
                'reference' => $validated['reference'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'sold_at' => now(),
            ]);

            foreach ($validated['lines'] as $line) {
                $product = $company->inventoryProducts()->whereKey($line['product_id'])->firstOrFail();
                $batch = $line['batch_id']
                    ? InventoryProductBatch::query()->whereKey($line['batch_id'])->lockForUpdate()->first()
                    : null;
                $quantity = round((float) $line['quantity'], 2);
                $unitPrice = round((float) $line['unit_price'], 2);
                $available = max(0, (float) ($batch?->current_quantity ?? 0));
                $stockQuantity = min($quantity, $available);
                $pendingQuantity = round($quantity - $stockQuantity, 2);

                if ($batch && $stockQuantity > 0) {
                    $batch->decrement('current_quantity', $stockQuantity);
                }

                $sale->items()->create([
                    'inventory_product_id' => $product->id,
                    'inventory_product_batch_id' => $batch?->id,
                    'name' => $product->name,
                    'lot_code' => $batch?->lot_code,
                    'quantity' => $quantity,
                    'stock_quantity' => $stockQuantity,
                    'pending_quantity' => $pendingQuantity,
                    'unit_price' => $unitPrice,
                    'total' => round($quantity * $unitPrice, 2),
                    ...$this->productCommissionData($branch, $product, round($quantity * $unitPrice, 2)),
                    'missing_reason' => $pendingQuantity > 0 ? (($line['missing_reason'] ?? '') ?: 'Venta con stock pendiente') : null,
                ]);

                if ($stockQuantity > 0) {
                    InventoryMovement::query()->create([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'inventory_product_id' => $product->id,
                        'inventory_product_batch_id' => $batch?->id,
                        'type' => 'sale',
                        'quantity' => $stockQuantity,
                        'unit_cost' => $batch?->unit_cost ?? 0,
                        'total_cost' => $stockQuantity * (float) ($batch?->unit_cost ?? 0),
                        'moved_at' => now(),
                        'reference' => 'SALE-'.$sale->id,
                        'reason' => 'Venta directa de producto',
                    ]);
                }

                if ($pendingQuantity > 0) {
                    InventoryMovement::query()->create([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'inventory_product_id' => $product->id,
                        'inventory_product_batch_id' => $batch?->id,
                        'type' => 'stock_shortage',
                        'quantity' => $pendingQuantity,
                        'unit_cost' => $batch?->unit_cost ?? 0,
                        'total_cost' => $pendingQuantity * (float) ($batch?->unit_cost ?? 0),
                        'moved_at' => now(),
                        'reference' => 'SALE-'.$sale->id,
                        'reason' => ($line['missing_reason'] ?? '') ?: 'Venta con stock pendiente',
                    ]);
                }
            }

            return $sale->load(['items', 'soldBy']);
        });

        $this->reset(['buyerSearch', 'buyerName', 'buyerNit', 'buyerPhone', 'buyerEmail', 'productSearch', 'lines', 'cashAmount', 'qrAmount', 'invoiceRequested', 'reference', 'notes']);
        $this->soldByUserId = Auth::id();
        $this->cashAmount = '0';
        $this->qrAmount = '0';
        $this->message = 'Venta registrada correctamente.';
        $this->openProductSaleTicket($sale, true);
    }

    public function previewProductSaleTicket(int $saleId): void
    {
        $sale = $this->company()
            ->productSales()
            ->with(['items', 'soldBy'])
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($saleId)
            ->firstOrFail();

        $this->openProductSaleTicket($sale, false);
    }

    public function closeTicketPreview(): void
    {
        $this->showTicketPreview = false;
        $this->ticketPreview = [];
    }

    public function markTicketPrinted(): void
    {
        $ticketId = $this->ticketPreview['ticket_id'] ?? null;

        if (! $ticketId) {
            return;
        }

        $this->company()
            ->cashboxTickets()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($ticketId)
            ->update([
                'printed_by_user_id' => Auth::id(),
                'printed_at' => now(),
                'status' => 'printed',
            ]);
    }

    public function getTotalProperty(): float
    {
        return collect($this->lines)->sum(fn (array $line) => round((float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0), 2));
    }

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $buyerSearch = trim($this->buyerSearch);
        $buyerDirectorySearch = trim($this->buyerDirectorySearch);
        $productSearch = trim($this->productSearch);

        return view('livewire.sales.product-sales-manager', [
            'branch' => $branch,
            'buyers' => $buyerSearch === '' ? collect() : $company->buyers()
                ->where('status', 'active')
                ->where(fn (Builder $query) => $query
                    ->where('full_name', 'like', "%{$buyerSearch}%")
                    ->orWhere('nit', 'like', "%{$buyerSearch}%")
                    ->orWhere('phone', 'like', "%{$buyerSearch}%"))
                ->limit(6)
                ->get(),
            'buyerDirectory' => $company->buyers()
                ->where('status', 'active')
                ->when($buyerDirectorySearch !== '', fn (Builder $query) => $query
                    ->where(fn (Builder $nested) => $nested
                        ->where('full_name', 'like', "%{$buyerDirectorySearch}%")
                        ->orWhere('nit', 'like', "%{$buyerDirectorySearch}%")
                        ->orWhere('phone', 'like', "%{$buyerDirectorySearch}%")
                        ->orWhere('email', 'like', "%{$buyerDirectorySearch}%")))
                ->latest()
                ->limit(80)
                ->get(),
            'products' => $productSearch === '' ? collect() : $company->inventoryProducts()
                ->with(['brand', 'useArea', 'batches' => fn ($query) => $query->where('branch_id', $branch->id)])
                ->where('status', 'active')
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$productSearch}%")
                    ->orWhere('code', 'like', "%{$productSearch}%")
                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', "%{$productSearch}%"))
                    ->orWhereHas('useArea', fn (Builder $areaQuery) => $areaQuery->where('name', 'like', "%{$productSearch}%"))
                    ->orWhereHas('batches', fn (Builder $batchQuery) => $batchQuery
                        ->where('branch_id', $branch->id)
                        ->where('lot_code', 'like', "%{$productSearch}%")))
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'staffUsers' => $company->users()->orderBy('name')->get(),
            'recentSales' => $company->productSales()
                ->with(['buyer', 'soldBy', 'items'])
                ->where('branch_id', $branch->id)
                ->latest('sold_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function buyerForSale(Company $company): ?Buyer
    {
        $nit = trim($this->buyerNit);
        $phone = trim($this->buyerPhone);

        if ($nit === '' && $phone === '') {
            return null;
        }

        $query = $company->buyers();
        $buyer = $query
            ->where(fn (Builder $nested) => $nested
                ->when($nit !== '', fn (Builder $q) => $q->orWhere('nit', $nit))
                ->when($phone !== '', fn (Builder $q) => $q->orWhere('phone', $phone)))
            ->first();

        if (! $buyer) {
            $buyer = new Buyer(['company_id' => $company->id]);
        }

        $buyer->fill([
            'full_name' => $this->buyerName ?: $buyer->full_name,
            'nit' => $nit ?: $buyer->nit,
            'phone' => $phone ?: $buyer->phone,
            'email' => $this->buyerEmail ?: $buyer->email,
            'status' => 'active',
        ])->save();

        return $buyer;
    }

    private function openProductSaleTicket(ProductSale $sale, bool $autoPrint): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $sale->loadMissing(['items', 'soldBy']);
        $payload = $this->productSaleTicketPayload($sale, $branch);
        $ticket = CashboxTicket::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'product_sale',
            'ticket_number' => 'SALE-'.$company->id.'-'.$branch->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999),
            'title' => 'Ticket de venta',
            'payload' => $payload,
            'status' => 'generated',
        ]);

        $payload['ticket_id'] = $ticket->id;
        $payload['ticket_number'] = $ticket->ticket_number;
        $ticket->update(['payload' => $payload]);

        $this->ticketPreview = $payload;
        $this->showTicketPreview = true;

        if ($autoPrint && $payload['printer_enabled'] && $payload['printer_name']) {
            $this->dispatch('rumika-auto-print-ticket');
        }
    }

    private function productSaleTicketPayload(ProductSale $sale, Branch $branch): array
    {
        $rows = $sale->items
            ->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'pending_quantity' => (float) $item->pending_quantity,
            ])
            ->values()
            ->all();

        return [
            'title' => 'Ticket de venta',
            'branch' => $branch->name,
            'business_date' => $sale->sold_at->format('d/m/Y H:i'),
            'currency_code' => $branch->currency_code ?? 'BOB',
            'currency_symbol' => $branch->moneySymbol(),
            'buyer' => $sale->buyer_name ?: 'Consumidor final',
            'buyer_nit' => $sale->buyer_nit ?: 'Sin NIT',
            'sold_by' => $sale->soldBy?->name ?? 'Sin vendedor',
            'received_by' => Auth::user()?->name ?? 'Sin cajero',
            'printer_enabled' => (bool) $branch->uses_ticket_printer,
            'printer_name' => $branch->printer_name,
            'printer_bridge_url' => $branch->printer_bridge_url,
            'rows' => $rows,
            'totals' => [
                'cash' => (float) $sale->cash_amount,
                'qr' => (float) $sale->qr_amount,
                'total' => (float) $sale->subtotal,
            ],
            'raw_ticket' => $this->buildRawProductSaleTicket($sale, $branch, $rows),
            'created_at' => now()->format('d/m/Y H:i'),
        ];
    }

    private function productCommissionData(Branch $branch, InventoryProduct $product, float $saleTotal): array
    {
        $percent = (float) $branch->product_commission_percent;
        $minimum = (float) $branch->product_commission_min_sale;

        if ($saleTotal <= 0 || $percent <= 0 || $saleTotal < $minimum || $product->commission_enabled === false) {
            return ['commission_percent' => 0, 'commission_amount' => 0];
        }

        return [
            'commission_percent' => $percent,
            'commission_amount' => round($saleTotal * $percent / 100, 2),
        ];
    }

    private function buildRawProductSaleTicket(ProductSale $sale, Branch $branch, array $rows): string
    {
        $width = 42;
        $currency = $branch->moneySymbol();
        $esc = "\x1B";
        $gs = "\x1D";
        $clean = fn ($value) => trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value) ?: (string) $value));
        $center = function ($value) use ($width, $clean): string {
            $text = substr($clean($value), 0, $width);

            return str_pad($text, $width, ' ', STR_PAD_BOTH);
        };
        $money = fn ($value) => $currency.' '.number_format((float) $value, 2, '.', '');
        $line = str_repeat('-', $width);
        $row = function ($name, $value) use ($clean): string {
            $name = substr($clean($name), 0, 25);
            $value = substr($clean($value), 0, 17);

            return str_pad($name, 25).str_pad($value, 17, ' ', STR_PAD_LEFT);
        };
        $lines = [
            $esc.'@',
            $center($branch->name),
            $center('Ticket de venta'),
            $line,
            'FECHA: '.$sale->sold_at->format('d/m/Y H:i'),
            'COMPRADOR: '.substr($clean($sale->buyer_name ?: 'Consumidor final'), 0, 31),
            'NIT: '.substr($clean($sale->buyer_nit ?: 'S/N'), 0, 36),
            'VENDEDOR: '.substr($clean($sale->soldBy?->name ?? 'Sin vendedor'), 0, 31),
            $line,
            'PRODUCTOS',
            $line,
        ];

        foreach ($rows as $item) {
            $name = $item['name'];

            if ((float) $item['quantity'] > 1) {
                $name .= ' x'.number_format((float) $item['quantity'], 0);
            }

            $lines[] = $row($name, $money($item['total']));

            if ((float) $item['pending_quantity'] > 0) {
                $lines[] = $row('Stock pendiente', number_format((float) $item['pending_quantity'], 2));
            }
        }

        $lines[] = $line;
        $lines[] = $row('EFECTIVO', $money($sale->cash_amount));
        $lines[] = $row('QR', $money($sale->qr_amount));
        $lines[] = $row('TOTAL', $money($sale->subtotal));
        $lines[] = $line;
        $lines[] = $center('Gracias por tu compra');
        $lines[] = $center('Sistema Rumika SaaS');
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';
        $lines[] = $gs.'V'."\x00";

        return implode("\n", $lines);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function activeBranch(): Branch
    {
        $company = $this->company();
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id'))
            ?? $branches->first()
            ?? $company->branches()->firstOrFail();
    }
}
