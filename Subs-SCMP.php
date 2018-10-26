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
	global $context, $modSettings, $txt, $boardurl;

	// If mod is disabled, then abort if current user has been updated yet:
	if (empty($modSettings['SCM_enabled']) || empty($modSettings['SCM_playlist']))
		if (!empty($_SESSION['SCM_last_update']) && !empty($modSettings['SCM_last_update']) && $_SESSION['SCM_last_update'] > $modSettings['SCM_last_update'])
			return;

	// Decide on the CSS for the player:
	if (empty($modSettings['SCM_enabled']) || empty($modSettings['SCM_playlist']))
		$css = $boardurl . '/Themes/default/css/hide_player.css';
	elseif (!empty($modSettings['SCM_style']) && SCM_styles($modSettings['SCM_style']))
		$css = 'skins/' . (!isset($modSettings['SCM_style']) ? 'aquaBlue' : $modSettings['SCM_style']) . '/skin.css';
	elseif (!empty($modSettings['SCM_custom_url']))
		$css = $modSettings['SCM_custom_url'];
	else
		$css = 'skins/aquaBlue/skin.css';

	// Unserialize the playlist so that we can insert it:
	$playlist = safe_unserialize($modSettings['SCM_playlist']);
	$echo = array();
	foreach ($playlist as $title => $url)
		$echo[$title] = '{\'title\':' . JavaScriptEscape($title) . ',\'url\':' . JavaScriptEscape($url) . '}';

	// Insert the player into the HTML header:
	$context['html_headers'] .= '
	<!-- SCM Music Player http://scmplayer.net -->
	<script type="text/javascript" src="http://scmplayer.net/script.js" data-config="{
		\'skin\': \'' . $css . '\',
		\'volume\': ' . (int) (!isset($modSettings['SCM_volume']) ? 50 : $modSettings['SCM_volume']) . ',
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

	// Record the current time so that we know if the admin have changed the options since....
	$_SESSION['SCM_last_update'] = time();
}

function SCM_Admin(&$areas)
{
	global $txt;
	loadLanguage('SCMP');
	$areas['config']['areas']['modsettings']['subsections']['scm_music_player'] = array($txt['SCM_section']);
}

function SCM_Area(&$areas)
{
	$areas['scm_music_player'] = 'SCM_Modify';
}

/*******************************************************************************/
// Functions required for mod configuration:
/*******************************************************************************/
function SCM_Modify($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings;

	$config_vars = array(
		array('check', 'SCM_enabled'),
		array('int', 'SCM_volume'),
		array('check', 'SCM_autoplay'),
		array('check', 'SCM_shuffle'),
		array('select', 'SCM_repeat', array(0 => $txt['SCM_repeat0'], 1 => $txt['SCM_repeat1'], 2 => $txt['SCM_repeat2'])),
		array('select', 'SCM_placement', array('top' => $txt['SCM_top'], 'bottom' => $txt['SCM_bottom'])),
		array('check', 'SCM_show_playlist'),
		//'',
		array('title', 'subtemplate_SCM_track_title'),
		array('callback', 'SCM_playlist'),
		//'',
		array('title', 'SCM_style_title'),
		array('callback', 'SCM_style'),
	);
	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		//checkSession();

		// Get the new playlist ready to store in the forum settings:
		$playlist = array();
		foreach ($_POST['SCM_title'] as $id => $title)
		{
			if (empty($_POST['SCM_url'][$id]) || empty($_POST['SCM_title'][$id]))
				unset($_POST['SCM_url'][$id], $_POST['SCM_title'][$id]);
			else
			{
				$data = $_POST['SCM_url'][$id];
				$playlist[$title] = (strpos($data, 'http://') !== 0 && strpos($data, 'https://') !== 0 ? 'http://' : '') . $data;
			}
		}
		$config_vars[] = array('text', 'SCM_playlist');
		$_POST['SCM_playlist'] = empty($playlist) ? false : safe_serialize($playlist);

		// Get the new style for the player:
		$_POST['SCM_custom_url'] = !empty($_POST['SCM_custom_url']) ? $_POST['SCM_custom_url'] : false;
		$config_vars[] = array('text', 'SCM_custom_url');
		$_POST['SCM_style'] = !empty($_POST['SCM_style']) ? $_POST['SCM_style'] : 'aquaBlue';
		$_POST['SCM_style'] = ($_POST['SCM_style'] == '_custom_' && !empty($_POST['SCM_custom_url']) ? '_custom' : SCM_styles($_POST['SCM_style']));
		$config_vars[] = array('text', 'SCM_style');
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');

		// Save the settings for this mod:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=modsettings;sa=scm_music_player');
	}

	// Get ready to show the settings to the user:
	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=scm_music_player';
	$context['settings_title'] = $txt['SCM_title'];
	prepareDBSettingContext($config_vars);
}

