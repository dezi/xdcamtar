<?php

include("./json.php");

function get_upload_status_complete(&$status,$index)
{
	//
	// Check if already done.
	//
	
	if (isset($status[ "uploads" ][ $index ][ "files" ]))
	{
		return true;
	}
	
	$dir  = "../tmp/xdcam/uploads";
	$file = $status[ "uploads" ][ $index ][ "dir" ];
	
	//
	// Check for index XML.
	//
	
	$indexxmlpath = "$dir/$file/XDCAM/PROAV/INDEX.XML";
	
	if (! file_exists($indexxmlpath)) return false;
	
	$indexxml = file_get_contents($indexxmlpath);
	
	$indexobj = json_decdat(json_encdat(simplexml_load_string($indexxml)));

	//
	// Get clip content path.
	//
	
	$clippath = "$dir/$file/XDCAM" . $indexobj[ "clipTable" ][ "@attributes" ][ "path" ];

	$clipfiles = array();
	
	foreach ($indexobj[ "clipTable" ][ "clip" ] as $clip)
	{
		$subclippath = $clippath . $clip[ "@attributes" ][ "clipId" ];
		
		//
		// Check for ../C0001/C0001C01.SMI
		//
		
		$subclipfile = $subclippath . "/" . $clip[ "@attributes" ][ "file" ];
		
		if (! file_exists($subclipfile)) return false;
		
		$clipfiles[] = $subclipfile;
		
		//
		// Check for video file.
		//
		
		$subclipfile = $subclippath . "/" . $clip[ "video" ][ "@attributes" ][ "file" ];

		if (! file_exists($subclipfile)) return false;
	
		$clipfiles[] = $subclipfile;

		//
		// Check for audio files.
		//
		
		foreach ($clip[ "audio" ] as $audio)
		{
			$subclipfile = $subclippath . "/" . $audio[ "@attributes" ][ "file" ];

			if (! file_exists($subclipfile)) return false;
		
			$clipfiles[] = $subclipfile;
		}
	}
			
	$status[ "uploads" ][ $index ][ "files" ] = $clipfiles;
	
	return true;
}

function get_upload_status(&$status)
{
	if (! isset($status[ "uploads" ])) $status[ "uploads" ] = array();
	
	//
	// Process uploads directory.
	//
	
	$dir = "../tmp/xdcam/uploads";
	$dfd = opendir($dir);
	
	while (($file = readdir($dfd)) !== false)
	{	
		if (substr($file,0,1) == ".") continue;
		if (! is_dir("$dir/$file")) continue;
		
		$index = -1;
		
		for ($index = 0; $index < count($status[ "uploads" ]); $index++)
		{
			if ($status[ "uploads" ][ $index ][ "dir" ] == $file)
			{
				break;
			}
		}
		
		//
		// For debugging check if $file is a link.
		//
		
		if (readlink("$dir/$file"))
		{
			$ducmd = "du -sk $dir/" . readlink("$dir/$file");
		}
		else
		{
			$ducmd = "du -sk $dir/$file";
		}
		
		$dures = exec($ducmd);

		if ($index == count($status[ "uploads" ]))
		{
			$status[ "uploads" ][ $index ] = array(); 
			$status[ "uploads" ][ $index ][ "dir"    ] = $file;
			$status[ "uploads" ][ $index ][ "status" ] = "evaluating...";		
			$status[ "uploads" ][ $index ][ "kbsize" ] = intval($dures);
			$status[ "uploads" ][ $index ][ "kbtime" ] = time();
		}
		else
		{
			if ($status[ "uploads" ][ $index ][ "kbsize" ] == intval($dures))
			{
				if ((time() - $status[ "uploads" ][ $index ][ "kbtime" ]) > 10)
				{
					$complete = get_upload_status_complete($status,$index);
					
					if ($complete)
					{
						$status[ "uploads" ][ $index ][ "status" ] = "uploaded";

						$tardir  = "../tmp/xdcam/tarballs";
						$tarball = "$tardir/$file.tar";
						
						if (file_exists($tarball))
						{
							$status[ "uploads" ][ $index ][ "status" ] = "tared";
						}

						if (file_exists($tarball . ".tmp"))
						{
							$status[ "uploads" ][ $index ][ "status" ] = "taring...";
						}
						
						if (file_exists($tarball . ".bad"))
						{
							$status[ "uploads" ][ $index ][ "status" ] = "failed";
						}
					}
					else
					{
						$status[ "uploads" ][ $index ][ "status" ] = "incomplete";
					}
				}
				else
				{
					$status[ "uploads" ][ $index ][ "status" ] = "evaluating...";
				}
			}
			else
			{
				$status[ "uploads" ][ $index ][ "status" ] = "uploading...";
				$status[ "uploads" ][ $index ][ "kbsize" ] = intval($dures);
				$status[ "uploads" ][ $index ][ "kbtime" ] = time();
			}		
		}
	}
	
	closedir($dfd);

	//
	// Process tarballs directory.
	//
	
	$dir = "../tmp/xdcam/tarballs";
	$dfd = opendir($dir);
	
	while (($file = readdir($dfd)) !== false)
	{	
		if ($file == ".") continue;
		if ($file == "..") continue;
		if ($file == ".DS_Store") continue;
		
		if (substr($file,-4) != ".tar") continue;

		$entry = substr($file,0,-4);
		
		$index = -1;
		
		for ($index = 0; $index < count($status[ "uploads" ]); $index++)
		{
			if ($status[ "uploads" ][ $index ][ "dir" ] == $entry)
			{
				break;
			}
		}
		
		$ducmd = "du -sk $dir/$file";
		$dures = exec($ducmd);
		
		if ($index == count($status[ "uploads" ]))
		{
			$status[ "uploads" ][ $index ] = array(); 
			$status[ "uploads" ][ $index ][ "dir"    ] = $entry;
			$status[ "uploads" ][ $index ][ "kbsize" ] = intval($dures);
			$status[ "uploads" ][ $index ][ "kbtime" ] = time();
		}
		
		$status[ "uploads" ][ $index ][ "status" ] = "tared";		
	}
	
	closedir($dfd);
}

