<?php

$GLOBALS[ "encoder"  ] = "1.0.0.1001";

$GLOBALS[ "servers"  ][] = "PC15930.spiegel.de:8880";
$GLOBALS[ "servers"  ][] = "dezimac.local:80";

$GLOBALS[ "uname"    ] = trim(`uname`);
$GLOBALS[ "hostname" ] = trim(`hostname`);

function Logflush()
{
	if (isset($GLOBALS[ "logfd" ])) fflush($GLOBALS[ "logfd" ]);
}

function Logdat($message)
{
	$logfile = "../log/encoder.log";
	
	if (! isset($GLOBALS[ "logfd" ]))
	{
		if (file_exists($logfile))
		{
			$GLOBALS[ "logdt" ] = date("Ymd",filemtime($logfile));
		}
		else
		{
			$GLOBALS[ "logdt" ] = date("Ymd");
		}
		
		$GLOBALS[ "logfd" ] = fopen($logfile,"a");

		if (! $GLOBALS[ "logfd" ])
		{
			echo "Cannot open logfile...\n";
			exit();
		}

		chmod($logfile,0666);
	}
	
	if ($GLOBALS[ "logdt" ] != date("Ymd"))
	{
		//
		// Log file expired, re-open.
		//
		
		fclose($GLOBALS[ "logfd" ]);
		
		rename($logfile,substr($logfile,0,-4) . "." . $GLOBALS[ "logdt" ] . ".log");
		
		$GLOBALS[ "logfd" ] = fopen($logfile,"a");
		$GLOBALS[ "logdt" ] = date("Ymd",filemtime($logfile));
		
		chmod($logfile,0666);
	}
	
	fputs($GLOBALS[ "logfd" ],$message);
}

//
// Write chunked line to server with log.
//

function WriteChunkedLine($fp,$line)
{
	$hlen = dechex(strlen($line));

    fwrite($fp,"$hlen\r\n");
    fwrite($fp,$line);
    fwrite($fp,"\r\n");
    fflush($fp);
    	
	Logdat($line);
}

//
// Do idle job.
//

function JobIdle($fp,$job)
{
    WriteChunkedLine($fp,"Nothing to do.\n");
    	
    usleep(1000000);

    return true;
}

//
// Do idle job.
//

function JobUpdate($fp,$job)
{
	$success = true;
	
    WriteChunkedLine($fp,"Updating files and config...\n");
    
    foreach ($job[ "update" ][ "files" ] as $file)
    {
		WriteChunkedLine($fp,"Requesting $file.\n");
		
		$host = $GLOBALS[ "acthost" ];
		$port = $GLOBALS[ "actport" ];
		
		$updurl = "http://$host:$port/update/$file";
		$upcont = file_get_contents($updurl);
		$upsize = strlen($upcont);
		
		WriteChunkedLine($fp,"Downloaded $updurl => $upsize.\n");
		
		$localfile = "../$file";
		
		//if (strstr($localfile,"encoder.php") !== false) continue;
		
		if (file_put_contents($localfile,$upcont) === false)
		{
			$success = false;
		}
 		
 		WriteChunkedLine($fp,"Wrote local $localfile => $upsize.\n");
	}
    
    if ($success)
    {
    	WriteChunkedLine($fp,"Updating files and config done.\n");
    	
    	$GLOBALS[ "restart" ] = true;
    }
    else
    {
    	WriteChunkedLine($fp,"Updating files and config failed.\n");
    }
    
    return true;
}

//
// Get encoding job from server.
//

function Getjob()
{
	$self    = $GLOBALS[ "hostname" ];
	$uname   = $GLOBALS[ "uname"    ];
	$encoder = $GLOBALS[ "encoder"  ];
	
	foreach ($GLOBALS[ "servers" ] as $host)
	{ 	
		$host = explode(":",$host);
		$port = $host[ 1 ];
		$host = $host[ 0 ];
		
		$fp = @fsockopen($host,$port,$errno,$errstr,2);
	
		if ($fp) 
		{
			$GLOBALS[ "acthost" ] = $host;
			$GLOBALS[ "actport" ] = $port;
			
			break;
		}
	}
	
	if (! $fp) 
	{
		Logdat("No server available.\n");
		
		return;
	}
	
	Logdat("Connected to $host:$port.\n");
		
	fwrite($fp,"GET /getjob HTTP/1.1\r\n");
    fwrite($fp,"Host: $host\r\n");
    fwrite($fp,"XDC-Host: $self\r\n");
    fwrite($fp,"XDC-Uname: $uname\r\n");
    fwrite($fp,"XDC-Encoder: $encoder\r\n");
    fwrite($fp,"Transfer-Encoding: chunked\r\n");
    fwrite($fp,"\r\n");
    fflush($fp);
    
    stream_set_timeout($fp,5);

    $json = "";
    $head = true;
    
    while (true) 
    {
    	if (feof($fp)) break;
    	
    	$line = fgets($fp,512);
        Logdat($line);
        
        if ($head) 
        {
        	$head = ($line != "\r\n");
    		
    		continue;
    	}
    	
        if ($line == "---\n") break;
        
        $json .= $line;
    }

    stream_set_timeout($fp,60);
    
    $job = json_decode($json,true);
    
    WriteChunkedLine($fp,"Start job processing...\n");
	
	if (isset($job[ "idle"   ])) JobIdle  ($fp,$job);
	if (isset($job[ "update" ])) JobUpdate($fp,$job);
   
    WriteChunkedLine($fp,"Done job processing.\n");
    
    //
    // Final dummy chunk and close.
    //
    
    WriteChunkedLine($fp,"");
    
    fclose($fp);
}

//
// Shutdown signal handler.
//

function Shutdown($signo)
{
	$GLOBALS[ "shutdown" ] = true;
	
	Logdat("Received shutdown signal...\n");
	
	Logflush();
}

//
// Fork number of processes and start read loop.
//

function MainLoop($selfname)
{
	declare(ticks = 1);
	
	$GLOBALS[ "shutdown" ] = false;
	$GLOBALS[ "restart"  ] = false;
	
    if (function_exists("pcntl_signal"))
    {
        pcntl_signal(SIGHUP, "Shutdown");
        pcntl_signal(SIGINT ,"Shutdown");
        pcntl_signal(SIGUSR1,"Shutdown");
        pcntl_signal(SIGTERM,"Shutdown");
    }
    else
    {
    	echo "No pcntl_signal, exitting...\n";
    	
    	exit(0);
    }
	
	if (! is_dir("../run")) mkdir("../run",0755);
	if (! is_dir("../log")) mkdir("../log",0755);
	
	file_put_contents("../run/$selfname.pid",getmypid());
	
	Logdat("Starting version " . $GLOBALS[ "encoder"  ] . ".\n");
	
	while ((! $GLOBALS[ "shutdown" ]) && (! $GLOBALS[ "restart" ]))
	{			
		Logdat("Alive...\n");
		
		Logflush();
				
		sleep(3);

		Getjob();
	}
	
	if ($GLOBALS[ "restart" ] && ! $GLOBALS[ "shutdown" ])
	{
		Logdat("Restarting.\n");
		Logdat("===========\n");
		
		pcntl_exec($_SERVER['_'],$_SERVER[ "argv" ]);
	}
	else
	{
		Logdat("Exitting.\n");
		Logdat("=========\n");
	}
	
	exit(0);
}

function Main()
{
	date_default_timezone_set("UTC");
	
	$selfname = $_SERVER[ "argv" ][ 0 ];
	
	MainLoop($selfname);
}

Main();
?>
