SELECT
	s.vmware_object_id,
	o.vmware_name as NAME,
	max(s.sample_time) as SAMPLE_TIME,
	min(u.usage_total) as MIN_USAGE,
	max(u.usage_total) as MAX_USAGE,
	avg(u.usage_total) as AVG_USAGE,
	min(u.provisioned) as MIN_PROV,
	max(u.provisioned) as MAX_PROV,
	avg(u.provisioned) as AVG_PROV,
	u.capacity as TOTAL_CAPACITY,
	max(extract (day from s.sample_time)) as day,
	max(extract (month from s.sample_time)) as month,
	max(extract (year from s.sample_time)) as year
FROM
	vmware_perf_datastore_usage u, vmware_perf_sample s, vmware_object o
WHERE
	s.sample_id = u.sample_id AND
	s.vmware_object_id = o.vmware_object_id AND
	s.sample_time > ADD_MONTHS(SYSDATE, -3) AND
	s.vmware_object_id = 5560
GROUP BY
	s.vmware_object_id,
	o.vmware_name,
	u.capacity,
	extract (year from s.sample_time),
	extract (month from s.sample_time),
	extract (day from s.sample_time)	
  