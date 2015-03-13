<?php

function get_tar_content($tarpath,$tarcont = null)
{
	$directory = array();
	
	if (($tarcont !== null) && (strlen($tarcont) == 0)) 
	{
		$tarcont = null;
	}
	
	//
	// Check for MXF essence extraction.
	//
	
	$wantpcm = false;
	
	if (($tarcont !== null) && strtolower(substr($tarcont,-8)) == ".mxf.pcm")
	{
		$tarcont = substr($tarcont,0,-4);
		$wantpcm = true;
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
			
			if ($wantpcm)
			{
				//
				// Parse MXF file and extract essence.
				//
				
				$todo = $size;
			
				while ($todo > 0)
				{
					$oidmgc = fread($tarfd,1);
	
					if (($oidmgc === false) || (strlen($oidmgc) == 0)) break;
	
					if (ord($oidmgc[ 0 ]) != 0x06) break;
	
					$oidlen = fread($tarfd,1);
	
					if (ord($oidlen[ 0 ]) != 0x0e) break;
	
					$oidval = fread($tarfd,14);
	
					$todo -= 16;
					
					$payber = fread($tarfd,1);
	
					$todo -= 1;
					
					if ((ord($payber[ 0 ]) & 0x80) == 0x80)
					{
						$paylen = ord($payber[ 0 ]) & 0x7f;
		
						$contraw = fread($tarfd,$paylen);
						
						$todo -= $paylen;
						
						$contlen = 0;
			
						for ($inx = 0; $inx < $paylen; $inx++)
						{
							$contlen = ($contlen << 8) + ord($contraw[ $inx ]);
						}
					}
					else
					{
						$contlen = ord($payber[ 0 ]) & 0x7f;
					}

					if (bin2hex($oidval) == "2b34010201010d01030116010400")
					{
						//
						// We have now the essence to read.
						//
						
						$elen = $contlen;
						$done = 0;
						
						error_log("tarman: Spooling audio essence size=$elen");

						while ($elen > 0)
						{
							$xfer = $elen;
				
							if ($xfer > (64 * 1024)) $xfer = 64 * 1024;
				
							$content = fread($tarfd,$xfer);
		
							echo $content;
							
							$elen -= strlen($content);
							$done += strlen($content);
							
							//error_log("tarman: Spooling audio essence done=$done rest=$elen");
						}
						
						error_log("tarman: Spooling audio essence done.");
					}
					else
					{
						fseek($tarfd,$contlen,SEEK_CUR);
					}
					
					$todo -= $contlen;
				}
			}
			else
			{
				//
				// Plain reading of contained file.
				//
				
				$todo = $size;
				$done = 0;
				
				error_log("tarman: Spooling mxf-file size=$todo");
			
				while ($todo > 0)
				{
					$xfer = $todo;
				
					if ($xfer > 32 * 1024) $xfer = 32 * 1024;
				
					$content = fread($tarfd,$xfer);
		
					echo $content;
				
					$todo -= strlen($content);
					$done += strlen($content);
					
					//error_log("tarman: Spooling mxf-file done=$done rest=$todo");
				}
				
				error_log("tarman: Spooling mxf-file done.");
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