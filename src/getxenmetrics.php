<?php 

//DISCLAIMER:
//LIMITATION OF LIABILITY: uptime software does not warrant that software obtained
//from the Grid will meet your requirements or that operation of the software will
//be uninterrupted or error free. By downloading and installing software obtained
//from the Grid you assume all responsibility for selecting the appropriate
//software to achieve your intended results and for the results obtained from use
//of software downloaded from the Grid. uptime software will not be liable to you
//or any party related to you for any loss or damages resulting from any claims,
//demands or actions arising out of use of software obtained from the Grid. In no
//event shall uptime software be liable to you or any party related to you for any
//indirect, incidental, consequential, special, exemplary or punitive damages or
//lost profits even if uptime software has been advised of the possibility of such
//damages.

// Set the JSON header
header("Content-type: text/json");

include("uptimeDB.php");

if (isset($_GET['query_type'])){
	$query_type = $_GET['query_type'];
}
if (isset($_GET['uptime_offset'])){
	$offset = $_GET['uptime_offset'];
}
if (isset($_GET['time_frame'])){
	$time_frame = $_GET['time_frame'];
}
else
{
	$time_frame = 3;
}
if (isset($_GET['dailyVal'])){
	$dailyVal = $_GET['dailyVal'];
}
if (isset($_GET['element'])){
	$element_id = $_GET['element'];
}
$json = array();
$oneElement = array();
$performanceData = array();
//date_default_timezone_set('UTC');

$db = new uptimeDB;
if ($db->connectDB())
{
	echo "";

}
else
{
 echo "unable to connect to DB exiting";	
 exit(1);
}


