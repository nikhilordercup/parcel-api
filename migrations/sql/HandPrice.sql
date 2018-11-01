ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `is_manualbooking` VARCHAR(5) NULL DEFAULT 'false' AFTER `customer_reference2`;
ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `manualbooking_ref` VARCHAR(255) NULL DEFAULT NULL AFTER `is_manualbooking`;