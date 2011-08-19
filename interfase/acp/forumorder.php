<?php
// -------------------------------------------------------------
// $Id: mass_email.php,v 1.12 2006/11/02 00:20:00 Psychopsia Exp $
//
// STARTED   : Mon Oct 23, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

//
// Do the job ...
//
if ($submit)
{
	$list = request_var('listContainer', array(0));
	
	$orderid = 10;
	foreach ($list as $catid) {
		$sql = 'UPDATE _forums
			SET forum_order = ' . (int) $orderid . '
			WHERE forum_id = ' . (int) $catid;
		$db->sql_query($sql);
		
		$orderid += 10;
	}
	
	_die('Update.');
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Forum order</title>
<link rel="stylesheet" type="text/css" href="style.css">
<script src="/net/scripts/prototype.js"></script>
<script src="/net/scripts/scriptaculous.js"></script>

<script>
Event.observe(window,'load',init,false);
function init() {
	Sortable.create('listContainer',{tag:'div',onUpdate:updateList});
}

function updateList(container) {
	var url = '_acp.forumorder.php';
	var params = Sortable.serialize(container.id);
	var ajax = new Ajax.Request(url,{
		method: 'post',
		parameters: 'submit=1&' + params,
		onLoading: function(){$('workingMsg').show()},
		onLoaded: function(){$('workingMsg').hide()}
	});
}
</script>
</head>

<body>
<div id="listContainer">
	<?php
	
	$sql = 'SELECT forum_id, forum_name
		FROM _forums
		ORDER BY forum_order ASC';
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
	?>
	<div id="item_<?php echo $row['forum_id']; ?>"><?php echo $row['forum_name']; ?></div>
	<?php
	}
	$db->sql_freeresult($result);
	?>
</div>

<div id="workingMsg" style="display:none;">Actualizando...</div>
</body>
</html>