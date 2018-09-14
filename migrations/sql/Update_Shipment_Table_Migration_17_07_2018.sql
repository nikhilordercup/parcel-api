ALTER TABLE `icargo_shipment` 
ADD COLUMN `action_by` VARCHAR(15) NULL DEFAULT NULL AFTER `is_internal`;
