SET NOCOUNT ON;
DECLARE @vmware_object_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @vmware_object_id = 10;
SET @time_frame = 6;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	e.entity_id,
	e.display_name as NAME,
	MIN(cast(s.sample_time as date)) as SAMPLE_TIME,
	min(a.cpu_usr + a.cpu_sys + a.cpu_wio) as MIN_CPU_USAGE,
	max(a.cpu_usr + a.cpu_sys + a.cpu_wio) as MAX_CPU_USAGE,
	avg(a.cpu_usr + a.cpu_sys + a.cpu_wio) as AVG_CPU_USAGE,
	min(c.numcpus) as NUM_CPU,
	min(u.mhz) as TOTAL_MHZ
FROM 
	performance_aggregate a
	JOIN performance_sample s
		ON (
			s.sample_time > @time_from AND
			a.sample_id = s.id		
		)
	JOIN entity e
		ON (s.uptimehost_id = e.entity_id)
	JOIN entity_configuration c
		ON (
			e.entity_id = @vmware_object_id AND
			e.entity_id = c.entity_id		
		)
	JOIN entity_configuration_cpu u
		ON c.entity_configuration_id = u.entity_configuration_id
GROUP BY
	e.entity_id,
	e.display_name,
	year(s.sample_time),
	month(s.sample_time), 
	day(s.sample_time)
ORDER BY
	SAMPLE_TIME