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

	// Build the SCMP playlist array if not cached:
	if (($context['SCMP_playlists'] = cache_get_data('SCMP_playlists', 86400)) == null)
	{
		// Gather everything we need for the "default" playlist:
		$func = function_exists('safe_unserialize') ? 'safe_unserialize' : 'unserialize';
		$songs = !empty($modSettings['SCM_playlist']) ? $modSettings['SCM_playlist'] : '';
		$songs = @$func($songs);
		if (empty($songs) || !is_array($songs))
			$songs = array();
		$context['SCMP_playlists'] = array(
			0 => array(
				'id' => 0,
				'name' => !empty($songs['__NAME__']) ? $songs['__NAME__'] : $txt['SCM_playlists_Default'],
			),
		);
		unset($songs['__NAME__']);
		$context['SCMP_playlists'][0]['songs'] = $songs;	

		// Gather up any other playlists available in the $modSettings array:
		foreach ($modSettings as $variable => $value)
		{
			if (preg_match('~SCM_playlist_([\d]+)~i', $variable, $matches) && !empty($value))
			{
				$songs = @$func($value);
				if (empty($songs) || !is_array($songs))
					$songs = array();
				$id = $matches[1];
				$context['SCMP_playlists'][$id] = array(
					'id' => $id,
					'name' => !empty($songs['__NAME__']) ? $songs['__NAME__'] : sprintf($txt['SCMP_playlist_which'], $id),
				);
				unset($songs['__NAME__']);
				$context['SCMP_playlists'][$id]['songs'] = $songs;
			}
		}
		asort($context['SCMP_playlists']);
		
		// Save the playlist information in the SMF cache:
		cache_put_data('SCMP_playlists', $context['SCMP_playlists'], 86400);
	}

	// Does the specified playlist exist?  If not, return to caller:
	$selected = !empty($modSettings['SCM_selected_playlist']) ? (int) $modSettings['SCM_selected_playlist'] : 0;
	if (empty($context['SCMP_playlists'][$selected]['songs']))
		return;
		
	// Should we revise the playlist currently playlist?
	$_SESSION['SCMP_selected'] = $selected;

	// Do we need to update the current player?  If not, return to caller:
	if (!empty($_SESSION['SCM_last_update']) && !empty($modSettings['SCM_last_update']) && $_SESSION['SCM_last_update'] < $modSettings['SCM_last_update'])
		return;
	$_SESSION['SCM_last_update'] = time();

	// Build the playlist for the Javascript code:
	$echo = array();
	foreach ($context['SCMP_playlists'][$selected]['songs'] as $title => $url)
		$echo[$title] = '{\'title\':' . JavaScriptEscape($title) . ',\'url\':' . JavaScriptEscape($url) . '}';
	$playlist = implode($echo, ',');

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

	// Save some decisions that need to be made in the code:
	$placement = !empty($modSettings['SCM_placement']) && $modSettings['SCM_placement'] == 'bottom' ? 'bottom' : 'top';
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
		\'autoplay\': ' . ($enabled && $autoplay ? 'true' : 'false'). ',
		\'shuffle\': ' . (!empty($modSettings['SCM_shuffle']) ? 'true' : 'false') . ',
		\'repeat\': ' . (!isset($modSettings['SCM_repeat']) ? '1' : $modSettings['SCM_repeat']) . ',
		\'placement\': \'' . $placement . '\',
		\'showplaylist\': ' . (!empty($modSettings['SCM_show_playlist']) ? 'true' : 'false') . ',
		\'playlist\': [' . $playlist . ']}">
	</script>';

	// Let's make any changes that the admin have made to the player:
	$context['html_headers'] .= '
	<script type="text/javascript">';

	// Did the placement of the SCMP player change?
	if (isset($_SESSION['SCMP_placement']) && $_SESSION['SCMP_placement'] <> $placement)
		$context['html_headers'] .= '
		SCM.placement("' . $placement . '");';
	$_SESSION['SCMP_placement'] = $placement;

	// Did the selected/current playlist change?
	if (isset($_SESSION['SCMP_selected']) && $_SESSION['SCMP_selected'] <> $selected)
		$context['html_headers'] .= '
		SCM.loadPlaylist([' . $playlist . ']);';
	$_SESSION['SCMP_selected'] = $selected;

	// Is the SCMP player disabled?
	$context['html_headers'] .= '
		if (SCM.isPlay()) {
			SCM.' . ($enabled ? 'start' : 'stop') . '();
		}';

	// Finish the header stuff for this mod:
	$context['html_headers'] .= '
	</script>
	<!-- SCM Music Player script end -->';
}

?>