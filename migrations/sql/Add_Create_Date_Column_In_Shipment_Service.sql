ALTER TABLE `icargo_shipment_service` ADD COLUMN `create_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `customer_reference2`;