function get_upload_tarball($status)
{
	//
	// We might wait a long time for tar processes.
	//
	
	set_time_limit(0);

	//
	// Check disk space.
	//
	
	$bytes = disk_total_space("../tmp/xdcam/tarballs");
	
	if ($bytes <= (100.0 * 1000.0 * 1000.0 * 1000.0))
	{
		//
		// Less than 100G free.
		//
		
		return;
	}
	
	//
	// Scan status for tars to be done.
	//
	
	for ($index = 0; $index < count($status[ "uploads" ]); $index++)
	{
		if ($status[ "uploads" ][ $index ][ "status" ] != "uploaded") continue;

		$file = $status[ "uploads" ][ $index ][ "dir" ];
		
		//
		// Check for tar already present.
		//
		
		$tardir  = "../tmp/xdcam/tarballs";
		$tarball = "$tardir/$file.tar";
		
		if (file_exists($tarball)) continue;
		
		//
		// Check for tar already in progress or failed.
		//
		
		if (file_exists($tarball . ".tmp")) continue;
		if (file_exists($tarball . ".bad")) continue;
		
		//
		// Try to lock uploads directory exclusivly.
		//
		
		$srcdir  = "../tmp/xdcam/uploads";
		$srctree = "$srcdir/$file";

		$lockfd = fopen($srcdir,"r");
		
		if (! flock($lockfd,LOCK_EX + LOCK_NB)) 
		{
			//
			// A tar is already in progress.
			//

			fclose($lockfd);
			
			continue;
		}
		
		//
		// Do tarball processing. Prefer GNU tar on OSX.
		//
		
		$tarbin = "/usr/local/bin/tar";
		if (! file_exists($tarbin)) $tarbin = "tar";

		//
		// Exclude hidden files and derefence symlinks for debug.
		//
		
		$taropt = "--exclude=.* --dereference";
	
		$tartar = "../tarballs/$file.tar";
		
		$tarcmd = "cd $srcdir;"
			    . "$tarbin cvf $tartar.tmp $taropt $file > $tartar.log"
			    . " || mv $tartar.tmp $tartar.bad;"
			    . "mv $tartar.tmp $tartar";
			    
		$tarres = exec($tarcmd);
		
		//
		// If success delete original copy.
		//
		
		$delcmd = "cd $srcdir;rm -rf $file";
		$delres = exec($delcmd);
		
		flock($lockfd,LOCK_UN);
		fclose($lockfd);
	}
}

//
// Start clean output buffer.
//

ob_end_clean();
ob_start();

//
// Read status from shared memory segment.
//

$shmid   = shmop_open(123456,"c",0644,64 * 1024);
$shmsize = shmop_size($shmid);

$status = json_decdat(shmop_read($shmid,0,$shmsize));

if ($status === null) $status = array();

//
// Update all statuses.
//

get_upload_status($status);

//
// Write back updated status to shared memory.
//

shmop_write($shmid,str_pad(json_encdat($status),$shmsize),0);
shmop_close($shmid);

//
// Prepare response for client.
//

echo "Kappa.StatusEvent(\n";
echo json_encdat($status) . "\n";
echo ");\n";

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
// Do detached processing if required.
//

get_upload_tarball($status);
?>
