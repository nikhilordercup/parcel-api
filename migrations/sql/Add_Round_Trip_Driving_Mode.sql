ALTER TABLE `icargo_customer_info` 
ADD COLUMN `round_trip` VARCHAR(3) NOT NULL DEFAULT 'NO' AFTER `auto_label_print`,
ADD COLUMN `driving_mode` VARCHAR(15) NOT NULL DEFAULT 'bicycling' AFTER `round_trip`;