if ($query_type == "xenserver-Mem")
{

	$min_mem_usage_array = array();
	$max_mem_usage_array = array();
	$avg_mem_usage_array = array();


	$getXenServerMemUsedsql = "SELECT
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
			dd.sampletime > date_sub(now(),interval  ". $time_frame . " month) AND
			i.entity_id = e.entity_id AND
			e.entity_id = $element_id

		GROUP BY 
			e.entity_id,
			year(dd.sampletime),
			month(dd.sampletime), 
			day(dd.sampletime)";


	$getXenserverMemCapcitySql = "SELECT
	e.entity_id,
	e.name,
	p.name as NAME,
	avg(dd.value) as VAL
FROM
	erdc_base b, erdc_configuration c, erdc_parameter p, erdc_decimal_data dd, erdc_instance i, entity e
WHERE
    b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	b.erdc_base_id = p.erdc_base_id AND
	(p.name = 'hostMemFree' or p.name = 'HostMemUsed') AND
	p.erdc_parameter_id = dd.erdc_parameter_id AND
	dd.erdc_instance_id = i.erdc_instance_id AND
	dd.sampletime >= CURDATE() AND
	i.entity_id = e.entity_id AND
	e.entity_id = $element_id 
GROUP BY 
	e.entity_id,
	year(dd.sampletime),
	month(dd.sampletime), 
	day(dd.sampletime),
	p.name";

	$capacitySQLResults = $db->execQuery($getXenserverMemCapcitySql);

	$myhostMemUsed = 0;
	$myhostMemFree = 0;
	foreach ($capacitySQLResults as $index => $row) 
	{
		if ($row['NAME'] == 'hostMemFree')
		{
			$myhostMemFree = $row['VAL'];
		}
		elseif ( $row['NAME'] == 'hostMemUsed')
		{
			$myhostMemUsed = $row['VAL'];
		}
	}

	$capacity = $myhostMemUsed + $myhostMemFree;




	$hostMemResults = $db->execQuery($getXenServerMemUsedsql);

	$name = $hostMemResults[0]['NAME'];
	$memScale = 1;

	foreach ($hostMemResults as $index => $row) {
		$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
		$x = $sample_time * 1000;

		$data = array($x, floatval($row['MIN_MEM_USAGE'] * $memScale ));
		array_push($min_mem_usage_array, $data);

		$data = array($x, floatval($row['MAX_MEM_USAGE'] * $memScale ));
		array_push($max_mem_usage_array, $data);

		$data = array($x, floatval($row['AVG_MEM_USAGE'] * $memScale ));
		array_push($avg_mem_usage_array, $data);
	}


	if ($dailyVal == 'min')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Min",
			'capacity' => $capacity,
			'unit' => 'MB',
			'series' => $min_mem_usage_array
			);
	}

	if ($dailyVal == 'max')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Max",
			'capacity' => $capacity,
			'unit' => 'MB',
			'series' => $max_mem_usage_array
			);
	}

	if ($dailyVal == 'avg')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Avg",
			'capacity' => $capacity,
			'unit' => 'MB',
			'series' => $avg_mem_usage_array
			);
	}


	if (count($my_series['series']) > 0)
	{
		array_push($json, $my_series);
	}
	if (count($json) > 0)
	{
		echo json_encode($json);
	}
	else
	{
		echo "No Data";
	}
}
elseif ($query_type == "vmware-Cpu")
{

	$min_cpu_usage_array = array();
	$max_cpu_usage_array = array();
	$avg_cpu_usage_array = array();



	$sql = "SELECT 
		s.vmware_object_id, 
		o.vmware_name as NAME,
		date(s.sample_time) as SAMPLE_TIME,
		min(a.cpu_usage) as MIN_CPU_USAGE,
		max(a.cpu_usage) as MAX_CPU_USAGE,
		avg(a.cpu_usage) as AVG_CPU_USAGE,
		a.cpu_total as TOTAL_CAPACITY,
		day(s.sample_time), 
		month(s.sample_time), 
		year(s.sample_time) 
	FROM 
		vmware_perf_aggregate a, vmware_perf_sample s, vmware_object o
	WHERE 
		s.sample_id = a.sample_id AND 
		s.vmware_object_id = o.vmware_object_id AND
		s.sample_time > date_sub(now(),interval  ". $time_frame . " month) AND
		s.vmware_object_id = $vmware_object_id

	GROUP BY 
		s.vmware_object_id,
		year(s.sample_time),
		month(s.sample_time), 
		day(s.sample_time)";

	$hostCpuResults = $db->execQuery($sql);

	$name = $hostCpuResults[0]['NAME'];
	$cpuScale = 1000;

	foreach ($hostCpuResults as $index => $row) {
		$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
		$x = $sample_time * 1000;

		$data = array($x, floatval($row['MIN_CPU_USAGE'] / $cpuScale ));
		array_push($min_cpu_usage_array, $data);

		$data = array($x, floatval($row['MAX_CPU_USAGE'] / $cpuScale ));
		array_push($max_cpu_usage_array, $data);

		$data = array($x, floatval($row['AVG_CPU_USAGE'] / $cpuScale ));
		array_push($avg_cpu_usage_array, $data);
	}

	$capacity = floatval($hostCpuResults[0]['TOTAL_CAPACITY'] / $cpuScale);

	if ($dailyVal == 'min')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Min",
			'capacity' => $capacity,
			'unit' => 'GHz',
			'series' => $min_cpu_usage_array
			);
	}

	if ($dailyVal == 'max')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Max",
			'capacity' => $capacity,
			'unit' => 'GHz',
			'series' => $max_cpu_usage_array
			);
	}

	if ($dailyVal == 'avg')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Avg",
			'capacity' => $capacity,
			'unit' => 'GHz',
			'series' => $avg_cpu_usage_array
			);
	}

	if (count($my_series['series']) > 0)
	{
		array_push($json, $my_series);
	}
	if (count($json) > 0)
	{
		echo json_encode($json);
	}
	else
	{
		echo "No Data";
	}





}

