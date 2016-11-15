SET NOCOUNT ON;
DECLARE @element_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @element_id = 49;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	e.entity_id,
	e.display_name as NAME,
	min(cast(dd.sampletime as date)) as SAMPLE_TIME,
	min(dd.value) as MIN_MEM_USAGE,
	max(dd.value) as MAX_MEM_USAGE,
	avg(dd.value) as AVG_MEM_USAGE
FROM erdc_base b
JOIN erdc_configuration c
	ON (
		b.name = 'XenServer' AND
		b.erdc_base_id = c.erdc_base_id			)
JOIN erdc_parameter p
	ON (
		p.name = 'hostMemUsed' AND
		b.erdc_base_id = p.erdc_base_id 
		)
JOIN erdc_decimal_data dd
	ON (
		dd.sampletime > @time_from AND
		p.erdc_parameter_id = dd.erdc_parameter_id
		)
JOIN erdc_instance i
	ON dd.erdc_instance_id = i.erdc_instance_id
JOIN entity e
	ON (
		e.entity_id = @element_id AND
		i.entity_id = e.entity_id
		)
GROUP BY 
	e.entity_id,
	e.display_name,
	year(dd.sampletime),
	month(dd.sampletime), 
	day(dd.sampletime)

/*
SELECT
			e.entity_id,
			e.display_name as NAME,
			date(dd.sampletime) as SAMPLE_TIME,
			min(dd.value) as MIN_MEM_USAGE,
			max(dd.value) as MAX_MEM_USAGE,
			avg(dd.value) as AVG_MEM_USAGE,
			day(dd.sampletime), 
			month(dd.sampletime), 
			year(dd.sampletime) 
		FROM
			erdc_base b, erdc_configuration c, erdc_parameter p, erdc_decimal_data dd, erdc_instance i, entity e
		WHERE
			b.name = 'XenServer' AND
			b.erdc_base_id = c.erdc_base_id AND
			b.erdc_base_id = p.erdc_base_id AND
			p.name = 'hostMemUsed' AND
			p.erdc_parameter_id = dd.erdc_parameter_id AND
			dd.erdc_instance_id = i.erdc_instance_id AND
			dd.sampletime > date_sub(now(),interval  " . $time_frame . " month) AND
			i.entity_id = e.entity_id AND
			e.entity_id = $element_id

		GROUP BY 
			e.entity_id,
			year(dd.sampletime),
			month(dd.sampletime), 
			day(dd.sampletime)
*/