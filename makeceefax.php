<?php
/*
	makeceefax.php
	Generates a Ceefax service from various 'modules' which make specific magazines
	Nathan Dane, 2019
*/
$time_start=microtime(true);
function shutdown()
{
	unlink("active.mcx");
}
register_shutdown_function('shutdown');
// Settings. See the Wiki for details @todo
define ("VERSION","V1.2");
define ("PAGEDIR","/home/pi/ceefax");	// Where do you want your teletext files?
define ("PREFIX","AUTO");	// What do you want the filename prefix to be?
define ("REGION","Northern Ireland");	// What UK TV Region are you in? 
define ("ROWADAPT",false);	// Are you using Row Adaptive Mode? (Recommended!)
define ("INTHEAD",true);	// Do you want to use the internal page header?

require "common.php";
require "fix.php";
require "simple_html_dom.php";

echo "MAKECEEFAX.PHP ".VERSION." (c) Nathan Dane, 2019\r\n";
echo "Saving to ".PAGEDIR."/\r\n\r\n";

if(file_exists("active.mcx"))exit("MAKECEEFAX is already running\r\n");
file_put_contents("active.mcx","");

// Load Modules
$moduledir=file_get_contents("modules.txt");	// If there's no modules.txt, show's over. Need redundancy
$moduledir=explode("\r\n",$moduledir);

foreach ($moduledir as $key=>$module)
{
	if (!strncmp($module,"#",1))
	{
		unset($moduledir[$key]);	// If it's been commented out, just quietly ignore it.
		continue;
	}
	if(file_exists("make$module/make$module.php"))	// Make sure the module exists before trying to load it
	{
		include "make$module/make$module.php";	// Load it. Might remove the 'make'
	}
	else
	{
		echo ucfirst($module)." module not found\r\n";	// If it doesn't exist, don't try to include or run it 
		unset($moduledir[$key]);	// And delete it from the list of available modules
	}
}
foreach ($moduledir as $function)	// Run each available module's initial function
{
	$function="make".$function;
	echo "\r\nRunning $function...\r\n";
	$function();
	echo "$function finished\r\n";
}

$time_end=microtime(true);
$execution_time=($time_end-$time_start);
$time=date("H:i:s");
echo "MAKECEEFAX.PHP finished at $time, took ".$execution_time." seconds";	// Closing statement. Useful for logging (but not much else!)
?>