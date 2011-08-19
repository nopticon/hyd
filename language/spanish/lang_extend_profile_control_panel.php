<?php
/*
<Orion, a web development framework for RK.>
Copyright (C) <2011>  <Orion>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if ( !defined('IN_NUCLEO') )
{
	die('Rock Republik &copy; 2006');
}

// admin part
if ( $lang_extend_admin )
{
	$lang['Lang_extend_profile_control_panel'] = 'Profile Control Panel language pack';
}

// who's online
$lang['Admin_founder_online_color'] = '%sFundador del Foro%s';
$lang['User_online_color'] = '%sUsuario%s';

// topic or privmsg display
$lang['Add_to_friend_list']								= 'Agregar a tu lista de amigos';
$lang['Remove_from_friend_list']					= 'Quitar de tu lista de amigos';
$lang['Add_to_ignore_list']								= 'Agregar a tu lista de ignorados';
$lang['Remove_from_ignore_list']					= 'Quitar de tu lista de ignorados';
$lang['Happy_birthday']										= 'Feliz Cumplea�os!';
$lang['Ignore_choosed']										= 'Has seleccionado ignorar a este usuario';
$lang['Online']														= 'Conectado';
$lang['Offline']													= 'Desconectado';
$lang['Hidden']														= 'Oculto';
$lang['Gender']														= 'G�nero';
$lang['Male']															= 'Masculino';
$lang['Female']														= 'Femenino';
$lang['No_gender_specify']								= 'Desconocido';
$lang['Age']															= 'Edad';
$lang['Do_not_allow_pm']									= 'Este usuario no acepta mensajes privados';
$lang['Ignore']														= 'Ignorar';

// main entry (profile.php)
$lang['Click_return_profilcp']						= 'Click %sAqui%s para regresar al perfil';

// birthday popup (profile_birthday.php)
$lang['Birthday']													= 'Cumplea�os';
$lang['birthday_msg']											= 'Hola %s, <br /><br /><br /> %s te desea feliz cumplea�os,<br>gracias por visitarnos!';

// home panel (profilcp_home.php)
$lang['profilcp_index_shortcut']					= 'Principal';
$lang['profilcp_index_pagetitle']					= 'Principal Perfil Privado';

// home panel : mini buddy list (functions_profile.php)
$lang['Friend_list']											= 'Lista de Amigos';
$lang['Friend_list_of']										= 'Amigo de';
$lang['Ignore_list']											= 'Lista de Ignorados';
$lang['Ignore_list_of']										= 'Ignorado por';
$lang['Nobody']														= 'Ninguno';
$lang['Always_visible']										= 'Siempre visible para este usuario';
$lang['Not_always_visible']								= 'Este usuario no te ver� cuando est�s en modo oculto';

// home panel : watched topics (functions_profile.php)
$lang['Stop_watching_selected_topics']		= 'Dejar de observar los temas seleccionados';
$lang['New_subscribed_topic']							= 'Temas subscritos';
$lang['Submit_period']										= 'Ver temas desde';

// buddy list (profilcp_buddy.php)
$lang['profilcp_buddy_shortcut']					= 'Amigos';
$lang['profilcp_buddy_pagetitle']					= 'Lista de Amigos';
$lang['profilcp_buddy_friend_shortcut']		= 'Lista de Amigos';
$lang['profilcp_buddy_friend_pagetitle']	= 'Editar tu lista de amigos';
$lang['profilcp_buddy_ignore_shortcut']		= 'Lista de Ignorados';
$lang['profilcp_buddy_ignore_pagetitle']	= 'Editar tu lista de Ignorados';
$lang['profilcp_buddy_list_shortcut']			= 'Todos los miembros';
$lang['profilcp_buddy_list_pagetitle']		= 'Lista de Miembros';
$lang['Click_return_privmsg']							= 'Click %sAqui%s para regresar al mensaje privado';
$lang['profilcp_buddy_could_not_add_user']= 'El usuario seleccionado no existe';
$lang['profilcp_buddy_could_not_anon_user']= 'No puedes hacer a An�nino tu amigo';
$lang['profilcp_buddy_add_yourself']			= 'No puedes auto hacerte tu amigo';
$lang['profilcp_buddy_already']						= 'El usuario ya est� en tus listas';
$lang['profilcp_buddy_ignore']						= 'No se puede agregar: el usuario te ha ignorado';
$lang['profilcp_buddy_you_admin']					= 'Un Administrador o Moderador no puede ignorar usuarios';
$lang['profilcp_buddy_admin']							= 'No puedes ignorar Administradores, Moderadores';
$lang['User_fields']											= 'Lista de campos de usuario';
$lang['Friend']														= 'Amigo';
$lang['Comp_LE']													= 'is less than';
$lang['Comp_EQ']													= 'is equal to';
$lang['Comp_NE']													= 'is different from';
$lang['Comp_GE']													= 'is greater than';
$lang['Comp_IN']													= 'includes';
$lang['Comp_NI']													= 'doesn\'t include';
$lang['Sort_none']												= 'Unsorted';
$lang['date_entry']												= 'YYYYMMDD';

// update profile (profilcp_profil.php)
$lang['profilcp_profil_shortcut']					= 'Perfil';
$lang['profilcp_profil_pagetitle']				= 'Editar Perfil';
$lang['profilcp_prefer_shortcut']					= 'Tu Perfil';
$lang['profilcp_prefer_pagetitle']				= 'Preferencias de Perfil';
$lang['profilcp_signature_shortcut']			= 'Firma';
$lang['profilcp_signature_pagetitle']			= 'Firma';
$lang['profilcp_avatar_shortcut']					= 'Avatar';
$lang['profilcp_avatar_pagetitle']				= 'Avatar';

// update profile : preferences - functions (mod_profile_control_panel.php)
$lang['Other']														= 'Otros';
$lang['Friend_only']											= 'S�lo Amigos';

// update profile : public informations : web info (mod_profile_control_public_web.php)
$lang['profilcp_profil_base_shortcut']		= 'Informaci�n P�blica';
$lang['Web_info']													= 'Informaci�n Web';

// update profile : public informations : real info (mod_profile_control_public_real.php)
$lang['Real_info']												= 'Informaci�n Personal';
$lang['Realname']													= 'Nombre Real';
$lang['Date_error']												= 'd�a %d, mes %d, a�o %d no es una fecha v�lida';

// update profile : public informations : messengers info (mod_profile_control_public_messengers.php)
$lang['Messangers']												= 'Mensajeros';

// update profile : public informations : contact info (mod_profile_control_public_contact.php)
$lang['Home_phone']												= 'Tel�fono de Casa';
$lang['Home_fax']													= 'FAX de Casa';
$lang['Work_phone']												= 'Tel�fono del trabajo';
$lang['Work_fax']													= 'FAX del trabajo';
$lang['Cellular']													= 'Tel�fono Celular';
$lang['Pager']														= 'Localizador';

// update profile : preferences - preferences panel ("Your profile")
$lang['Profile_control_panel']						= 'Opciones de Perfil';

// update profile : preferences - i18n panel (mod_profile_control_panel_international.php)
$lang['Profile_control_panel_i18n']				= 'Internacionalizaci�n';
$lang['summer_time']											= 'Est�s en una zona de horario de verano?';

// update profile : preferences - notification panel (mod_profile_control_panel_notification.php)
$lang['Profile_control_panel_notification']	= 'Notificaci�n';

// update profile : preferences - posting panel (mod_profile_control_panel_posting.php)
$lang['Profile_control_panel_posting']		= 'Publicaci�n';

// update profile : preferences - privacy panel (mod_profile_control_panel_privacy.php)
$lang['Profile_control_panel_privacy']		= 'Privacidad';
$lang['View_user']												= 'Mostrarme conectado';
$lang['Public_view_pm']										= 'Aceptar mensajes privados';
$lang['Public_view_website']							= 'Mostrar mi informaci�n Web';
$lang['Public_view_messengers']						= 'Mostrar mis Mensajeros';
$lang['Public_view_real_info']						= 'Mostrar mi Informaci�n Personal';

// update profile : preferences - reading panel (mod_profile_control_panel_reading.php)
$lang['Profile_control_panel_reading']		= 'Lectura';
$lang['Public_view_avatar']								= 'Mostrar Avatars';
$lang['Public_view_sig']									= 'Mostrar Firmas';
$lang['Public_view_img']									= 'Mostrar Im�genes';

// update profile : preferences - profile preferences
$lang['profile_prefer']										= 'Opciones de Perfil';

// update profile : preferences - system panel (mod_profile_control_panel_system.php)
$lang['Profile_control_panel_system']			= 'Sistema';
$lang['summer_time_set']									= 'Es horario de verano? (agregar +1 hora a la hora local)';
$lang['Forum_rules']											= 'Tema de reglas de registro';

// update profile : preferences - admin part (mod_profile_control_panel_admin.php)
$lang['profilcp_admin_shortcut']					= 'Administraci�n';
$lang['User_deleted']											= 'Usuario se ha borrado correctamente';
$lang['User_special']											= 'Campos especiales para Administradores';
$lang['User_special_explain']							= 'Estos campos no pueden ser modificados por usuarios. Aqu� puedes seleccionar su estado y otras opciones de los usuarios.';
$lang['User_status']											= 'Usuario est� activo';
$lang['User_allow_email']									= 'Puede enviar emails';
$lang['User_allow_pm']										= 'Puede enviar Mensajes Privados';
$lang['User_allow_website']								= 'Puede mostrar su informaci�n Web';
$lang['User_allow_messanger']							= 'Puede mostrar sus direcciones de mensajeros';
$lang['User_allow_real']									= 'Puede mostrar su informaci�n personal';
$lang['User_allow_sig']										= 'Puede mostrar su firma';
$lang['Rank_title']												= 'T�tulo del Rango';
$lang['User_delete']											= 'Borrar este usuario';
$lang['User_delete_explain']							= 'Click aqui para borrar este usuario; esto no puede deshacerse.';
$lang['No_assigned_rank']									= 'No se ha seleccionado un rango especial';
$lang['User_self_delete']									= 'Puedes borrar tu cuenta sin el Administrador del sistema';

// update profile : signature (profilcp_profile_signature.php)
$lang['profilcp_sig_preview']							= 'Vista previa de la firma';

// display profile (profilcp_public.php)
$lang['profilcp_public_shortcut']					= 'P�blico';
$lang['profilcp_public_pagetitle']				= 'Mostrar informaci�n p�blica';
$lang['profilcp_public_base_shortcut']		= 'Informaci�n';
$lang['profilcp_public_base_pagetitle']		= 'Informaci�n del Perfil';
$lang['profilcp_public_groups_shortcut']	= 'Grupos';
$lang['profilcp_public_groups_pagetitle']	= 'Grupos a los que pertenece este usuario';

// update profile : preferences - home panel (mod_profile_control_panel_home.php)
$lang['Profile_control_panel_home']				= 'Panel Principal del Perfil';
$lang['Profile_control_panel_home_buddy']	= 'Listas de Amigos';
$lang['Buddy_friend_display']							= 'Mostrar mi lista de amigos en el panel principal';
$lang['Buddy_ignore_display']							= 'Mostrar mi lista de ignorados en el panel principal';
$lang['Buddy_friend_of_display']					= 'Mostrar "Amigo de" en el panel principal';
$lang['Buddy_ignored_by_display']					= 'Mostrar "Ignorado por" en el panel principal';

$lang['Profile_control_panel_home_privmsg']	= 'Mensajes Privados';
$lang['Privmsgs_per_page']								= 'N�mero de Mensajes Privados mostrados por p�gina en el panel principal';

$lang['Profile_control_panel_home_wtopics']	= 'Temas Observados';
$lang['Watched_topics_per_page']					= 'N�mero de temas observados mostrados por p�gina en el panel principal';

// display profile : base info (profilcp_public_base.php)
$lang['Unavailable']											= 'No Disponible';
$lang['Last_visit']												= 'Ultima visita';
$lang['User_posts']												= 'Mensajes del usuario';
$lang['User_post_stats']									= '<b>%s</b> mensajes, %.2f%% del total, %.2f mensajes por d�a';
$lang['Most_active_topic']								= 'Tema m�s activo';
$lang['Most_active_topic_stat']						= '%s mensajes, %.2f%% del tema, %.2f%% del foro';
$lang['Most_active_forum']								= 'Foro m�s activo';
$lang['Most_active_forum_stat']						= '%s mensajes, %.2f%% del foro, %.2f%% del total';

// register (profilcp_register.php)
$lang['profilcp_register_shortcut']				= 'Registro';
$lang['profilcp_register_pagetitle']			= 'Informaci�n de Registro';
$lang['profilcp_email_title']							= 'Direcci�n de Email';
$lang['profilcp_email_confirm']						= 'Confirma tu direcci�n de Email';
$lang['anti_robotic']											= 'Imagen de Control';
$lang['anti_robotic_explain']							= 'Este control est� dise�ado para prevenir registros masivos por robot';
$lang['profilcp_password_explain']				= 'Debes confirmar la contrase�a actual si deseas cambiarla';
$lang['Agree_rules']											= 'Chequeando aqui, est�s declarando que est�s de acuerdo con los t�rminos';
$lang['profilcp_username_missing']				= 'Debes ingresar un nombre de usuario';
$lang['profilcp_email_not_matching']			= 'Los Emails ingresados no coinciden';
$lang['Robot_flood_control']							= 'La imagen de control no coincide con la que has ingresado';
$lang['Disagree_rules']										= 'Has especificado que no est�s de acuerdo con los t�rminos de uso de Rock Republik, as� que no podr�s registrarte.';

?>