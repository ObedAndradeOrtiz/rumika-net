-- Ejecutar una sola vez si no se usa php artisan migrate --force.
ALTER TABLE inventory_movements
    ADD COLUMN transfer_key CHAR(36) NULL,
    ADD INDEX inventory_movements_transfer_key_index (transfer_key);
