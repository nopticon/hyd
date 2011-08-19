<h1>{L_ADMIN_USERS_LIST_MAIL_TITLE}</h1>

<p>{L_ADMIN_USERS_LIST_MAIL_EXPLAIN}</p>
<table width="100%" cellpadding="6" cellspacing="1" border="0" class="forumline">
	<tr>
		<th class="thTop" height="25" valign="middle" nowrap="nowrap">{L_USERNAME}</th>
		<th class="thTop" height="25" valign="middle" nowrap="nowrap">{L_EMAIL}</th>
	</tr>
	<!-- BEGIN userrow -->
	<tr>
		<td class="{userrow.COLOR}" align="center" valign="middle" height="28" nowrap="nowrap"><span class="genmed"><a href="{userrow.U_ADMIN_USER}">{userrow.USERNAME}</a></span></td>
		<td class="{userrow.COLOR}" align="center" valign="middle" height="28" nowrap="nowrap"><span class="genmed"><a href="mailto:{userrow.EMAIL}">{userrow.EMAIL}</a></span></td>
	</tr>
	<!-- END userrow -->
	<tr>
		<td class="catBottom" height="28" align="center" valign="middle" colspan="8">
		</td>
	</tr>
</table>