SELECT
	e.entity_id,
	e.name,
	ro.id,
	ro.object_name,
	min(rov.value),
	max(rov.value),
	avg(rov.value),
	date(rov.sample_time),
	day(rov.sample_time), 
	month(rov.sample_time), 
	year(rov.sample_time) 
FROM
	erdc_base b, erdc_configuration c, erdc_instance i, entity e, ranged_object ro, ranged_object_value rov
WHERE
	b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	c.id = i.configuration_id AND
	i.entity_id = e.entity_id AND
	i.erdc_instance_id = ro.instance_id AND
	ro.id = rov.ranged_object_id AND
	rov.name = 'diskFree' AND
	rov.sample_time >= '2014-09-20 14:18:01' AND 
	rov.sample_time < '2014-10-23 14:18:01'
GROUP BY
	ro.id,
	e.entity_id,
	year(rov.sample_time),
	month(rov.sample_time), 
	day(rov.sample_time)