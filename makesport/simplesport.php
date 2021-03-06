<?php
/*
	simplesport.php - ALPHA version
	Nathan J. Dane, 2018.
	Returns a BBC Sport page as an array
	
	Layout (TBC):
	Short Title
	Description
	URL
	Sport (e.g. Football)
	Summary
	paragraphs(in an array)
	
*/

function getSport($url,$limit)
{
	$found=false;
	$html = file_get_html($url);	// Under NO circumstances should $html be overwritten. It's here to stay.
	
	if ($html===false) return false;
	
	$URL=$html->find("meta[property=og:url]",0);	// URL. The BBC try to hide the AV URL behind a legitamite one, 
	$URL=$URL->content;								// So we have to take drastic measures to remove them
	$URL=htmlspecialchars_decode($URL);
	if(!strncmp($URL,"https://www.bbc.com/sport/av/",30)) // Don't even try AV pages
	{
		echo "Skipped: AV Story\r\n";
		return false;
	}
	
	$stitle=$html->find("meta[property=og:title]",0);	// Short title
	$stitle=$stitle->content;
	$stitle=htmlspecialchars_decode($stitle);
	
	$ltitle=$html->find("title",0)->plaintext;	// Long title
	$ltitle=htmlspecialchars_decode($ltitle);
	
	$desc=$html->find('meta[property=og:description]',0);	// Description
	$desc=$desc->content;
	$desc=htmlspecialchars_decode($desc);
	
	$area=$html->find('meta[property=article:section]',0);	// Area
	$area=$area->content;
	$area=htmlspecialchars_decode($area);
	
	$intro=$html->find('p[class=sp-story-body__introduction]',0)->plaintext;	// Summary
	if($intro==false)$found=true;
	$intro=htmlspecialchars_decode($intro);
	
	if($found==true)$html=$html->find('div[id="orb-modules"]',0);
	if(!$html) return false;	// Something has went desperately wrong
	
	$paragraph='';
	$i=0;
	foreach ($html->find('p') as $para)
	{
		if($i<$limit && $found==true)
		{
			$paragraph[]=$para->plaintext;
			$i++;
		}
		if (strpos($para,"introduction"))
		$found=true;
	}
	return array($stitle,$ltitle,$desc,$url,$area,$intro,$paragraph);
	//				1		2		3	4	5		6		7
}
?>