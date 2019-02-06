ALTER TABLE `icargo_shipments_pod` ADD `tracking_id` INT NOT NULL DEFAULT '0' AFTER `is_custom_create`;
ALTER TABLE `icargo_shipment_tracking` ADD `pod_id` INT NOT NULL DEFAULT '0' AFTER `custom_tracking`;
