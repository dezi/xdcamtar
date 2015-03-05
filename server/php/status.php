<?php

include("./json.php");
include("./smem.php");
include("./tard.php");

function xdcam_uploads_get_status_complete(&$status,$index)
{
	$mystatus = &$status[ "uploads" ];
	
	//
	// Check if already done.
	//
	
	if (isset($mystatus[ $index ][ "files" ]))
	{
		return true;
	}
	
	$dir  = "../tmp/xdcam/uploads";
	$entry = $mystatus[ $index ][ "entry" ];
	
	//
	// Check for index XML.
	//
	
	$indexxmlpath = "$dir/$entry/XDCAM/PROAV/INDEX.XML";
	
	if (! file_exists($indexxmlpath)) return false;
	
	$indexxml = file_get_contents($indexxmlpath);
	
	$indexobj = json_decdat(json_encdat(simplexml_load_string($indexxml)));

	//
	// Get clip content path.
	//
	
	$clippath = "$dir/$entry/XDCAM" . $indexobj[ "clipTable" ][ "@attributes" ][ "path" ];

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

	$mystatus[ $index ][ "files" ] = $clipfiles;

	return true;
}

function xdcam_previews_get_status(&$status)
{
	$oldpreviews = isset($status[ "previews" ]) ? $status[ "previews" ] : array();
	$newpreviews = array();
	
	//
	// Get a list of files just beeing encoded.
	//
	
	$encoding = array();
	
	if (isset($status[ "encoders" ]))
	{
		foreach ($status[ "encoders" ] as $instance => $encoder)
		{
			if (! isset($encoder[ "progress" ])) continue;
			if (! isset($encoder[ "progress" ][ "input"   ])) continue;
			if (! isset($encoder[ "progress" ][ "percent" ])) continue;
			
			$input   = $encoder[ "progress" ][ "input"   ];
			$percent = $encoder[ "progress" ][ "percent" ];
			
			$input = explode(".tar/",$input);
			if (count($input) != 2) continue;
			$input = $input[ 1 ];
			
			$encoding[ $input ] = $percent;
		}
	}
	
	//
	// Process tarballs directory.
	//
	
	$prv = "../tmp/xdcam/previews";
	
	$dir = "../tmp/xdcam/tarballs";
	$dfd = opendir($dir);
	
	while (($entry = readdir($dfd)) !== false)
	{	
		if (substr($entry,-4) != ".tar") continue;
		
		if (isset($oldpreviews[ $entry ]))
		{
			$newpreviews[ $entry ] = $oldpreviews[ $entry ];
			
			foreach ($newpreviews[ $entry ] as $xmloutpath => $exists)
			{
				$name = explode("previews/",$xmloutpath);
				$name = substr($name[ 1 ],0,-4) . ".MXF";
				
				$newpreviews[ $entry ][ $xmloutpath ] = file_exists($xmloutpath) ? "done" : "want";
				
				if (isset($encoding[ $name ])) $newpreviews[ $entry ][ $xmloutpath ] = $encoding[ $name ];
			}
		}
		else
		{
			$newpreviews[ $entry ] = array();
			
			$tarball = "$dir/$entry";
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
			
				$xmloutpath = dirname(getcwd()) . "/tmp/xdcam/previews/" . substr($name,0,-4) . ".xml";
				
				$newpreviews[ $entry ][ $xmloutpath ] = file_exists($xmloutpath) ? "done" : "want";
				
				if (isset($encoding[ $name ])) $newpreviews[ $entry ][ $xmloutpath ] = $encoding[ $name ];
			}
		}

	}
	
	closedir($dfd);
		
	$status[ "previews" ] = &$newpreviews;
}

