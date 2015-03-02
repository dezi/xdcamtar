<?php

include("./json.php");
include("./smem.php");

header("Content-type: text/plain; charset=utf-8");

//
// Read input content.
//

$pd = fopen("php://input","r");

$json = "";

while (($line = fgets($pd)) !== false)
{
	$json .= $line;
}

fclose($pd);

//
// Decode and update progress.
//

$progress = json_decode($json,true);

$pstat[ "input"   ] = $progress[ "input"   ];
$pstat[ "percent" ] = $progress[ "percent" ];
$pstat[ "docnum"  ] = $progress[ "docnum"  ];
$pstat[ "clname"  ] = $progress[ "clname"  ];
$pstat[ "acttime" ] = time();

error_log("Progress: " . $pstat[ "docnum" ] . "/" . $pstat[ "clname" ] . " => " . $pstat[ "percent" ]);

//
// Get shared memory locked.
//

$status = smem_getmem();

$instance = $_SERVER[ "HTTP_XDC_INSTANCE" ];

$status[ "encoders" ][ $instance ][ "progress" ] = $pstat;

//
// Write back updated status to shared memory.
//

smem_putmem($status);
?>
