SELECT
	e.display_name as NAME,
	e.entity_id as ID,
	ro.object_name as OBJ_NAME
FROM
erdc_base b
JOIN erdc_configuration c
	ON (
		b.name = 'XenServer' AND
		b.erdc_base_id = c.erdc_base_id 
		)
JOIN erdc_instance i
	ON c.id = i.configuration_id
JOIN entity e
	ON i.entity_id = e.entity_id
JOIN ranged_object ro
	ON i.erdc_instance_id = ro.instance_id
GROUP BY
e.entity_id,
e.display_name,
ro.object_name