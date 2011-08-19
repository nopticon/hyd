
<br /><br />

<form action="admin_xs_config.{PHP}" method="post"><table width="99%" cellpadding="4" cellspacing="1" border="0" align="center" class="forumline">
	<tr>
	  <th class="thHead" colspan="2">{L_XS_SETTINGS}</th>
	</tr>
	<!-- BEGIN switch_updated -->
	<tr>
		<td class="row3" colspan="2" align="left">{L_XS_UPDATED}<br /><br /><span class="gensmall">{L_XS_UPDATED_EXPLAIN}</span></td>
	</tr>
	<!-- END switch_updated -->
	<!-- BEGIN switch_xs_warning -->
	<tr>
		<td class="row3" colspan="2" align="left">{L_XS_WARNING}<br /><br /><span class="gensmall">{L_XS_WARNING_EXPLAIN}</span></td>
	</tr>
	<!-- END switch_xs_warning -->
	<tr>
		<td class="row1">{L_XS_DEF_TEMPLATE}<br /><span class="gensmall">{L_XS_DEF_TEMPLATE_EXPLAIN}</span></td>
		<td class="row2"><input type="text" name="xs_def_template" value="{XS_DEF_TEMPLATE}" /></td>
	</tr>
	<tr>
		<td class="row1">{L_XS_CHECK_SWITCHES}<br /><span class="gensmall">{L_XS_CHECK_SWITCHES_EXPLAIN}</span></td>
		<td class="row2">
			<input type="radio" name="xs_check_switches" value="0" <?php echo !$this->vars['XS_CHECK_SWITCHES'] ? 'checked="checked" ' : ''; ?>/> {L_XS_CHECK_SWITCHES_0}<br />
			<br />
			<input type="radio" name="xs_check_switches" value="2" <?php echo $this->vars['XS_CHECK_SWITCHES'] == 2 ? 'checked="checked" ' : ''; ?>/> {L_XS_CHECK_SWITCHES_2}<br />
			<br />
			<input type="radio" name="xs_check_switches" value="1" <?php echo $this->vars['XS_CHECK_SWITCHES'] == 1 ? 'checked="checked" ' : ''; ?>/> {L_XS_CHECK_SWITCHES_1}
		</td>
	</tr>
	<tr>
		<td class="row1">{L_XS_USE_ISSET}</td>
		<td class="row2"><input type="radio" name="xs_use_isset" value="1" <?php echo $this->vars['XS_USE_ISSET'] ? 'checked="checked" ' : ''; ?>/> {L_YES}&nbsp;&nbsp;<input type="radio" name="xs_use_isset" value="0" <?php echo !$this->vars['XS_USE_ISSET'] ? 'checked="checked" ' : ''; ?>/> {L_NO}</td>
	</tr>
	<tr>
	  <th class="thHead" colspan="2">{L_XS_SETTINGS_CACHE}</th>
	</tr>
	<tr>
		<td class="row1">{L_XS_USE_CACHE}<br /><span class="gensmall">{L_XS_CACHE_EXPLAIN}</span></td>
		<td class="row2"><input type="radio" name="xs_use_cache" value="1" <?php echo $this->vars['XS_USE_CACHE'] ? 'checked="checked" ' : ''; ?>/> {L_YES}&nbsp;&nbsp;<input type="radio" name="xs_use_cache" value="0" <?php echo !$this->vars['XS_USE_CACHE'] ? 'checked="checked" ' : ''; ?>/> {L_NO}</td>
	</tr>
	<tr>
		<td class="row1">{L_XS_CACHE_DIR}<br /><span class="gensmall">{L_XS_CACHE_DIR_EXPLAIN}</span></td>
		<td class="row2" nowrap="nowrap"><input type="text" name="xs_cache_dir" value="{XS_CACHE_DIR}" /><br />
			<br />
			<input type="radio" name="xs_cache_dir_absolute" value="1" <?php echo $this->vars['XS_CACHE_DIR_ABSOLUTE'] ? 'checked="checked" ' : ''; ?>/> {L_XS_DIR_ABSOLUTE}<br />
			<span class="gensmall">{L_XS_DIR_ABSOLUTE_EXPLAIN}</span><br />
			<input type="radio" name="xs_cache_dir_absolute" value="0" <?php echo !$this->vars['XS_CACHE_DIR_ABSOLUTE'] ? 'checked="checked" ' : ''; ?>/> {L_XS_DIR_RELATIVE}<br />
			<span class="gensmall">{L_XS_DIR_RELATIVE_EXPLAIN}</span>
		</td>
	</tr>
	<tr>
		<td class="row1">{L_XS_AUTO_COMPILE}<br /><span class="gensmall">{L_XS_AUTO_COMPILE_EXPLAIN}</span></td>
		<td class="row2"><input type="radio" name="xs_auto_compile" value="1" <?php echo $this->vars['XS_AUTO_COMPILE'] ? 'checked="checked" ' : ''; ?>/> {L_YES}&nbsp;&nbsp;<input type="radio" name="xs_auto_compile" value="0" <?php echo !$this->vars['XS_AUTO_COMPILE'] ? 'checked="checked" ' : ''; ?>/> {L_NO}</td>
	</tr>
	<tr>
		<td class="row1">{L_XS_AUTO_RECOMPILE}<br /><span class="gensmall">{L_XS_AUTO_RECOMPILE_EXPLAIN}</span></td>
		<td class="row2"><input type="radio" name="xs_auto_recompile" value="1" <?php echo $this->vars['XS_AUTO_RECOMPILE'] ? 'checked="checked" ' : ''; ?>/> {L_YES}&nbsp;&nbsp;<input type="radio" name="xs_auto_recompile" value="0" <?php echo !$this->vars['XS_AUTO_RECOMPILE'] ? 'checked="checked" ' : ''; ?>/> {L_NO}</td>
	</tr>
	<tr>
		<td class="row1">{L_XS_SEPARATOR}<br /><span class="gensmall">{L_XS_SEPARATOR_EXPLAIN}</span></td>
		<td class="row2"><input type="text" name="xs_separator" value="{XS_SEPARATOR}" /></td>
	</tr>
	<tr>
		<td class="row1">{L_XS_PHP}<br /><span class="gensmall">{L_XS_PHP_EXPLAIN}</span></td>
		<td class="row2"><input type="text" name="xs_php" value="{XS_PHP}" /></td>
	</tr>
	<tr>
		<td class="catBottom" colspan="2" align="center">{S_HIDDEN_FIELDS}<input type="submit" name="submit" value="{L_SUBMIT}" class="bold" />&nbsp;&nbsp;<input type="reset" value="{L_RESET}" class="liteoption" />
		</td>
	</tr>
