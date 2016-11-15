SET NOCOUNT ON;
DECLARE @element_id int;
DECLARE @time_frame int;
DECLARE @time_from date;
DECLARE @datastore_name varchar(255);

SET @element_id = 49;
SET @datastore_name = 'Centos63';
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	e.entity_id,
	e.display_name as ENTITY_NAME,
	ro.id,
	ro.object_name as OBJ_NAME,
	min(rov.value) as MIN_USAGE,
	max(rov.value) as MAX_USAGE,
	avg(rov.value) as AVG_USAGE,
	min(cast(rov.sample_time as date)) as SAMPLE_TIME,
	day(rov.sample_time), 
	month(rov.sample_time), 
	year(rov.sample_time) 
FROM erdc_base b
JOIN erdc_configuration c
	ON (
		b.name = 'XenServer' AND
		b.erdc_base_id = c.erdc_base_id
		)
JOIN erdc_parameter p
	ON b.erdc_base_id = p.erdc_base_id
JOIN erdc_decimal_data dd
	ON (
		dd.sampletime > @time_from AND
		p.erdc_parameter_id = dd.erdc_parameter_id
		)
JOIN erdc_instance i
	ON c.id = i.configuration_id
JOIN entity e
	ON (
		e.entity_id = @element_id AND
		i.entity_id = e.entity_id
		)
JOIN ranged_object ro
	ON (
		ro.object_name = @datastore_name AND
		i.erdc_instance_id = ro.instance_id
		)
JOIN ranged_object_value rov
	ON (
		rov.name = 'diskUsed' AND
		rov.sample_time > @time_from AND
		ro.id = rov.ranged_object_id
	)
GROUP BY
	ro.id,
	e.display_name,
	e.entity_id,
	ro.object_name,
	year(rov.sample_time),
	month(rov.sample_time), 
	day(rov.sample_time)