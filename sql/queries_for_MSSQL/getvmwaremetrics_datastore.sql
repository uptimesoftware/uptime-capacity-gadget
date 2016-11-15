SET nocount ON;
DECLARE @vmware_object_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @vmware_object_id = 8;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
	s.vmware_object_id,
	o.vmware_name as NAME,
	MIN(cast(s.sample_time as date)) as SAMPLE_TIME,
	min(u.usage_total) as MIN_USAGE,
	max(u.usage_total) as MAX_USAGE,
	avg(u.usage_total) as AVG_USAGE,
	min(u.provisioned) as MIN_PROV,
	max(u.provisioned) as MAX_PROV,
	avg(u.provisioned) as AVG_PROV,
	min(u.capacity) as TOTAL_CAPACITY
FROM
	vmware_perf_datastore_usage u
	JOIN vmware_perf_sample s
	ON (
		s.sample_id = u.sample_id AND
		s.sample_time > @time_from
	)
	JOIN vmware_object o
	ON (
		s.vmware_object_id = o.vmware_object_id AND
		s.vmware_object_id = @vmware_object_id
		)
GROUP BY
	s.vmware_object_id,
	o.vmware_name,
	year(s.sample_time),
	month(s.sample_time),
	day(s.sample_time)