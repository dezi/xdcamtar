<?php

$GLOBALS[ "uname"    ] = trim(`uname`);
$GLOBALS[ "hostname" ] = trim(`hostname`);

$GLOBALS[ "server_host" ] = "dezimac.local";
$GLOBALS[ "server_port" ] = 80;

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
// Get encoding job from server.
//

function Getjob()
{
	$host = $GLOBALS[ "server_host" ];
	$port = $GLOBALS[ "server_port" ];
	
	$self = $GLOBALS[ "hostname" ];
	$unam = $GLOBALS[ "uname"    ];
	
	$fp = @fsockopen($host,$port,$errno,$errstr,30);
	
	if (!$fp) 
	{
    	Logdat("Failed server connect $host:$port\n");
    	
    	return;
	}
	
	fwrite($fp,"GET /getjob HTTP/1.1\r\n");
    fwrite($fp,"Host: $host\r\n");
    fwrite($fp,"XDC-Host: $self\r\n");
    fwrite($fp,"XDC-Unam: $unam\r\n");
    fwrite($fp,"Transfer-Encoding: chunked\r\n");
    fwrite($fp,"\r\n");
    fflush($fp);
    
    while (true) 
    {
    	if (feof($fp)) break;
    	
    	$line = fgets($fp,128);
        Logdat($line);
        
        if ($line == "---\n") break;
    }
    
    for ($inx = 0; $inx <= 100; $inx++)
    {
    	$line = "Done $inx%\n";
		$hlen = dechex(strlen($line));
		
    	fwrite($fp,"$hlen\r\n");
    	fwrite($fp,$line);
    	fwrite($fp,"\r\n");
    	fflush($fp);
    	
		Logdat($line);
    	
    	usleep(100000);
    }
    
    fclose($fp);
}

//
// Shutdown signal handler.
//

function Shutdown($signo)
{
	$GLOBALS[ "shutdown" ] = true;
	
	Logdat("Received shutdown signal...\n");
}

//
// Fork number of processes and start read loop.
//

function MainLoop($selfname)
{
	declare(ticks = 1);
	
	$GLOBALS[ "shutdown" ] = false;
	
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
	
	Logdat("Starting.\n");
	
	while (! $GLOBALS[ "shutdown" ])
	{			
		Logdat("Alive...\n");
		
		Logflush();
		
		Getjob();
		
		sleep(3);
	}
	
	Logdat("Exitting.\n");
	
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
