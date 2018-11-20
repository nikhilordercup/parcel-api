ALTER TABLE `icargo_invoice_vs_docket` ADD `reference1` VARCHAR(255) NULL DEFAULT NULL AFTER `chargable_unit`, ADD `reference2` VARCHAR(255) NULL DEFAULT NULL AFTER `reference1`;
