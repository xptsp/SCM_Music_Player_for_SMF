<?php
/********************************************************************************
* Subs-SCMP.php - Subs of the SCM Music Player for SMF mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

/*******************************************************************************/
// Hook functions:
/*******************************************************************************/
function SCM_Load()
{
	global $context, $modSettings, $txt, $boardurl, $user_info, $board, $topic;

	// Disable the player if we are not allowed to listen to Site Music:
	$disabled = !allowedTo('listen_to_music');
	if (!$disabled)
	{
		$action = isset($_GET['action']) && $_GET['action'] != 'forum' ? $_GET['action'] : false;
		$disabled |= (!empty($modSettings['SCM_hide_boardindex']) && empty($action) && empty($board) && empty($topic));
		$disabled |= (!empty($modSettings['SCM_hide_messageindex']) && empty($action) && !empty($board) && empty($topic));
		$disabled |= (!empty($modSettings['SCM_hide_posts']) && !empty($topic));
		$disabled |= (!empty($modSettings['SCM_hide_profile']) && $action == 'profile');
		$disabled |= (!empty($modSettings['SCM_hide_pm']) && $action == 'pm');
		$disabled |= (!empty($modSettings['SCM_hide_members']) && $action == 'members');
		$disabled |= (!empty($modSettings['SCM_hide_admin']) && $action == 'admin');
		$disabled |= (!empty($modSettings['SCM_hide_calendar']) && $action == 'calendar');
		$disabled |= (!empty($modSettings['SCM_hide_moderate']) && $action == 'moderate');
	}	
	if ($disabled)
		$_SESSION['SCM_last_update'] = $modSettings['SCM_enabled'] = false;

	// If mod is disabled, then abort if current user has been updated yet:
	if (empty($modSettings['SCM_enabled']) || empty($modSettings['SCM_playlist']))
		if (!empty($_SESSION['SCM_last_update']) && !empty($modSettings['SCM_last_update']) && $_SESSION['SCM_last_update'] > $modSettings['SCM_last_update'])
			return;

	// Figure out which playlist we are going to play:
	$_SESSION['SCM_last_update'] = time();
	$selected = !empty($modSettings['SCM_selected_playlist']) ? $modSettings['SCM_selected_playlist'] : '';
	$playlist = !empty($modSettings['SCM_playlist_' . $selected]) ? $modSettings['SCM_playlist_' . $selected] : (!empty($modSettings['SCM_playlist']) ? $modSettings['SCM_playlist'] : false);

	// Decide on the CSS for the player:
	if (empty($modSettings['SCM_enabled']) || empty($modSettings['SCM_playlist']))
		$css = $boardurl . '/Themes/default/css/hide_player.css';
	elseif (!empty($modSettings['SCM_style']) && !empty($modSettings['SCM_style']))
		$css = 'skins/' . (!isset($modSettings['SCM_style']) ? 'aquaBlue' : $modSettings['SCM_style']) . '/skin.css';
	elseif (!empty($modSettings['SCM_custom_url']))
		$css = $modSettings['SCM_custom_url'];
	else
		$css = 'skins/aquaBlue/skin.css';

	// Unserialize the playlist so that we can insert it:
	$playlist = !empty($playlist) ? safe_unserialize($playlist) : array();
	$echo = array();
	foreach ($playlist as $title => $url)
		$echo[$title] = '{\'title\':' . JavaScriptEscape($title) . ',\'url\':' . JavaScriptEscape($url) . '}';

	// Insert the player into the HTML header:
	$context['html_headers'] .= '
	<!-- SCM Music Player http://scmplayer.net -->
	<script type="text/javascript" src="http://scmplayer.net/script.js" data-config="{
		\'skin\': \'' . $css . '\',
		\'volume\': ' . ((int) (!isset($modSettings['SCM_volume']) ? 50 : $modSettings['SCM_volume'])) . ',
		\'autoplay\': ' . (!empty($modSettings['SCM_enabled']) && !empty($modSettings['SCM_playlist']) && !empty($modSettings['SCM_autoplay']) ? 'true' : 'false') . ',
		\'shuffle\': ' . (!empty($modSettings['SCM_shuffle']) ? 'true' : 'false') . ',
		\'repeat\': ' . (!isset($modSettings['SCM_repeat']) ? '1' : $modSettings['SCM_repeat']) . ',
		\'placement\': \'' . (isset($modSettings['SCM_placement']) && $modSettings['SCM_placement'] == 'bottom' ? 'bottom' : 'top') . '\',
		\'showplaylist\': ' . (!empty($modSettings['SCM_show_playlist']) ? 'true' : 'false') . ',
		\'playlist\': [' . implode($echo, ',') . ']}">
	</script>';

	// Let's make any changes that the admin have made to the player:
	$context['html_headers'] .= '
	<script type="text/javascript">
		SCM.skin("' . $css . '");
		SCM.placement("' . (isset($modSettings['SCM_placement']) && $modSettings['SCM_placement'] == 'bottom' ? 'bottom' : 'top') . '");';
	if (!empty($_SESSION['SCM_last_update']) && !empty($modSettings['SCM_last_update']) && $_SESSION['SCM_last_update'] < $modSettings['SCM_last_update'])
		$context['html_headers'] .= '
		SCM.loadPlaylist([' . implode($echo, ',') . ']);';
	if (empty($modSettings['SCM_enabled']))
		$context['html_headers'] .= '
		SCM.stop();';
	$context['html_headers'] .= '
	</script>
	<!-- SCM Music Player script end -->';
}

?>