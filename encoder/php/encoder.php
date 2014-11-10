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
