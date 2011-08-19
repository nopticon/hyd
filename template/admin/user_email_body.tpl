
{ERROR_BOX}

	<table width="99%" cellpadding="3" cellspacing="0" border="0" align="center" class="forumline">
		<tr>
			<td class="catHead" align="center"><span class="genmed"><b>{L_EMAIL_TITLE}</b></span></td>
		</tr>
		<tr>
			<td height="1" bgcolor="2E2E2C"></td>
		</tr>
		<tr>
			<td>
				<table width="100%" cellpadding="5" cellspacing="0" border="0" align="center">
					<tr>
						<td class="mmb">{L_EMAIL_EXPLAIN}</td>
					</tr>
					<tr>
						<td class="mmb">
							<table width="100%" cellpadding="3" cellspacing="0" border="0" align="center" class="forumline">
								<tr>
									<td class="catHead" align="center"><span class="genmed"><b>{L_COMPOSE}</b></span></td>
								</tr>
								<tr>
									<td height="1" bgcolor="2E2E2C"></td>
								</tr>
								<tr>
									<td>
										<table width="100%" cellpadding="5" cellspacing="0" border="0" align="center" class=" class="mmb">
										<form action="{S_USER_ACTION}" method="post">{S_HIDDEN_FIELDS}
											<tr>
												<td class="row1" align="right"><b>{L_RECIPIENTS}</b></td>
												<td class="row2" align="left">{S_GROUP_SELECT}</td>
											</tr>
											<tr>
												<td class="row1" align="right"><b>{L_EMAIL_SUBJECT}</b></td>
												<td class="row2"><span class="gen"><input type="text" name="subject" size="45" maxlength="100" tabindex="2" class="post" value="{SUBJECT}" /></span></td>
											</tr>
											<tr>
												<td class="row1" align="right" valign="top"> <span class="gen"><b>{L_EMAIL_MSG}</b></span> 
												<td class="row2"><span class="gen"> <textarea name="message" rows="15" cols="35" wrap="virtual" style="width:450px" tabindex="3" class="post">{MESSAGE}</textarea></span>
											</tr>
											<tr>
												<td class="mmb" align="center" colspan="2"><input type="submit" name="submit" value="Enviar {L_EMAIL}"></td>
											</tr>
										</form>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	