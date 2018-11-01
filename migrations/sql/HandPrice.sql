<<<<<<< HEAD
ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `is_manualbooking` VARCHAR(5) NULL DEFAULT 'false' AFTER `customer_reference2`;
ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `manualbooking_ref` VARCHAR(255) NULL DEFAULT NULL AFTER `is_manualbooking`;
=======
ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `is_manualbooking` VARCHAR(5) NULL DEFAULT 'false' AFTER `customer_reference2`;
ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `manualbooking_ref` VARCHAR(255) NULL DEFAULT NULL AFTER `is_manualbooking`;
>>>>>>> 6ccbd5d385b921ba03aa85d1a1faab0588c0adc1
