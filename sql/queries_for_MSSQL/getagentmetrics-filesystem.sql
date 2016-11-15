SET NOCOUNT ON;
DECLARE @vmware_object_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @vmware_object_id = 23;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	min(e.display_name) as NAME,
	min(cast(s.sample_time as date)) as SAMPLE_TIME,
	sum(a.total_size) as TOTAL_CAPACITY,
	sum(a.total_size) as TOTALSIZE ,
	min(a.space_used) as MIN_FILESYS_USAGE,
	max(a.space_used) as MAX_FILESYS_USAGE,
	avg(a.space_used) as AVG_FILESYS_USAGE
FROM
	performance_fscap a
JOIN performance_sample s
	ON (
		s.id = a.sample_id AND
		s.sample_time >  @time_from
	)
JOIN
	entity e
	ON (
		s.uptimehost_id = e.entity_id AND
		e.entity_id = @vmware_object_id
	)
GROUP BY
	sample_id
ORDER BY
	sample_id

/*
SELECT

e.display_name as NAME,
date(s.sample_time) as SAMPLE_TIME,
sum(a.total_size) as TOTAL_CAPACITY,
sum(a.total_size) as TOTALSIZE ,
	min(a.space_used) as MIN_FILESYS_USAGE,
	max(a.space_used) as MAX_FILESYS_USAGE,
	avg(a.space_used) as AVG_FILESYS_USAGE
FROM
	performance_fscap a, performance_sample s, entity e
WHERE
	s.id = a.sample_id AND
	s.uptimehost_id = e.entity_id AND
	s.sample_time > date_sub(now(),interval " . $time_frame . " month) AND
	e.entity_id = $vmware_object_id
GROUP BY
	sample_id
	*/

