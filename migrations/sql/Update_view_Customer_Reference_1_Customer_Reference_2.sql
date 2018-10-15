CREATE
     OR REPLACE
VIEW `icargo_shipments_view` AS
    SELECT
        `S`.`warehouse_id` AS `warehouse_id`,
        `S`.`company_id` AS `company_id`,
        `S`.`instaDispatch_loadIdentity` AS `instaDispatch_loadIdentity`,
        `S`.`customer_id` AS `customer_id`,
        `SST`.`carrier` AS `carrier`,
        `SST`.`service_name` AS `service_name`,
        `SST`.`customer_reference1` AS `customer_reference1`,
        `SST`.`customer_reference2` AS `customer_reference2`,
        `S`.`instaDispatch_loadGroupTypeCode` AS `shipment_type`,
        `S`.`shipment_create_date` AS `booking_date`,
        `S`.`booked_by` AS `booked_by`,
        SUM(`SST`.`grand_total`) AS `amount`,
        `SST`.`isInvoiced` AS `isInvoiced`
    FROM
        (`icargo_shipment` `S`
        LEFT JOIN `icargo_shipment_service` `SST` ON ((`SST`.`load_identity` = `S`.`instaDispatch_loadIdentity`)))
    WHERE
        (((`S`.`current_status` = 'C')
            OR (`S`.`current_status` = 'O')
            OR (`S`.`current_status` = 'S')
            OR (`S`.`current_status` = 'D')
            OR (`S`.`current_status` = 'Ca'))
            AND ((`S`.`instaDispatch_loadGroupTypeCode` = 'SAME')
            OR (`S`.`instaDispatch_loadGroupTypeCode` = 'NEXT')))
    GROUP BY `S`.`instaDispatch_loadIdentity`;
