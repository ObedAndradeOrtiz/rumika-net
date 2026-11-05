<?php

namespace App\Support;

use App\Models\InventoryMovement;
use App\Models\InventoryProductBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryTransferDeletion
{
    public static function delete(int $companyId, int $branchId, int $movementId): void
    {
        DB::transaction(function () use ($companyId, $branchId, $movementId) {
            $movement = InventoryMovement::query()
                ->where('company_id', $companyId)->where('branch_id', $branchId)
                ->whereIn('type', ['transfer_out', 'transfer_in'])
                ->find($movementId);
            abort_unless($movement, 404);

            if ($movement->transfer_key) {
                $pair = InventoryMovement::query()->where('company_id', $companyId)
                    ->where('transfer_key', $movement->transfer_key)->orderBy('id')->lockForUpdate()->get();
            } else {
                // Older versions wrote the receipt immediately after the dispatch.
                // Require matching details too, rather than guessing by product alone.
                $pair = InventoryMovement::query()->where('company_id', $companyId)
                    ->whereIn('id', $movement->type === 'transfer_out'
                        ? [$movement->id, $movement->id + 1]
                        : [$movement->id - 1, $movement->id])
                    ->orderBy('id')->lockForUpdate()->get();
            }

            $out = $pair->firstWhere('type', 'transfer_out');
            $in = $pair->firstWhere('type', 'transfer_in');
            if ($pair->count() !== 2 || ! $out || ! $in
                || $out->branch_id === $in->branch_id
                || $out->related_branch_id !== $in->branch_id
                || ! in_array($in->related_branch_id, [$out->branch_id, $in->branch_id], true)
                || $out->inventory_product_id !== $in->inventory_product_id
                || $out->quantity !== $in->quantity || $out->unit_cost !== $in->unit_cost
                || $out->reference !== $in->reference || $out->reason !== $in->reason
                || (! $movement->transfer_key && (! $out->moved_at || ! $in->moved_at
                    || abs($out->moved_at->diffInSeconds($in->moved_at)) > 60))) {
                self::fail('No se pudo identificar la entrada y salida de este traspaso con seguridad. Revisa sus movimientos.');
            }

            $batches = InventoryProductBatch::query()->where('company_id', $companyId)
                ->where('inventory_product_id', $out->inventory_product_id)
                ->whereIn('id', [$out->inventory_product_batch_id, $in->inventory_product_batch_id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $batches->get($out->inventory_product_batch_id);
            $target = $batches->get($in->inventory_product_batch_id);

            if (! $source || ! $target || $source->branch_id !== $out->branch_id
                || $target->branch_id !== $in->branch_id || $source->lot_code !== $target->lot_code) {
                self::fail('No se encontraron los lotes originales del traspaso.');
            }

            if ($pair->contains(fn ($item) => $item->inventoryCount?->status === 'closed')) {
                self::fail('El traspaso pertenece a un inventario cerrado. Revisa el cierre antes de eliminarlo.');
            }

            $quantity = (float) $out->quantity;
            if ($quantity <= 0 || (float) $target->current_quantity < $quantity
                || (float) $target->initial_quantity < $quantity) {
                self::fail('La sucursal destino ya no tiene cantidad suficiente en este lote para devolver el traspaso.');
            }

            $target->decrement('current_quantity', $quantity);
            $target->decrement('initial_quantity', $quantity);
            $source->increment('current_quantity', $quantity);
            // Model deletes preserve the audit trail for both sides of the transfer.
            $in->delete();
            $out->delete();
        });
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['transferDelete' => $message]);
    }
}
