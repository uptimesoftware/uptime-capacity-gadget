SELECT
	e.name, e.entity_id
FROM
	erdc_base b, erdc_configuration c, erdc_instance i, entity e
WHERE
	b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	c.id = i.configuration_id AND
	i.entity_id = e.entity_id