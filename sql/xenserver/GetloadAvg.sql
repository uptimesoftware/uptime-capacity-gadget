SELECT
	e.entity_id,
	e.name,
	date(dd.sampletime),
	min(dd.value),
	max(dd.value),
	avg(dd.value),
	day(dd.sampletime), 
	month(dd.sampletime), 
	year(dd.sampletime) 
FROM
	erdc_base b, erdc_configuration c, erdc_parameter p, erdc_decimal_data dd, erdc_instance i, entity e
WHERE
	b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	b.erdc_base_id = p.erdc_base_id AND
	p.name = 'loadAvg' AND
	p.erdc_parameter_id = dd.erdc_parameter_id AND
	dd.erdc_instance_id = i.erdc_instance_id AND
	i.entity_id = e.entity_id AND
	dd.sampletime >= '2014-09-20 14:18:01' AND 
	dd.sampletime < '2014-10-23 14:18:01'
GROUP BY 
	e.entity_id,
	year(dd.sampletime),
	month(dd.sampletime), 
	day(dd.sampletime)