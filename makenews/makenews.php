<?php
/*
	makenews.php 
	Creates Ceefax Magazine 1 from http://bbc.co.uk/news
	makenews.php is part of makeceefax.php
	Nathan Dane, 2018
*/
require "simplenews.php";	// You should have got simplenews.php with this module
require "newsheader.php";

echo "Loaded MAKENEWS.PHP V0.1 (c) Nathan Dane, 2018\r\n";

function newsPage($page,$mpp)
{
	$line=4;
	$found=false;
	$para=array();
	$inserter=pageInserter("News Page $mpp $page[4]");
	$pheader=pageHeader($mpp);
	$iheader=intHeader();
	$nheader=newsHeader($page[4]);
	$title=outputLine($line,"C",$page[0],21);
	$line+=$title[0];
	$intro=outputLine($line," ",$page[5],21);
	$ln=$line;
	$ln+=$intro[0];
	foreach($page[6] as $element)
	{
		if ($ln>21)
			break;
		$ln++;
		$out=outputLine($ln,"F",$element,22);
		if ($out[1] !== false)
		{
			foreach($out[1] as $line)
			{
				array_push($para,$line);
			}
		}
		$ln+=$out[0];
	}
	$footer=newsFooter($nheader[1],$mpp);
	return array_merge($inserter,$pheader,$iheader,$nheader[0],$title[1],$intro[1],$para,$footer);
}

function newsHeadlines($pages,$region=false)
{
	if ($region)
	{
		$inserter=pageInserter("Regional Headlines 160");
		$pheader=pageHeader('160');
		$iheader=intHeader();
		$nheader=newsHeader(REGION);
		$footer=newsHeadlinesfooter($region);
	}
	else
	{
		$inserter=pageInserter("News Headlines 101");
		$pheader=pageHeader('101');
		$iheader=intHeader();
		$nheader=newsHeader('headlines');
		$footer=newsHeadlinesfooter($region);
	}
	$lines=array();
	$OL=4;
	$i=0;
	foreach ($pages as $page)
	{
		$headline=$page[0];
		if ($OL==4) $textcol="M";
		$headline=myTruncate2($headline, 70, " ");	// Cut headline to 70 chars
		$headline=wordwrap($headline,35,"\r\n");	// Wrap it for 2 lines. Not original, but required for longer headlines
		$headline=explode("\r\n",$headline);	// Convert it back to a string
		if (strlen($headline[0])<36)
		{
			$headline[0]=substr(str_pad($headline[0],35),0,35);
			$headline[0].='C';	// Yellow
		}
		array_push($lines,"OL,$OL,$textcol$headline[0]".(104+$i)."\r\n");
		if ($OL<5)
			$OL+=2;
		else
			$OL+=1;
		$textcol='G';	// white
		if(isset($headline[1]))
		{
		if (strlen($headline[1])<36)
		{
			$headline[1]=substr(str_pad($headline[1],39),0,39);
		}
		array_push($lines,"OL,$OL,$textcol$headline[1]"."\r\n");
		}
		if ($OL<7)
			$OL+=2;
		else
			$OL+=1;
		$i++;
		if ($i==8)
			break;
	}
	return array_merge($inserter,$pheader,$iheader,$nheader[0],$lines,$footer);
}

function makenews()
{
	$stories=array();
	$rstories=array();
	$rssfeed="http://feeds.bbci.co.uk/news/uk/rss.xml?edition=uk";	// BBC UK stories
	$rawFeed = file_get_contents($rssfeed);
	$xml = new SimpleXmlElement($rawFeed);
	$count=104;
	foreach($xml->channel->item as $chan) {
		// Don't want video/sport stories. They don't render too well on teletext
		if (strncmp($chan->title,"VIDEO:",6)) 
		if (strncmp($chan->link,"http://www.bbc.co.uk/sport/",26))
		{
			$url=$chan->link; 
			$str = file_get_html($url);
			$title=$str->find("link[rel=canonical]");
			$title=substr ($title[0],35);
			$title=substr($title, 0, strpos( $title, '"'));
			echo $title."\n";
			if (!strncmp($title,"/www.bbc.co.uk/news/av/",21))
			{
				continue 1;
			}
			echo $chan->title."\n";
			$name="news".$count;
			$$name=getNews($url,4);	// REEEALLY inefficiant. We're effectively downloading the page twice
			file_put_contents(PAGEDIR.'/'.PREFIX."$count.tti",(newsPage($$name,$count)));	// Make the ordinary pages while downloading
			$stories[]=$$name;
			$count++;
			if ($count>112) break;	// Stop after we get the pages that we want
		}
	}
	
	$rssfeed="http://feeds.bbci.co.uk/news/world/rss.xml?edition=uk";	// BBC world stories
	$rawFeed = file_get_contents($rssfeed);
	$xml = new SimpleXmlElement($rawFeed);
	foreach($xml->channel->item as $chan) {
		// Don't want video/sport stories. They don't render too well on teletext
		if (strncmp($chan->title,"VIDEO:",6)) 
		if (strncmp($chan->link,"http://www.bbc.co.uk/sport/",25))
		{
			$url=$chan->link; 
			$str = file_get_html($url);
			$title=$str->find("link[rel=canonical]");
			$title=substr ($title[0],35);
			$title=substr($title, 0, strpos( $title, '"'));
			echo $title."\n";
			if (!strncmp($title,"/www.bbc.co.uk/news/av/",21))
			{
				continue 1;
			}
			echo $chan->title."\n";
			$name="news".$count;
			$$name=getNews($url,4);
			file_put_contents(PAGEDIR.'/'.PREFIX."$count.tti",(newsPage($$name,$count)));
			$stories[]=$$name;
			$count++;
			if ($count>124) break;	// Stop after we get the pages that we want
		}
	}
	
	$count=161;
	$region=strtolower(REGION);
	$region=str_replace(' ','_',$region);
	$rssfeed="http://feeds.bbci.co.uk/news/$region/rss.xml";	// BBC regional stories
	$rawFeed = file_get_contents($rssfeed);
	$xml = new SimpleXmlElement($rawFeed);
	foreach($xml->channel->item as $chan) {
		// Don't want video/sport stories. They don't render too well on teletext
		if (strncmp($chan->title,"VIDEO:",6)) 
		if (strncmp($chan->link,"http://www.bbc.co.uk/sport/",25))
		{
			$url=$chan->link; 
			$str = file_get_html($url);
			$title=$str->find("link[rel=canonical]");
			$title=substr ($title[0],35);
			$title=substr($title, 0, strpos( $title, '"'));
			echo $title."\n";
			if (!strncmp($title,"/www.bbc.co.uk/news/av/",21))
			{
				continue 1;
			}
			echo $chan->title."\n";
			$name="news".$count;
			$$name=getNews($url,4);
			file_put_contents(PAGEDIR.'/'.PREFIX."$count.tti",(newsPage($$name,$count)));
			$rstories[]=$$name;
			$count++;
			if ($count>169) break;	// Stop after we get the pages that we want
		}
	}
	
	file_put_contents(PAGEDIR.'/'.PREFIX."101.tti",(newsHeadlines($stories)));
	file_put_contents(PAGEDIR.'/'.PREFIX."160.tti",(newsHeadlines($rstories,true)));
}
// OK, that's 30 pages of news made. Now for the indexes!