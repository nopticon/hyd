
<h1>{L_GROUP_TITLE}</h1>

<form action="{S_GROUP_ACTION}" method="post" name="post"><table border="0" cellpadding="3" cellspacing="1" class="forumline" align="center">
	<tr> 
	  <th class="thHead" colspan="2">{L_GROUP_EDIT_DELETE}</th>
	</tr>
	<tr>
	  <td class="row1" colspan="2"><span class="gensmall">{L_ITEMS_REQUIRED}</span></td>
	</tr>
	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_GROUP_NAME}:</span></td>
	  <td class="row2" width="62%"> 
		<input type="text" name="group_name" size="35" maxlength="40" value="{GROUP_NAME}" />
	  </td>
	</tr>
	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_GROUP_DESCRIPTION}:</span></td>
	  <td class="row2" width="62%"> 
		<textarea name="group_description" rows=5 cols=51>{GROUP_DESCRIPTION}</textarea>
	  </td>
	</tr>
	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_GROUP_MODERATOR}:</span></td>
	  <td class="row2" width="62%"><input type="text" class="post" name="username" maxlength="50" size="20" value="{GROUP_MODERATOR}" />   <input type="submit" name="usersubmit" value="{L_FIND_USERNAME}" class="liteoption" onclick="window.open('{U_SEARCH_USER}', '_phpbbsearch', 'HEIGHT=250,resizable=yes,WIDTH=400');return false;" /></td>
	</tr>

	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_GROUP_STATUS}:</span></td>
	  <td class="row2" width="62%"> 
		<input type="radio" name="group_type" value="{S_GROUP_OPEN_TYPE}" {S_GROUP_OPEN_CHECKED} /> {L_GROUP_OPEN}   <input type="radio" name="group_type" value="{S_GROUP_CLOSED_TYPE}" {S_GROUP_CLOSED_CHECKED} />	{L_GROUP_CLOSED}   <input type="radio" name="group_type" value="{S_GROUP_HIDDEN_TYPE}" {S_GROUP_HIDDEN_CHECKED} />	{L_GROUP_HIDDEN}</td> 
	</tr>
	<tr> 
  <td class="row1" width="38%"><span class="gen">{L_GROUP_ALLOW_EMAIL}:</span><br/>
  <span class="gensmall">{L_GROUP_ALLOW_EMAIL_EXPLAIN}</span></td>
  <td class="row2" width="62%">
	<input type="radio" name="group_allow_email" value="{S_GROUP_ALL_ALLOW_EMAIL}" {S_GROUP_ALL_ALLOW_EMAIL_CHECKED} /> {L_GROUP_ALL_ALLOW_EMAIL}   
	<input type="radio" name="group_allow_email" value="{S_GROUP_REG_ALLOW_EMAIL}" {S_GROUP_REG_ALLOW_EMAIL_CHECKED} /> {L_GROUP_REG_ALLOW_EMAIL}   
	<input type="radio" name="group_allow_email" value="{S_GROUP_PRIVATE_ALLOW_EMAIL}" {S_GROUP_PRIVATE_ALLOW_EMAIL_CHECKED} /> {L_GROUP_PRIVATE_ALLOW_EMAIL}   
	<input type="radio" name="group_allow_email" value="{S_GROUP_MOD_ALLOW_EMAIL}" {S_GROUP_MOD_ALLOW_EMAIL_CHECKED} /> {L_GROUP_MOD_ALLOW_EMAIL}   
	<input type="radio" name="group_allow_email" value="{S_GROUP_ADMIN_ALLOW_EMAIL}" {S_GROUP_ADMIN_ALLOW_EMAIL_CHECKED} /> {L_GROUP_ADMIN_ALLOW_EMAIL} 
  </td> 
</tr>
	<tr> 
		<td class="row1" width="38%"><span class="gen">{L_GROUP_COUNT}:<br/>{L_GROUP_COUNT_MAX}:</span><br/>
		<span class="gensmall">{L_GROUP_COUNT_EXPLAIN}</span></td>
		<td class="row2" width="62%"><input type="text" class="post" name="group_count" maxlength="12" size="12" value="{GROUP_COUNT}" /><br/><input type="text" class="post" name="group_count_max" maxlength="12" size="12" value="{GROUP_COUNT_MAX}" />
		<br/>   <span class="gen"></span><input type="checkbox" name="group_count_enable" {GROUP_COUNT_ENABLE_CHECKED} > {L_GROUP_COUNT_ENABLE}
		<br/>   <input type="checkbox" name="group_count_update" value="0"/> {L_GROUP_COUNT_UPDATE}
		<br/>   <input type="checkbox" name="group_count_delete" value="0"/> {L_GROUP_COUNT_DELETE}</span>
		</td>
	</tr>
	<!-- BEGIN group_edit -->
	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_DELETE_MODERATOR}</span>
	  <br />
	  <span class="gensmall">{L_DELETE_MODERATOR_EXPLAIN}</span></td>
	  <td class="row2" width="62%"> 
		<input type="checkbox" name="delete_old_moderator" value="1">
		{L_YES}</td>
	</tr>
	<tr> 
	  <td class="row1" width="38%"><span class="gen">{L_GROUP_DELETE}:</span></td>
	  <td class="row2" width="62%"> 
		<input type="checkbox" name="group_delete" value="1">
		{L_GROUP_DELETE_CHECK}</td>
	</tr>
	
	<!-- END group_edit -->
	<tr> 
	  <td class="catBottom" colspan="2" align="center"><span class="cattitle"> 
		<input type="submit" name="group_update" value="{L_SUBMIT}" class="bold" />
		   
		<input type="reset" value="{L_RESET}" name="reset" class="liteoption" />
		</span></td>
	</tr>
</table>{S_HIDDEN_FIELDS}</form>
