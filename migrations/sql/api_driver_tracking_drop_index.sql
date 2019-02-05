ALTER TABLE icargo_api_driver_tracking DROP INDEX driver_id;
INSERT INTO `icargo_shipment_tracking_code` (`id`, `shipment_code`, `tracking_code`) VALUES (NULL, 'OUTFORDELIVERY', 'OUTFORDELIVERY');