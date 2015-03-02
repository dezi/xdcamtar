<?php

//
// Version definitions.
//

$GLOBALS[ "encoder"  ] = "1.0.0.1001";
$GLOBALS[ "vserver"  ] = "1.0.0.1000";

//
// Local includes.
//

include("./json.php");
include("./smem.php");
include("./tard.php");

//
// Process a clients get job request.
//

function ProcessRequest()
{
	//
	// Start clean output buffer.
	//

	ob_end_clean();
	ob_start();

	//
	// Figure out what to do.
	//

	$job = GetJob();

	//
	// Transmitt job to encoder.
	//
	
	echo json_encdat($job);

	echo "\n---\n";

	//
	// Finalize output and flush.
	//

	$size = ob_get_length();

	header("Content-type: text/plain; charset=utf-8");
	header("Content-Length: $size");
	header("Connection: close");

	ob_end_flush();
	flush();

	//
	// Register job.
	//
	
	$status = smem_getmem();

	$job[ "uname"     ] = $_SERVER[ "HTTP_XDC_UNAME"    ];
	$job[ "encoder"   ] = $_SERVER[ "HTTP_XDC_ENCODER"  ];
	$job[ "hostname"  ] = $_SERVER[ "HTTP_XDC_HOSTNAME" ];
	$job[ "instance"  ] = $_SERVER[ "HTTP_XDC_INSTANCE" ];
	$job[ "remoteip"  ] = $_SERVER[ "REMOTE_ADDR" 	   ];
	$job[ "timestamp" ] = time();
 
	$status[ "encoders" ][ $job[ "instance" ] ] = $job;
	
	smem_putmem($status);

	//
	// Listen for progress and result.
	//

	$pd = fopen("php://input","r");
	
	error_log("Opened php://input => $pd");
	
	$ld = isset($GLOBALS[ "logfile" ]) ? $GLOBALS[ "logfile" ] : false;

	while (($line = fgets($pd,128)) !== false)
	{
		if ($ld === false) 
		{
			error_log(trim($line));
		}
		else
		{
			fputs($ld,$line);
		}
	}

	if ($ld !== false) 
	{
		flock($ld,LOCK_UN);
		
		fclose($ld);
	}
	
	fclose($pd);

	error_log("Done $pd");
}

//
// Get job.
//

function GetJob()
{
	error_log($_SERVER[ "HTTP_XDC_ENCODER"  ] . "/" . $_SERVER[ "HTTP_XDC_INSTANCE" ]);
	
	if (($job = JobUpdateSoftware()) !== null) return $job;
	
	if (($job = JobXDCAMEncode())    !== null) return $job;
	
	return JobIdle();
}

function GetDirectoryListing($dir,$suffix = null)
{
	$dfd = opendir($dir);
	
	if ($dfd === false) return null;
	
	$list = array();
	
	while (($file = readdir($dfd)) !== false)
	{
		if ($file == ".") continue;
		if ($file == "..") continue;
		if ($file == ".DS_Store") continue;
		if ($file == "._.DS_Store") continue;
		
		if (($suffix !== null) && substr($file,-strlen($suffix)) != $suffix)
		{
			continue;
		}
		
		array_push($list,"$dir/$file");
	}
	
	closedir($dfd);
	
	return $list;
}

//
// Job: encode video.
//