function xdcam_uploads_get_status(&$status)
{
	if (! isset($status[ "uploads" ])) $status[ "uploads" ] = array();
	
	$mystatus = &$status[ "uploads" ];

	//
	// Process uploads directory.
	//
	
	$dir = "../tmp/xdcam/uploads";
	$dfd = opendir($dir);
	
	while (($entry = readdir($dfd)) !== false)
	{	
		if (substr($entry,0,1) == ".") continue;
		
		if (! is_dir("$dir/$entry")) continue;
		
		$index = -1;
		
		for ($index = 0; $index < count($mystatus); $index++)
		{
			if ($mystatus[ $index ][ "entry" ] == $entry)
			{
				break;
			}
		}
		
		//
		// For debugging check if $entry is a link.
		//
		
		if (readlink("$dir/$entry"))
		{
			$ducmd = "du -sk $dir/" . readlink("$dir/$entry");
		}
		else
		{
			$ducmd = "du -sk $dir/$entry";
		}
		
		$dures = exec($ducmd);

		if ($index == count($mystatus))
		{
			$mystatus[ $index ] = array(); 
			$mystatus[ $index ][ "entry"  ] = $entry;
			$mystatus[ $index ][ "status" ] = "evaluating...";		
			$mystatus[ $index ][ "kbsize" ] = intval($dures);
			$mystatus[ $index ][ "kbtime" ] = time();
		}
		else
		{
			if ($mystatus[ $index ][ "kbsize" ] == intval($dures))
			{
				if ((time() - $mystatus[ $index ][ "kbtime" ]) > 10)
				{
					$complete = xdcam_uploads_get_status_complete($status,$index);
					
					if ($complete)
					{
						$mystatus[ $index ][ "status" ] = "uploaded";

						$tardir  = "../tmp/xdcam/tarballs";
						$tarball = "$tardir/$entry.tar";
						
						if (file_exists($tarball))
						{
							$mystatus[ $index ][ "status" ] = "tared";
						}

						if (file_exists($tarball . ".tmp"))
						{
							$mystatus[ $index ][ "status"  ] = "taring...";
							$mystatus[ $index ][ "tarsize" ] = filesize($tarball . ".tmp") / 1024;
						}
						
						if (file_exists($tarball . ".bad"))
						{
							$mystatus[ $index ][ "status" ] = "failed";
						}
					}
					else
					{
						$mystatus[ $index ][ "status" ] = "incomplete";
					}
				}
				else
				{
					$mystatus[ $index ][ "status" ] = "evaluating...";
				}
			}
			else
			{
				$mystatus[ $index ][ "status" ] = "uploading...";
				$mystatus[ $index ][ "kbsize" ] = intval($dures);
				$mystatus[ $index ][ "kbtime" ] = time();
			}
			
			$mystatus[ $index ][ "path"   ] = realpath(getcwd() . "/$dir/$entry");		
		}
	}
	
	closedir($dfd);
}

function xdcam_tarballs_get_status(&$status)
{
	if (! isset($status[ "uploads" ])) $status[ "uploads" ] = array();
	
	$mystatus = &$status[ "uploads" ];
	
	//
	// Process tarballs directory.
	//
	
	$dir = "../tmp/xdcam/tarballs";
	$dfd = opendir($dir);
	
	while (($entry = readdir($dfd)) !== false)
	{	
		if (substr($entry,0,1) == ".") continue;		
		
		if (substr($entry,-4) != ".tar") continue;

		$entryname = substr($entry,0,-4);
		
		$index = -1;
		
		for ($index = 0; $index < count($mystatus); $index++)
		{
			if ($mystatus[ $index ][ "entry" ] == $entryname)
			{
				break;
			}
		}
		
		$ducmd = "du -sk $dir/$entry";
		$dures = exec($ducmd);
		
		if ($index == count($mystatus))
		{
			$mystatus[ $index ] = array(); 
			$mystatus[ $index ][ "entry"  ] = $entryname;
			$mystatus[ $index ][ "kbsize" ] = intval($dures);
			$mystatus[ $index ][ "kbtime" ] = time();
		}
		
		$mystatus[ $index ][ "status" ] = "tared";		
		$mystatus[ $index ][ "path"   ] = realpath(getcwd() . "/$dir/$entry");		
	}
	
	closedir($dfd);
}

function xdcam_uploads_make_tarball($status)
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

		$entry = $status[ "uploads" ][ $index ][ "entry" ];
		
		//
		// Check for tar already present.
		//
		
		$tardir  = "../tmp/xdcam/tarballs";
		$tarball = "$tardir/$entry.tar";
		
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
		$srctree = "$srcdir/$entry";

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
		
		if (! file_exists($tarbin)) $tarbin = "/usr/bin/gnutar";
		if (! file_exists($tarbin)) $tarbin = "tar";

		//
		// Exclude hidden files and derefence symlinks for debug.
		//
		
		$taropt = "--exclude=.* --dereference";
	
		$tartar = "../tarballs/$entry.tar";
		
		$tarcmd = "cd $srcdir;"
			    . "$tarbin cvf $tartar.tmp $taropt $entry > $tartar.log"
			    . " || mv $tartar.tmp $tartar.bad;"
			    . "mv $tartar.tmp $tartar";
			    
		$tarres = exec($tarcmd);
		
		//
		// If success delete original copy.
		//
		
		if (file_exists("$srcdir/$tartar"))
		{
			$delcmd = "cd $srcdir;rm -rf $entry";
			$delres = exec($delcmd);
		}
		
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
// Get shared memory locked.
//

$status = smem_getmem();

//
// Update all statuses.
//

xdcam_uploads_get_status ($status);
xdcam_tarballs_get_status($status);
xdcam_previews_get_status($status);

//
// Write back updated status to shared memory.
//

smem_putmem($status);

//
// Prepare response for browser.
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

xdcam_uploads_make_tarball($status);
?>
