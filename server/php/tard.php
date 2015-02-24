<?php

function get_tar_content($tarpath,$tarcont = null)
{
	$directory = array();
	
	if (($tarcont !== null) && (strlen($tarcont) == 0)) 
	{
		$tarcont = null;
	}
	
	$tarfd = fopen($tarpath,"r");
	
	$curpos = 0;
	
	while (($header = fread($tarfd,512)) != null)
	{
		if (strlen($header) != 512) break;
		
		$file = trim(substr($header,0,100));
		
		if (! strlen($file)) break;

		//
		// Figure out entry size.
		//
		
		$size = substr($header,124,12);
		
		if (ord($size[ 0 ]) >= 128)
		{
			//
			// Base 256 (binary) coded.
			//
			
			$bigsize = 0;
			
			for ($inx = 1; $inx <= 11; $inx++)
			{
				$bigsize = ($bigsize << 8) + ord($size[ $inx ]);
			}
			
			$size = $bigsize;
		}
		else
		{
			//
			// Base 8 (octal) coded.
			//
			
			$size = octdec($size);
		}
		
		//
		// Register directory entry.
		//
		
		$entry = array();
		
		$entry[ "name" ] = $file;
		$entry[ "size" ] = $size;
		$entry[ "offs" ] = $curpos;
		
		$directory[] = $entry;
		
		//
		// Handle file entry.
		//
		
		if (($tarcont == null) || ($tarcont !== $file))
		{
			//
			// Skip entry size.
			//
			
			fseek($tarfd,$size,SEEK_CUR);
		}
		else
		{
			//
			// Pass through file entry.
			//
			
			error_log("tarman: $file => $size");
			
			$todo = $size;
			
			while ($todo > 0)
			{
				$xfer = $todo;
				
				if ($xfer > 32 * 1024) $xfer = 32 * 1024;
				
				$content = fread($tarfd,$xfer);
		
				echo $content;
				
				$todo -= strlen($content);
			}
			
			error_log("tarman: $file => done");

			break;
		}
		
		//
		// Figure out padding and skip this.
		//
		
		$padd = ($size % 512);
		
		if ($padd > 0) 
		{
			$padd = 512 - $padd;
			
			fseek($tarfd,$padd,SEEK_CUR);
		}
		
		$curpos += 512 + $size + $padd;
	}
	
	fclose($tarfd);
	
	return $directory;
}

?>