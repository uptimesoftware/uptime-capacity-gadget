SET nocount ON;
DECLARE @vmware_object_id int;
DECLARE @time_frame int;
DECLARE @time_from date;

SET @vmware_object_id = 71;
SET @time_frame = 1;
SET @time_from = DATEADD(month, -@time_frame, GETDATE())

SELECT
		s.vmware_object_id,
		o.vmware_name as NAME,
		MIN(cast(s.sample_time as date)) as SAMPLE_TIME,
		min(a.memory_usage) as MIN_MEM_USAGE,
		max(a.memory_usage) as MAX_MEM_USAGE,
		CAST(avg(a.memory_usage) as INT) as AVG_MEM_USAGE,
		min(a.memory_total) as TOTAL_CAPACITY,
		(SELECT	vpa.memory_total
			FROM	vmware_object vo
			INNER JOIN vmware_latest_basic_sample vlbs
			ON vo.vmware_object_id = vlbs.vmware_object_id
			INNER JOIN vmware_perf_aggregate vpa
			ON vlbs.sample_id = vpa.sample_id
			WHERE vlbs.vmware_object_id = @vmware_object_id) AS CURR_CAPACITY,
		year(s.sample_time),
		month(s.sample_time),
		day(s.sample_time)
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
