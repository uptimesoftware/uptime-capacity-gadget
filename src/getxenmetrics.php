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
if (isset($_GET['query_type'])) {
    $query_type = $_GET['query_type'];
}
if (isset($_GET['uptime_offset'])) {
    $offset = $_GET['uptime_offset'];
}
if (isset($_GET['time_frame'])) {
    $time_frame = intval($_GET['time_frame']);
} else {
    $time_frame = 3;
}
if (isset($_GET['dailyVal'])) {
    $dailyVal = $_GET['dailyVal'];
}
if (isset($_GET['element'])) {
    $element_id = $_GET['element'];
}
$json = array();
$oneElement = array();
$performanceData = array();
//date_default_timezone_set('UTC');
$db = new uptimeDB;
if ($db->connectDB()) {
    echo "";
} else {
    echo "unable to connect to DB exiting";
    exit(1);
}
if ($query_type == "xenserver-Mem") {
    $min_mem_usage_array = array();
    $max_mem_usage_array = array();
    $avg_mem_usage_array = array();
	$hostMemResults = array();
    $getXenServerMemUsedsql = $db->DB->prepare("
        SET NOCOUNT ON;
        DECLARE @element_id int;
        DECLARE @time_frame int;
        DECLARE @time_from date;
        SET @element_id = $element_id;
        SET @time_frame = $time_frame;
        SET @time_from = DATEADD(month, -@time_frame, GETDATE())
        SELECT
                e.entity_id,
                e.display_name as NAME,
                min(cast(dd.sampletime as date)) as SAMPLE_TIME,
                min(dd.value) as MIN_MEM_USAGE,
                max(dd.value) as MAX_MEM_USAGE,
                avg(dd.value) as AVG_MEM_USAGE
        FROM erdc_base b
        JOIN erdc_configuration c
                ON (
                        b.name = 'XenServer' AND
                        b.erdc_base_id = c.erdc_base_id			)
        JOIN erdc_parameter p
                ON (
                        p.name = 'hostMemUsed' AND
                        b.erdc_base_id = p.erdc_base_id 
                        )
        JOIN erdc_decimal_data dd
                ON (
                        dd.sampletime > @time_from AND
                        p.erdc_parameter_id = dd.erdc_parameter_id
                        )
        JOIN erdc_instance i
                ON dd.erdc_instance_id = i.erdc_instance_id
        JOIN entity e
                ON (
                        e.entity_id = @element_id AND
                        i.entity_id = e.entity_id
                        )
        GROUP BY 
                e.entity_id,
                e.display_name,
                year(dd.sampletime),
                month(dd.sampletime), 
                day(dd.sampletime)");
    
    $getXenServerMemUsedmysql = $db->DB->prepare("SELECT
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
			dd.sampletime > date_sub(now(),interval  " . $time_frame . " month) AND
			i.entity_id = e.entity_id AND
			e.entity_id = $element_id
		GROUP BY 
			e.entity_id,
			year(dd.sampletime),
			month(dd.sampletime), 
			day(dd.sampletime)");
    $getXenserverMemCapacitySql =$db->DB->prepare("
        SET NOCOUNT ON;
        DECLARE @element_id int;
        DECLARE @time_frame int;
        DECLARE @time_from date;
        SET @element_id = $element_id;
        SET @time_frame = $time_frame;
        SET @time_from = DATEADD(month, -@time_frame, GETDATE())
        SELECT
            e.entity_id,
            e.name,
            p.name as NAME,
            avg(dd.value) as VAL
        FROM erdc_base b
        JOIN erdc_configuration c
            ON (
                b.name = 'XenServer' AND
                b.erdc_base_id = c.erdc_base_id
                )
        JOIN erdc_parameter p
            ON (
                (p.name = 'hostMemFree' or p.name = 'HostMemUsed') AND
                b.erdc_base_id = p.erdc_base_id 
                )
        JOIN erdc_decimal_data dd
            ON (
                dd.sampletime > @time_from AND
                p.erdc_parameter_id = dd.erdc_parameter_id
                )
        JOIN erdc_instance i
            ON dd.erdc_instance_id = i.erdc_instance_id
        JOIN entity e
            ON (
                e.entity_id = @element_id AND
                i.entity_id = e.entity_id
                )
        GROUP BY 
            e.entity_id,
            e.name,
            year(dd.sampletime),
            month(dd.sampletime), 
            day(dd.sampletime),
            p.name");
    
    $getXenserverMemCapcitymySql = $db->DB->prepare("
        SELECT
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
            p.name");
	if ($db->dbType == 'mysql'){
		$capacitySQLResults = $db->execQuery($getXenserverMemCapcitymySql);
	} else{
		$capacitySQLResults = $db->execQuery($getXenserverMemCapacitySql);
	}
    $myhostMemUsed = 0;
    $myhostMemFree = 0;
    foreach ((array)$capacitySQLResults as $index => $row) {
        if ($row['NAME'] == 'hostMemFree') {
            $myhostMemFree = $row['VAL'];
        } elseif ($row['NAME'] == 'hostMemUsed') {
            $myhostMemUsed = $row['VAL'];
        }
    }
    $capacity = $myhostMemUsed + $myhostMemFree;
	if ($db->dbType == 'mysql'){
		$hostMemResults = $db->execQuery($getXenServerMemUsedmysql);
	} else{
		$hostMemResults = $db->execQuery($getXenServerMemUsedsql);
	}
	if (!isset($hostMemResults)) {
		break;
	}
	
    $name = $hostMemResults[0]['NAME'];
    $memScale = 1;
    foreach ((array)$hostMemResults as $index => $row) {
        $sample_time = strtotime($row['SAMPLE_TIME']) - $offset;
        $x = $sample_time * 1000;
        $data = array($x, floatval($row['MIN_MEM_USAGE'] * $memScale));
        array_push($min_mem_usage_array, $data);
        $data = array($x, floatval($row['MAX_MEM_USAGE'] * $memScale));
        array_push($max_mem_usage_array, $data);
        $data = array($x, floatval($row['AVG_MEM_USAGE'] * $memScale));
        array_push($avg_mem_usage_array, $data);
    }
    if ($dailyVal == 'min') {
        $my_series = array(
            'name' => $name . " - Daily Mem Min",
            'capacity' => $capacity,
            'unit' => 'MB',
            'series' => $min_mem_usage_array
        );
    }
    if ($dailyVal == 'max') {
        $my_series = array(
            'name' => $name . " - Daily Mem Max",
            'capacity' => $capacity,
            'unit' => 'MB',
            'series' => $max_mem_usage_array
        );
    }
    if ($dailyVal == 'avg') {
        $my_series = array(
            'name' => $name . " - Daily Mem Avg",
            'capacity' => $capacity,
            'unit' => 'MB',
            'series' => $avg_mem_usage_array
        );
    }
    if (count((array)$my_series['series']) > 0) {
        array_push($json, $my_series);
    }
    if (count((array)$json) > 0) {
        echo json_encode($json);
    } else {
        echo "No Data";
    }
} elseif ($query_type == "xenserver-DiskUsed") {
    //we'll need to split the $element_id into the real entity_id and the datastore name
    $element_id_split = explode("-", $element_id);
    $element_id = $element_id_split[0];
    $datastore_name = $element_id_split[1];
    $min_disk_usage_array = array();
    $max_disk_usage_array = array();
    $avg_disk_usage_array = array();
	$diskUsedResults = array();
    $diskUsedSql =$db->DB->prepare("
        SET NOCOUNT ON;
        DECLARE @element_id int;
        DECLARE @time_frame int;
        DECLARE @time_from date;
        DECLARE @datastore_name varchar(255);
        SET @element_id = $element_id;
        SET @datastore_name = '$datastore_name';
        SET @time_frame = $time_frame;
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
            day(rov.sample_time)");
    $diskUsedmySql = $db->DB->prepare("SELECT
		e.entity_id,
		e.display_name as ENTITY_NAME,
		ro.id,
		ro.object_name as OBJ_NAME,
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
		ro.object_name = '$datastore_name'
	GROUP BY
		ro.id,
		e.entity_id,
		year(rov.sample_time),
		month(rov.sample_time), 
		day(rov.sample_time)");
$diskCapacitySql = $db->DB->prepare("
        SET NOCOUNT ON;
        DECLARE @element_id int;
        DECLARE @time_frame int;
        DECLARE @time_from date;
        DECLARE @datastore_name varchar(255);
        SET @element_id = $element_id;
        SET @datastore_name = '$datastore_name';
        SET @time_frame = $time_frame;
        SET @time_from = DATEADD(month, -@time_frame, GETDATE())
        SELECT
            e.entity_id,
            e.name,
            ro.id,
            ro.object_name,
            rov.name as NAME,
            avg(rov.value) as VAL,
            min(cast(rov.sample_time as date)) as SAMPLE_TIME
        FROM erdc_base b
        JOIN erdc_configuration c
            ON (
                b.name = 'XenServer' AND
                b.erdc_base_id = c.erdc_base_id
            )
        JOIN erdc_parameter p
            ON (
                (p.name = 'hostMemFree' or p.name = 'HostMemUsed') AND
                b.erdc_base_id = p.erdc_base_id 
            )
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
                (rov.name = 'diskFree' or rov.name = 'diskUsed' ) AND
                rov.sample_time > @time_from AND
                ro.id = rov.ranged_object_id
            )
        GROUP BY
            ro.id,
            ro.object_name,
            e.display_name,
            e.entity_id,
            e.name,
            year(rov.sample_time),
            month(rov.sample_time), 
            day(rov.sample_time),
            rov.name");
    $diskCapacitymySql = $db->DB->prepare("
    SELECT
	e.entity_id,
	e.name AS ENTITY_NAME,
	ro.id,
	ro.object_name AS OBJ_NAME,
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
	ro.object_name = '$datastore_name' AND
	rov.sample_time >= CURDATE()
    GROUP BY
	ro.id,
	e.entity_id,
	year(rov.sample_time),
	month(rov.sample_time), 
	day(rov.sample_time),
	rov.name");
	if ($db->dbType == 'mysql'){
		$diskCapacityResults = $db->execQuery($diskCapacitymySql);
	} else{
		$diskCapacityResults = $db->execQuery($diskCapacitySql);
	}
    $mydiskUsed = 0;
    $mydiskFree = 0;
    foreach ((array)$diskCapacityResults as $index => $row) {
        if ($row['NAME'] == 'diskFree') {
            $mydiskFree = $row['VAL'];
        } elseif ($row['NAME'] == 'diskUsed') {
            $mydiskUsed = $row['VAL'];
        }
    }
    $capacity = $mydiskUsed + $mydiskFree;
	if ($db->dbType == 'mysql'){
		$diskUsedResults = $db->execQuery($diskUsedmySql);
	} else{
		$diskUsedResults = $db->execQuery($diskUsedSql);
	}
	if (!isset($diskUsedResults)) {
		break;
	}
	
    $name = $diskUsedResults[0]['ENTITY_NAME'] . " - " . $diskUsedResults[0]['OBJ_NAME'];
    $datastoreScale = 1;
    foreach ($diskUsedResults as $index => $row) {
        $sample_time = strtotime($row['SAMPLE_TIME']) - $offset;
        $x = $sample_time * 1000;
        $data = array($x, floatval($row['MIN_USAGE'] / $datastoreScale));
        array_push($min_disk_usage_array, $data);
        $data = array($x, floatval($row['MAX_USAGE'] / $datastoreScale));
        array_push($max_disk_usage_array, $data);
        $data = array($x, floatval($row['AVG_USAGE'] / $datastoreScale));
        array_push($avg_disk_usage_array, $data);
    }
    if ($dailyVal == 'min') {
        $usage_series = array(
            'name' => $name . " - Daily Actual Min",
            'capacity' => $capacity,
            'unit' => 'GBs',
            'series' => $min_disk_usage_array
        );
    }
    if ($dailyVal == 'max') {
        $usage_series = array(
            'name' => $name . " - Daily Actual Max",
            'capacity' => $capacity,
            'unit' => 'GBs',
            'series' => $max_disk_usage_array
        );
    }
    if ($dailyVal == 'avg') {
        $usage_series = array(
            'name' => $name . " - Daily Actual Avg",
            'capacity' => $capacity,
            'unit' => 'GBs',
            'series' => $avg_disk_usage_array
        );
    }
    if (count((array)$usage_series['series']) > 0) {
        array_push($json, $usage_series);
    }
    if (count((array)$json) > 0) {
        echo json_encode($json);
    } else {
        echo "No Data";
    }
}
// Unsupported request
else { echo "Error: Unsupported Request '$query_type'" . "</br>";}
// close sessions
$db->closeDB();
?>