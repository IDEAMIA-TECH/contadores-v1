ALTER TABLE client_xmls
ADD COLUMN impuesto varchar(10) DEFAULT NULL AFTER total_impuestos_trasladados,
ADD COLUMN tasa_o_cuota decimal(8,6) DEFAULT NULL AFTER impuesto,
ADD COLUMN tipo_factor varchar(10) DEFAULT NULL AFTER tasa_o_cuota; 