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
	$action = isset($_GET['action']) && $_GET['action'] != 'forum' ? $_GET['action'] : false;
	$enabled = $modSettings['SCM_enabled'];
	if (!$disabled)
	{
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
		$_SESSION['SCM_last_update'] = $enabled = false;

	// Figure out which playlist we are going to play:
	$selected = !empty($modSettings['SCM_selected_playlist']) ? '_' . $modSettings['SCM_selected_playlist'] : '';
	$playlist = !empty($modSettings['SCM_playlist' . $selected]) ? $modSettings['SCM_playlist' . $selected] : (!empty($modSettings['SCM_playlist']) ? $modSettings['SCM_playlist'] : false);
	if (empty($playlist))
		$enabled = false;
	$enabled = !empty($enabled);

	// If mod is disabled, then abort if current user has been updated yet:
	if (!$enabled && !empty($_SESSION['SCM_last_update']) && !empty($modSettings['SCM_last_update']) && $_SESSION['SCM_last_update'] > $modSettings['SCM_last_update'])
		return;
	$_SESSION['SCM_last_update'] = time();

	// Decide on the CSS for the player:
	if (!$enabled || empty($playlist))
		$css = $boardurl . '/Themes/default/css/hide_player.css';
	elseif (!empty($modSettings['SCM_style']))
	{
		if ($modSettings['SCM_style'] == 'custom' && !empty($modSettings['SCM_custom_url']))
			$css = $modSettings['SCM_custom_url'];
		else
			$css = 'skins/' . (!isset($modSettings['SCM_style']) ? 'aquaBlue' : $modSettings['SCM_style']) . '/skin.css';
	}
	if (empty($css))
		$css = 'skins/aquaBlue/skin.css';
	if (substr($css, 0, 4) !== 'http')
		$css = $boardurl . '/SCM_Music_Player/' . $css;

	// Unserialize the playlist so that we can insert it:
	$unserialize = function_exists('safe_unserialize') ? 'safe_unserialize' : 'unserialize';
	$playlist = !empty($playlist) ? $unserialize($playlist) : array();
	$echo = array();
	foreach ($playlist as $title => $url)
		$echo[$title] = '{\'title\':' . JavaScriptEscape($title) . ',\'url\':' . JavaScriptEscape($url) . '}';
	$playlist = implode($echo, ',');

	// Save some decisions that need to be made in the code:
	$placement = !empty($modSettings['SCM_placement']) && $modSettings['SCM_placement'] == 'bottom' ? 'bottom' : 'top';
	$include_playlist = empty($_SESSION['SCM_last_update']) || empty($modSettings['SCM_last_update']) || $_SESSION['SCM_last_update'] < $modSettings['SCM_last_update'];
	$autoplay = !empty($modSettings['SCM_autoplay']);
	if ($autoplay && !empty($modSettings['SCM_autoplay_from']) && !empty($modSettings['SCM_autoplay_to']))
	{
		$hour = (int) date('G');
		if ($modSettings['SCM_autoplay_from'] > $modSettings['SCM_autoplay_to'])
			$autoplay = ($hour >= $modSettings['SCM_autoplay_from']) || ($hour <= $modSettings['SCM_autoplay_to']);
		else
			$autoplay = ($hour >= $modSettings['SCM_autoplay_from'] && $hour <= $modSettings['SCM_autoplay_to']);
	}

	// Insert the player into the HTML header:
	$context['html_headers'] .= '
	<!-- SCM Music Player http://scmplayer.net -->
	<script type="text/javascript" src="' . $boardurl . '/SCM_Music_Player/script.js" data-config="{
		\'skin\': \'' . $css . '\',
		\'volume\': ' . ((int) (!isset($modSettings['SCM_volume']) ? 50 : $modSettings['SCM_volume'])) . ',
		\'autoplay\': ' . ($autoplay ? 'true' : 'false'). ',
		\'shuffle\': ' . (!empty($modSettings['SCM_shuffle']) ? 'true' : 'false') . ',
		\'repeat\': ' . (!isset($modSettings['SCM_repeat']) ? '1' : $modSettings['SCM_repeat']) . ',
		\'placement\': \'' . $placement . '\',
		\'showplaylist\': ' . (!empty($modSettings['SCM_show_playlist']) ? 'true' : 'false') . ',
		\'playlist\': [' . $playlist . ']}">
	</script>';

	// Let's make any changes that the admin have made to the player:
	$context['html_headers'] .= '
	<script type="text/javascript">' . (!$enabled ? '
		SCM.stop();' : '') . ($enabled ? '
		SCM.placement("' . $placement . '");' .
		($include_playlist ? 'SCM.loadPlaylist([' . $playlist . ']);' : '') : '') . '
	</script>
	<!-- SCM Music Player script end -->';
}

?>