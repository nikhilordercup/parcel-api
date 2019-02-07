ALTER TABLE `icargo_shipment_service` ADD `provider` VARCHAR(255) NULL DEFAULT NULL COMMENT 'indicate label generated with what provider like postmen, coreprime etc.' AFTER `reconciled_code`;
