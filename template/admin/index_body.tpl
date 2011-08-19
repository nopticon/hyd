
<table width="100%" cellpadding="4" cellspacing="1" border="0">
	<tr>
		<td>
			<h1>{L_WELCOME}</h1>
			<p>{L_ADMIN_INTRO}</p>
			<h1>{L_FORUM_STATS}</h1>
		</td>
	</tr>
	<tr>
		<td>
			<table width="100%" cellpadding="4" cellspacing="1" border="0" class="adminlist">
				<tr class="hdr"> 
					<td align="center" width="25%" nowrap="nowrap" height="25"><b>{L_STATISTIC}</b></td>
					<td align="center" width="25%" height="25"><b>{L_VALUE}</b></td>
					<td align="center" width="25%" nowrap="nowrap" height="25"><b>{L_STATISTIC}</b></td>
					<td align="center" width="25%" height="25"><b>{L_VALUE}</b></td>
				</tr>
				<tr> 
				<td class="row1" nowrap="nowrap">{L_NUMBER_POSTS}:</td>
				<td class="row2"><b>{NUMBER_OF_POSTS}</b></td>
				<td class="row1" nowrap="nowrap">{L_POSTS_PER_DAY}:</td>
				<td class="row2"><b>{POSTS_PER_DAY}</b></td>
				</tr>
				<tr> 
				<td class="row1" nowrap="nowrap">{L_NUMBER_TOPICS}:</td>
				<td class="row2"><b>{NUMBER_OF_TOPICS}</b></td>
				<td class="row1" nowrap="nowrap">{L_TOPICS_PER_DAY}:</td>
				<td class="row2"><b>{TOPICS_PER_DAY}</b></td>
				</tr>
				<tr> 
				<td class="row1" nowrap="nowrap">{L_NUMBER_USERS}:</td>
				<td class="row2"><b>{NUMBER_OF_USERS}</b></td>
				<td class="row1" nowrap="nowrap">{L_USERS_PER_DAY}:</td>
				<td class="row2"><b>{USERS_PER_DAY}</b></td>
				</tr>
				<tr> 
				<td class="row1" nowrap="nowrap">{L_BOARD_STARTED}:</td>
				<td class="row2"><b>{START_DATE}</b></td>
				<td class="row1" nowrap="nowrap">{L_AVATAR_DIR_SIZE}:</td>
				<td class="row2"><b>{AVATAR_DIR_SIZE}</b></td>
				</tr>
				<tr> 
				<td class="row1" nowrap="nowrap">{L_DB_SIZE}:</td>
				<td class="row2"><b>{DB_SIZE}</b></td>
				<td class="row1" nowrap="nowrap">{L_GZIP_COMPRESSION}:</td>
				<td class="row2"><b>{GZIP_COMPRESSION}</b></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td><h1>{L_WHO_IS_ONLINE}</h1></td>
	</tr>
	<tr>
		<td>
			<table width="100%" cellpadding="4" cellspacing="1" border="0" class="adminlist">
				<tr class="hdr"> 
				<td align="center" width="20%" height="25"><b> {L_USERNAME} </b></th>
				<td align="center" width="20%" height="25"><b> {L_STARTED} </b></th>
				<td align="center" width="20%"><b> {L_LAST_UPDATE} </b></th>
				<td align="center" width="20%"><b> {L_FORUM_LOCATION} </b></th>
				<td align="center" width="20%" height="25"><b> {L_IP_ADDRESS} </b></th>
				</tr>
				<!-- BEGIN reg_user_row -->
				<tr> 
				<td width="20%" class="{reg_user_row.ROW_CLASS}"> <span class="gen"><a href="{reg_user_row.U_USER_PROFILE}" class="gen">{reg_user_row.USERNAME}</a></span> </td>
				<td width="20%" class="{reg_user_row.ROW_CLASS}" align="center"> <span class="gen">{reg_user_row.STARTED}</span> </td>
				<td width="20%" class="{reg_user_row.ROW_CLASS}" align="center" nowrap="nowrap"> <span class="gen">{reg_user_row.LASTUPDATE}</span> </td>
				<td width="20%" class="{reg_user_row.ROW_CLASS}"> <span class="gen"><a href="{reg_user_row.U_FORUM_LOCATION}" class="gen">{reg_user_row.FORUM_LOCATION}</a></span> </td>
				<td width="20%" class="{reg_user_row.ROW_CLASS}"> <span class="gen"><a href="{reg_user_row.U_WHOIS_IP}" class="gen" target="_phpbbwhois">{reg_user_row.IP_ADDRESS}</a></span> </td>
				</tr>
				<!-- END reg_user_row -->
				<tr class="hdr"> 
				<td colspan="5" height="1"><img src="{TEMPLATE}images/spacer.gif" width="1" height="1" alt=""></td>
				</tr>
				<!-- BEGIN guest_user_row -->
				<tr> 
				<td width="20%" class="{guest_user_row.ROW_CLASS}"> <span class="gen">{guest_user_row.USERNAME}</span> </td>
				<td width="20%" class="{guest_user_row.ROW_CLASS}" align="center"> <span class="gen">{guest_user_row.STARTED}</span> </td>
				<td width="20%" class="{guest_user_row.ROW_CLASS}" align="center" nowrap="nowrap"> <span class="gen">{guest_user_row.LASTUPDATE}</span> </td>
				<td width="20%" class="{guest_user_row.ROW_CLASS}"> <span class="gen"><a href="{guest_user_row.U_FORUM_LOCATION}" class="gen">{guest_user_row.FORUM_LOCATION}</a></span> </td>
				<td width="20%" class="{guest_user_row.ROW_CLASS}"> <span class="gen"><a href="{guest_user_row.U_WHOIS_IP}" target="_phpbbwhois">{guest_user_row.IP_ADDRESS}</a></span> </td>
				</tr>
				<!-- END guest_user_row -->
			</table>
		</td>
	</tr>
</table>
