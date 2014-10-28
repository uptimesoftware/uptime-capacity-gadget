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
if (isset($_GET['metricType'])){
	$metricType = $_GET['metricType'];
}
if (isset($_GET['element'])){
	$vmware_object_id = $_GET['element'];
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


if ($query_type == "Mem")
{

	$min_mem_usage_array = array();
	$max_mem_usage_array = array();
	$avg_mem_usage_array = array();


	$sql = "SELECT 
		s.vmware_object_id, 
		o.vmware_name as NAME,
		date(s.sample_time) as SAMPLE_TIME,
		min(a.memory_usage) as MIN_MEM_USAGE,
		max(a.memory_usage) as MAX_MEM_USAGE,
		avg(a.memory_usage) as AVG_MEM_USAGE,
		a.memory_total		as TOTAL_CAPACITY,
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

	$hostMemResults = $db->execQuery($sql);

	$name = $hostMemResults[0]['NAME'];

	foreach ($hostMemResults as $index => $row) {
		$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
		$x = $sample_time * 1000;

		$data = array($x, floatval($row['MIN_MEM_USAGE']));
		array_push($min_mem_usage_array, $data);

		$data = array($x, floatval($row['MAX_MEM_USAGE']));
		array_push($max_mem_usage_array, $data);

		$data = array($x, floatval($row['AVG_MEM_USAGE']));
		array_push($avg_mem_usage_array, $data);
	}

	$capacity = intval($hostMemResults[0]['TOTAL_CAPACITY']);

	if ($metricType == 'min')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Min",
			'capacity' => $capacity,
			'series' => $min_mem_usage_array
			);
	}

	if ($metricType == 'max')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Max",
			'capacity' => $capacity,
			'series' => $max_mem_usage_array
			);
	}

	if ($metricType == 'avg')
	{
		$my_series = array(
			'name' => $name . " - Daily Mem Avg",
			'capacity' => $capacity,
			'series' => $avg_mem_usage_array
			);
	}


	array_push($json, $my_series);
	echo json_encode($json);
}
elseif ($query_type == "Cpu")
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

	foreach ($hostCpuResults as $index => $row) {
		$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
		$x = $sample_time * 1000;

		$data = array($x, floatval($row['MIN_CPU_USAGE']));
		array_push($min_cpu_usage_array, $data);

		$data = array($x, floatval($row['MAX_CPU_USAGE']));
		array_push($max_cpu_usage_array, $data);

		$data = array($x, floatval($row['AVG_CPU_USAGE']));
		array_push($avg_cpu_usage_array, $data);
	}

	$capacity = intval($hostCpuResults[0]['TOTAL_CAPACITY']);

	if ($metricType == 'min')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Min",
			'capacity' => $capacity,
			'series' => $min_cpu_usage_array
			);
	}

	if ($metricType == 'max')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Max",
			'capacity' => $capacity,
			'series' => $max_cpu_usage_array
			);
	}

	if ($metricType == 'avg')
	{
		$my_series = array(
			'name' => $name . " - Daily Cpu Avg",
			'capacity' => $capacity,
			'series' => $avg_cpu_usage_array
			);
	}


	array_push($json, $my_series);
	echo json_encode($json);





}

	


    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    }

?>
