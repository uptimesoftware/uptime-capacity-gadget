SET nocount ON;
DECLARE @vmware_object_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @vmware_object_id = 58;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
		s.vmware_object_id,
		o.vmware_name as NAME,
		MIN(cast(s.sample_time as date)) as SAMPLE_TIME,
		min(a.cpu_usage) as MIN_CPU_USAGE,
		max(a.cpu_usage) as MAX_CPU_USAGE,
		avg(a.cpu_usage) as AVG_CPU_USAGE,
		min(a.cpu_total) as TOTAL_CAPACITY,
		year(s.sample_time) as year,
		month(s.sample_time) as month,
		day(s.sample_time) as day
	FROM
		vmware_perf_aggregate a
		JOIN vmware_perf_sample s
		ON (
			s.sample_id = a.sample_id AND
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
		ORDER BY
			year(s.sample_time),
			month(s.sample_time),
			day(s.sample_time)
