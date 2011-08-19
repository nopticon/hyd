<!-- INCLUDE slide.htm -->
 
<table width="100%" cellpadding="0" cellspacing="3" border="0" align="center">
  <tr> 
	<td align="center" > 
	  <table width="100%" cellpadding="4" cellspacing="1" border="0" class="adminlist">
			<tr class="hdr"> 
				<td height="25"><b>{L_ADMIN}</b></td>
			</tr>
			<tr class="row1"> 
				<td><span class="genmed"><a href="{U_ADMIN_INDEX}" target="main" class="genmed">{L_ADMIN_INDEX}</a></span></td>
			</tr>
			<tr class="row2"> 
				<td><span class="genmed"><a href="{U_FORUM_INDEX}" target="_parent" class="genmed">{L_FORUM_INDEX}</a></span></td>
			</tr>
			<tr class="row1"> 
				<td><span class="genmed"><a href="{U_FORUM_INDEX}" target="main" class="genmed">{L_PREVIEW_FORUM}</a></span></td>
			</tr>
			<!-- BEGIN catrow -->
			<tr class="hdr"> 
				<td height="28" style="cursor:pointer;cursor:hand;" onclick="onMenuCatClick('{catrow.MENU_CAT_ID}');"><span class="cattitle">{catrow.ADMIN_CATEGORY}</span></td>
			</tr>
			<tr> 
			<td class="row1">
				<div id="menuCat_{catrow.MENU_CAT_ID}" style="display:block;">
					<table width="100%" cellpadding="4" cellspacing="1" border="0" class="bodyline">
			<!-- BEGIN modulerow -->
			<tr class="{modulerow.ROW_CLASS}"> 
				<td><div id="menuCat_{catrow.MENU_CAT_ID}_{catrow.modulerow.ROW_COUNT}" style="display:block;" class="genmed"><a href="{catrow.modulerow.U_ADMIN_MODULE}"  target="main" class="genmed">{catrow.modulerow.ADMIN_MODULE}</a></div> 
				</td>
			</tr>
			<!-- END modulerow -->
					</table>
				</div>
			</td>
		</tr>
			<!-- END catrow -->
	  </table>
	</td>
  </tr>
</table>
