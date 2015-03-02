<?php

//
// Get shared memory with lock.
//

function smem_getmem()
{
	//
	// Exclusive lock via semaphore.
	//

	$semid = sem_get(223456);
	sem_acquire($semid);

	error_log("Status-shm: islocked.");

	//
	// Read status from shared memory segment.
	//

	$shmid   = shmop_open(123456,"c",0644,64 * 1024);
	$shmsize = shmop_size($shmid);

	$status = json_decdat(shmop_read($shmid,0,$shmsize));

	if ($status === null) $status = array();

	$GLOBALS[ "smem_semid"   ] = $semid;
	$GLOBALS[ "smem_shmid"   ] = $shmid;
	$GLOBALS[ "smem_shmsize" ] = $shmsize;
	
	return $status;
}

//
// Put shared memory with unlock.
//

function smem_putmem($status)
{
	$semid   = $GLOBALS[ "smem_semid"   ];
	$shmid   = $GLOBALS[ "smem_shmid"   ];
	$shmsize = $GLOBALS[ "smem_shmsize" ];
	
	shmop_write($shmid,str_pad(json_encdat($status),$shmsize),0);
	shmop_close($shmid);

	//
	// Release exclusive lock on semaphore.
	//

	sem_release($semid);

	error_log("Status-shm: unlocked.");
}

//
// Create a new guid.
//

function smem_createguid()
{
	return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
		mt_rand(0,65535), 
		mt_rand(0,65535), 
		mt_rand(0,65535), 
		mt_rand(16384,20479), 
		mt_rand(32768,49151), 
		mt_rand(0,65535), 
		mt_rand(0,65535), 
		mt_rand(0,65535)
		);    
}
?>