function JobXDCAMEncode()
{
	$job = null;

	//
	// Get all tarballs in uploaded directory.
	//
	
	$tardir   = "../tmp/xdcam/tarballs";
	$tarballs = GetDirectoryListing($tardir,".tar");
	
	if (($tarballs === null) || ! count($tarballs)) return null;
	
	$candidates = array();
	
	foreach ($tarballs as $tarball)
	{
		error_log("TARBALL: $tarball");

		$tarcont = get_tar_content($tarball);
		
		foreach ($tarcont as $tarfile)
		{
			$name = $tarfile[ "name" ];
			$size = $tarfile[ "size" ];
			
			if (substr($name,-7) != "V01.MXF") continue;
			
			if ($size < 200000000) 
			{
				//
				// Technical filler clip is skipped.
				//
				
				continue;
			}
			
			$xmloutpath = "../out/xdcam/previews/" . substr($name,0,-4) . ".xml";
			
			if (file_exists($xmloutpath)) continue;
			
			$candidates[ $name ] = $size;
		}
	}
	
	asort($candidates);
	
	foreach ($candidates as $name => $size)
	{
		$path = pathinfo($name,PATHINFO_DIRNAME);

		$docnum = explode("/",$name);
		$clname = array_pop($docnum);
		$docnum = $docnum[ 0 ];

		error_log("candidate: $docnum => $name => $size");
		
		//
		// Try to open and lock logfile.
		//
		
		$logfile = "../out/xdcam/previews/" . substr($name,0,-4) . ".log";
		$logdir  = pathinfo($logfile,PATHINFO_DIRNAME);
	
		if (! file_exists($logdir)) 
		{ 
			umask(0); 
			mkdir($logdir,0777,true);
		}
		
		$logfd = fopen($logfile,"w");
		
		if (! flock($logfd,LOCK_EX | LOCK_NB))
		{
			//
			// Some other process is busy on this.
			//
			
			error_log("Cannot get lock on $logfile, skipping...");
	
			fclose($logfd);
			
			continue;
		}
		
		//
		// We hold a lock on the logfile now.
		//
		
		$GLOBALS[ "logfile" ] = $logfd;
		
		error_log("Opened and locked $logfile => " . $GLOBALS[ "logfile" ]);

		$job[ "jobname" ] = "encode";
		
		$job[ "encode" ][ "docnum" ] = "$docnum";
		$job[ "encode" ][ "clname" ] = "$clname";
		$job[ "encode" ][ "input"  ] = "/tarman/xdcam/tarballs/$docnum.tar/$name";
		$job[ "encode" ][ "output" ] = "/output/xdcam/previews/$path";

		$job[ "encode" ][ "options" ][ "--config"      ] = "config.dezi-osx.json";
		$job[ "encode" ][ "options" ][ "--profile"     ] = "profile.XDCAM-Preview.json";
		$job[ "encode" ][ "options" ][ "--logprocess"  ] = "true";
		$job[ "encode" ][ "options" ][ "--logprogres"  ] = "true";
		$job[ "encode" ][ "options" ][ "--usehttpd"    ] = "true";

		$job[ "encode" ][ "options" ][ "--inputvideo"  ] = $job[ "encode" ][ "input"  ];
		$job[ "encode" ][ "options" ][ "--outputdir"   ] = $job[ "encode" ][ "output" ];
		
		break;
	}

	return $job;
}

//
// Job: update encoder software.
//

function JobUpdateSoftware()
{
	if ($_SERVER[ "HTTP_XDC_ENCODER" ] == $GLOBALS[ "encoder" ])
	{
		return null;
	}

	$encoderdir   = "../../encoder";
	
	$encodersub[] = "rcd";
	$encodersub[] = "php";
	$encodersub[] = "config";
	$encodersub[] = "config/bin";
	
	foreach ($encodersub as $subdir)
	{
		$dfd = opendir("$encoderdir/$subdir");
	
		if (! $dfd) continue;
		
		while (($file = readdir($dfd)) !== false)
		{
			if ($file == ".") continue;
			if ($file == "..") continue;
			if ($file == ".DS_Store") continue;
			if ($file == "._.DS_Store") continue;
			
			if (is_dir("$encoderdir/$subdir/$file")) continue;
			
			$job[ "jobname" ] = "update";
			$job[ "update"  ][ "files" ][] = "$subdir/$file";
		}
		
		closedir($dfd);
	}
	
	return $job;
}

//
// Job: do nothing.
//

function JobIdle()
{
	$job[ "jobname" ] = "idle";
	$job[ "idle"    ] = array();
	
	return $job;
}

//
// Main request processing.
//
 
ProcessRequest();

?>
