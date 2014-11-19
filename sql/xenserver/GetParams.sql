SELECT
	p.name,
	p.erdc_parameter_id,
	p.data_type_id
FROM
	erdc_base b, erdc_configuration c, erdc_parameter p
WHERE
	b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	b.erdc_base_id = p.erdc_base_id