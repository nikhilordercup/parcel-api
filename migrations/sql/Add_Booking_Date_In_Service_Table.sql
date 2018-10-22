UPDATE icargo_shipment_service AS SST, icargo_shipment AS ST SET SST.create_date=ST.shipment_create_date WHERE ST.instaDispatch_loadidentity=SST.load_identity AND ST.shipment_service_type='P';
