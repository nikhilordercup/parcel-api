ALTER TABLE `icargo_shipment_service`
ADD COLUMN `customer_reference1` VARCHAR(125) NULL DEFAULT NULL AFTER `terms_of_trade`,
ADD COLUMN `customer_reference2` VARCHAR(125) NULL DEFAULT NULL AFTER `customer_reference1`;