</table></form>

<br clear="all" />


<table width="99%" cellpadding="4" cellspacing="1" border="0" align="center" class="forumline">
	<tr>
	  <th class="thHead" colspan="2">{L_XS_DEBUG_HEADER}</th>
	</tr>
	<tr>
		<td colspan="2" class="row3" align="center"><span class="gensmall">{L_XS_DEBUG_EXPLAIN}</span></td>
	</tr>
	<tr>
		<th class="thHead" colspan="2">{L_XS_DEBUG_VARS}</th>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{<b></b>TEMPLATE<b></b>}</span></td>
		<td class="row2" align="left"><span class="gen">{TEMPLATE}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{<b></b>PHP<b></b>}</span></td>
		<td class="row2" align="left"><span class="gen">{PHP}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{<b></b>TEMPLATE_NAME<b></b>}</span></td>
		<td class="row2" align="left"><span class="gen">{TEMPLATE_NAME}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{<b></b>LANG<b></b>}</span></td>
		<td class="row2" align="left"><span class="gen">{LANG}</span></td>
	</tr>
	<tr>
		<th class="thHead" colspan="2">{XS_DEBUG_HDR1}</th>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_TPL_NAME}</span></td>
		<td class="row2" align="left"><span class="gen">{XS_DEBUG_FILENAME1}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_CACHE_FILENAME}</span></td>
		<td class="row2" align="left"><span class="gen">{XS_DEBUG_FILENAME2}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_DATA}</span></td>
		<td class="row2" align="left"><span class="gensmall">{XS_DEBUG_DATA}</span></td>
	</tr>
	<tr>
		<th class="thHead" colspan="2">{XS_DEBUG_HDR2}</th>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_TPL_NAME}</span></td>
		<td class="row2" align="left"><span class="gen">{XS_DEBUG_FILENAME3}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_CACHE_FILENAME}</span></td>
		<td class="row2" align="left"><span class="gen">{XS_DEBUG_FILENAME4}</span></td>
	</tr>
	<tr>
		<td class="row1" align="left"><span class="gen">{L_XS_DEBUG_DATA}</span></td>
		<td class="row2" align="left"><span class="gensmall">{XS_DEBUG_DATA2}</span></td>
	</tr>
</table>