elseif ( $query_type == "xenserver-DiskUsed")
{

	$min_disk_usage_array = array();
	$max_disk_usage_array = array();
	$avg_disk_usage_array = array();




	$diskUsedSql = "SELECT
		e.entity_id,
		e.name,
		ro.id,
		ro.object_name as NAME,
		min(rov.value) as MIN_USAGE,
		max(rov.value) as MAX_USAGE,
		avg(rov.value) as AVG_USAGE,
		date(rov.sample_time) as SAMPLE_TIME,
		day(rov.sample_time), 
		month(rov.sample_time), 
		year(rov.sample_time) 
	FROM
		erdc_base b, erdc_configuration c, erdc_instance i, entity e, ranged_object ro, ranged_object_value rov
	WHERE
		b.name = 'XenServer' AND
		b.erdc_base_id = c.erdc_base_id AND
		c.id = i.configuration_id AND
		i.entity_id = e.entity_id AND
		i.erdc_instance_id = ro.instance_id AND
		ro.id = rov.ranged_object_id AND
		rov.name = 'diskUsed' AND
		rov.sample_time > date_sub(now(),interval  ". $time_frame . " month) AND
		e.entity_id = $element_id AND
		ro.object_name = 'NFS_ISO_library'
	GROUP BY
		ro.id,
		e.entity_id,
		year(rov.sample_time),
		month(rov.sample_time), 
		day(rov.sample_time)";



	$diskCapacitySql = "SELECT
	e.entity_id,
	e.name,
	ro.id,
	ro.object_name,
	rov.name as NAME,
	avg(rov.value) as VAL,
	date(rov.sample_time)
FROM
	erdc_base b, erdc_configuration c, erdc_instance i, entity e, ranged_object ro, ranged_object_value rov
WHERE
	b.name = 'XenServer' AND
	b.erdc_base_id = c.erdc_base_id AND
	c.id = i.configuration_id AND
	i.entity_id = e.entity_id AND
	i.erdc_instance_id = ro.instance_id AND
	ro.id = rov.ranged_object_id AND
	(rov.name = 'diskFree' or rov.name = 'diskUsed' ) AND
	ro.object_name = 'NFS_ISO_library' AND
	rov.sample_time >= CURDATE()
GROUP BY
	ro.id,
	e.entity_id,
	year(rov.sample_time),
	month(rov.sample_time), 
	day(rov.sample_time),
	rov.name";



	$diskCapacityResults = $db->execQuery($diskCapacitySql);

	$mydiskUsed = 0;
	$mydiskFree = 0;
	foreach ($diskCapacityResults as $index => $row) 
	{
		if ($row['NAME'] == 'diskFree')
		{
			$mydiskFree = $row['VAL'];
		}
		elseif ( $row['NAME'] == 'diskUsed')
		{
			$mydiskUsed = $row['VAL'];
		}
	}

	$capacity = $mydiskUsed + $mydiskFree;


	$diskUsedResults = $db->execQuery($diskUsedSql);

	$name = $diskUsedResults[0]['NAME'];
	$datastoreScale = 1;

	foreach ($diskUsedResults as $index => $row) {
		$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
		$x = $sample_time * 1000;

		$data = array($x, floatval($row['MIN_USAGE'] / $datastoreScale ));
		array_push($min_disk_usage_array, $data);

		$data = array($x, floatval($row['MAX_USAGE'] / $datastoreScale ));
		array_push($max_disk_usage_array, $data);

		$data = array($x, floatval($row['AVG_USAGE'] / $datastoreScale ));
		array_push($avg_disk_usage_array, $data);
	}


	if ($dailyVal == 'min')
	{
		$usage_series = array(
			'name' => $name . " - Daily Actual Min",
			'capacity' => $capacity,
			'unit' => 'GBs',
			'series' => $min_disk_usage_array
			);
	}

	if ($dailyVal == 'max')
	{
		$usage_series = array(
			'name' => $name . " - Daily Actual Max",
			'capacity' => $capacity,
			'unit' => 'GBs',
			'series' => $max_disk_usage_array
			);
	}

	if ($dailyVal == 'avg')
	{
		$usage_series = array(
			'name' => $name . " - Daily Actual Avg",
			'capacity' => $capacity,
			'unit' => 'GBs',
			'series' => $avg_disk_usage_array
			);
	}

	if (count($usage_series['series']) > 0)
	{
		array_push($json, $usage_series);
	}
	if (count($json) > 0)
	{
		echo json_encode($json);
	}
	else
	{
		echo "No Data";
	}


}

	


    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    }

?>
