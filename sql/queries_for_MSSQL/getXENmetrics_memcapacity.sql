SET NOCOUNT ON;
DECLARE @element_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @element_id = 49;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	e.entity_id,
	e.name,
	p.name as NAME,
	avg(dd.value) as VAL
FROM erdc_base b
JOIN erdc_configuration c
	ON (
		b.name = 'XenServer' AND
		b.erdc_base_id = c.erdc_base_id			)
JOIN erdc_parameter p
	ON (
		(p.name = 'hostMemFree' or p.name = 'HostMemUsed') AND
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
	e.name,
	year(dd.sampletime),
	month(dd.sampletime), 
	day(dd.sampletime),
	p.name