function SCM_styles($test = false)
{
	$styles = array(
		'aquaBlue', 'aquaGreen', 'aquaOrange', 'aquaPink', 'aquaPurple', 'aquaPurpleReal', 'black', 'blue',
		'cyber', 'green', 'orange', 'pink',  'purple', 'scmBlue', 'scmGreen', 'scmOrange', 'scmPurple', 'scmRed',
		'simpleBlack', 'simpleBlue', 'simpleGreen', 'simpleOrange', 'simplePurple', 'simpleRed', 'tunes',
	);
	$list = array();
	foreach ($styles as $style)
		$list[$style] = $style;
	return ($test ? ($test == '_custom_' ? $test : (isset($list[$test]) ? $test : false)) : $list);
}

/*******************************************************************************/
// Template callback functions:
/*******************************************************************************/
function template_callback_SCM_playlist()
{
	global $context, $txt, $modSettings, $boardurl;

	// List the tracks in the playlist:
	echo '
						<dt>
							<strong><u>', $txt['SCM_music_title'], '</u></strong>
						</dt>
						<dd>
							<strong><u>', $txt['SCM_music_URL'], '</u></strong>
						</dd>';
	if (!empty($modSettings['SCM_playlist']))
	{
		$playlist = safe_unserialize($modSettings['SCM_playlist']);
		$counter = 0;
		if (count($playlist))
		{
			$playlist[''] = '';
			foreach ($playlist as $title => $url)
			{
				$counter++;
				echo '
						<dt id="title_', $counter, '">
							<input type="text" name="SCM_title[]" value=', JavaScriptEscape($title), ' size="25" class="input_text" />
						</dt>
						<dd id="url_', $counter, '">
							<input type="text" name="SCM_url[]" value=', JavaScriptEscape($url), ' size="40" class="input_text" />', ($counter > 1 ? '
							<a href="javascript:void(0);" onclick="removeTrack(' . $counter .'); return false;"><img src="' . $boardurl . '/Themes/default/images/icons/quick_remove.gif"></a>' : ''), '
						</dd>';
			}
		}
	}

	// Add the button and Javascript to add a track to the playlist:
	echo '				<div id="insert_playlist"></div>
						<script type="text/javascript">
							var counter = ', ++$counter, ';
							function addTrack()
							{
								setOuterHTML(document.getElementById("insert_playlist"), \'<dt id="title_\' + counter + \'"><input type="text" name="SCM_title[]" size="25" class="input_text" /></dt><dd id="url_\' + counter + \'"><input type="text" name="SCM_url[]" size="40" class="input_text" /> <a href="javascript:void(0);" onclick="removeTrack(\' + counter + \'); return false;"><img src="' . $boardurl . '/Themes/default/images/icons/quick_remove.gif"></a></dd><div id="insert_playlist"></div>\');
								counter++;
							}
							function removeTrack(track)
							{
								setOuterHTML(document.getElementById("title_" + track), "");
								setOuterHTML(document.getElementById("url_" + track), "");
							}
							document.write(\'<dt><input type="submit" name="addtrack" id="addtrack" value="', $txt['SCM_add_track'], '" onclick="addTrack(); return false;" class="button_submit" /></dt>\');
						</script>';
}

function template_callback_SCM_style()
{
	global $modSettings, $txt;
	echo '
						<center><table>';
	foreach (SCM_styles() as $style)
		echo '
							<tr>
								<td><input type="radio" name="SCM_style"', ($modSettings['SCM_style'] == $style ? ' checked="checked"' : ''), ' value="', $style, (!empty($modSettings['SCM_enabled']) ? '" onclick="SCM.skin(\'skins/' . $style . '/skin.css\');" ' : '"'), '/>', $style, '</td>
								<td><iframe src="http://scmplayer.net/skinPreview.html#skins/', $style, '/skin.css" frameborder="0" width="400" height="25" style="padding: 1px;"></iframe></option></td>
							</tr>';
	echo '
							<tr>
								<td><input type="radio" name="SCM_style"', ($modSettings['SCM_style'] == '_custom_' ? ' checked="checked"' : ''), ' value="_custom_" />', $txt['SCM_custom_style'], '</td>
								<td><input type="text" name="SCM_custom_url" size="50"', (isset($modSettings['SCM_custom_url']) && $modSettings['SCM_style'] == '_custom_' ? 'value="' . $modSettings['SCM_custom_url'] . '"' : ''), ' /></td>
							</tr>
						</table></center>';
}

?>