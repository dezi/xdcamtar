<?php

function json_encrec($data,$level = 0) 
{
	$pad = str_pad("\n",$level * 2 + 1,' ');
	
	switch ($type = gettype($data)) 
	{
		case 'NULL':
			return 'null';
			
		case 'boolean':
			return ($data ? 'true' : 'false');
			
		case 'integer':
		case 'double':
		case 'float':
			return $data;
			
		case 'string':
			$str = addslashes($data);
			$str = str_replace("\r","\\r",$str);
			$str = str_replace("\n","\\n",$str);
			$str = str_replace("\t","\\t",$str);
			$str = str_replace("\\'","'" ,$str);
			return '"' . $str . '"';
			
		case 'object':
			$data = get_object_vars($data);
			
		case 'array':
			$output_isarray = true;
			
			foreach ($data as $key => $value) 
			{
				if (gettype($key) == 'integer') continue;
				
				$output_isarray = false;
				break;
			}
			
			if ($output_isarray) 
			{
				$output_intkeys = array();

				foreach ($data as $key => $value) 
				{
					$output_intkeys[] = json_encrec($value,$level + 1);
				}
				
				return "$pad" . "[" . "$pad  " . implode(",$pad  ",$output_intkeys) . "$pad]";
			}
			else
			{
				$output_txtkeys = array();
				
				foreach ($data as $key => $value) 
				{
					$output_txtkeys[] 
						= json_encrec($key,$level + 1) 
						. ':' 
						. json_encrec($value,$level + 1)
						;
				}
			
				return "$pad" . "{" . "$pad  " . implode(",$pad  ",$output_txtkeys) . "$pad}";
			}
			
			return '';
				
		default:
			return '';
	}
}

function json_encdat($data,$level = 0)
{
	return trim(json_encrec($data,$level));
} 

function json_decdat($data)
{	
	$data = str_replace("\\'","'",$data);
	$data = str_replace("\t" ," ",$data);

	$result = json_decode($data,true);
	
	return $result;
}

?>
