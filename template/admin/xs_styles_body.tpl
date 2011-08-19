<?php

$override = $config['override_user_style'];
$style = $config['default_style'];

?>

<br /><br />

<table width="100%" cellpadding="4" cellspacing="1" border="0" class="forumline">
	<tr>
		<th class="thCornerL" align="center" nowrap="nowrap">{L_XS_STYLES_ID}</th>
		<th class="thTop" align="center" nowrap="nowrap">{L_XS_STYLES_STYLE}</th>
		<th class="thTop" align="center" nowrap="nowrap">{L_XS_STYLES_TEMPLATE}</th>
		<th class="thTop" align="center" nowrap="nowrap">{L_XS_STYLES_USER}</th>
		<th class="thCornerR" colspan="2" align="center" nowrap="nowrap">{L_XS_STYLES_OPTIONS}</th>
	</tr>
	<!-- BEGIN styles -->
	<?php
		$id = $styles_item['ID'];
		$users = $styles_item['TOTAL'];
		$default = ($style == $id) ? 1 : 0;
		if($default)
		{
			$row1 = 'row3';
			$row2 = 'row3';
			$row3 = 'row3';
			$row_total = 'row3';
		}
		else
		{
			$row1 = 'row1';
			$row2 = 'row2';
			$row3 = 'row3';
			$row_total = ($users > 0) ? 'row3' : 'row1';
		}
	?>
	<tr>
		<td class="<?php echo $row2; ?>" align="center"><span class="gen">{styles.ID}</span></td>
		<td class="<?php echo $row1; ?>" align="left" nowrap="nowrap"><span class="gen">{styles.STYLE}</span></td>
		<td class="<?php echo $row2; ?>" align="left" nowrap="nowrap"><span class="gen">{styles.TEMPLATE}</span></td>
		<td class="<?php echo $row_total; ?>" align="center">{styles.TOTAL}</td>
		<td class="<?php echo $row1; ?>" align="center" valign="middle" nowrap="nowrap"><span class="gensmall">
		<?php
		 if(!$default) {
		?>
		[<a href="{SCRIPT}setdefault={styles.ID}">{L_XS_STYLES_SET_DEFAULT}</a>]<br />
		<?php } else if($override) { ?>
		[<a href="{SCRIPT}setoverride=0">{L_XS_STYLES_NO_OVERRIDE}</a>]<br />
		<?php } else { ?>
		[<a href="{SCRIPT}setoverride=1">{L_XS_STYLES_DO_OVERRIDE}</a>]<br />
		<?php } ?>
		[<a href="{SCRIPT}moveusers={styles.ID}">{L_XS_STYLES_SWITCH_ALL}</a>]
		</span></td>
		<?php if($users) { ?>
		<form action="{SCRIPT}" method="get" name="select_{styles.ID}" onsubmit="if(document.select_{styles.ID}.style.value == -1){return false;}">{S_HIDDEN_FIELDS}<input type="hidden" name="moveaway" value="{styles.ID}" />
		<td class="<?php echo $row1; ?>" align="center" valign="middle" nowrap="nowrap"><span class="gensmall">
		<select name="style" onchange="document.select_{styles.ID}.submit();">
		<option value="">{L_XS_STYLES_SWITCH_ALL2}</option>
		<option value="0">{L_XS_STYLES_DEFSTYLE}</option>
		<optgroup label="{L_XS_STYLES_AVAILABLE}">
		<?php
			for($i=0; $i<$styles_count; $i++)
			if($i != $styles_i)
			{
				$item = &$this->_tpldata['styles.'][$i];
				echo '<option value="', $item['ID'], '">', $item['STYLE'], '</option>';
			}
		?>
		</optgroup>
		</select>
		</span></td>
		</form>
		<?php } else { ?>
		<td class="<?php echo $row1; ?>">&nbsp;</td>
		<?php } ?>
	</tr>
	<!-- END styles -->
</table>

<br />
