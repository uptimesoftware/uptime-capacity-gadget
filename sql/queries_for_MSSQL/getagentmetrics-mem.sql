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
	min(a.free_mem) as MIN_MEM_USAGE,
	max(a.free_mem) as MAX_MEM_USAGE,
	avg(a.free_mem) as AVG_MEM_USAGE,
	min(c.memsize) as TOTAL_CAPACITY
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

GROUP BY
	e.entity_id,
	e.display_name,
	year(s.sample_time),
	month(s.sample_time), 
	day(s.sample_time)