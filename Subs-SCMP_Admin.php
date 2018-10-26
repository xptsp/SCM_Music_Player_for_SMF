<?php
/********************************************************************************
* Subs-SCMP_Admin.php - Admin subs of the SCM Music Player for SMF mod
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
function SCM_Admin(&$areas)
{
	global $txt, $context;
	loadLanguage('SCMP');
	$tmp = $context['SCMP_decision'] = isset($_REQUEST['area']) && $_REQUEST['area'] == 'scm_media_player' && isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'edit';
	$areas['config']['areas']['scm_media_player'] = array(
		'label' => $txt['SCM_area'],
		'function' => 'SCM_Config',
		'icon' => 'modifications.gif',
		'subsections' => array(
			'settings' => array($txt['language_settings']),
			'skins' => array($txt['SCM_skins']),
			'playlists' => array($txt['SCM_playlists']),
			($tmp ? 'edit' : 'new') => array($txt[$tmp ? 'SCM_edit_playlist' : 'SCM_new_playlist']),
		),
	);
}

function SCM_Permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$permissionList['membergroup']['listen_to_music'] = array(false, 'general', 'view_basic_info');
}

/*******************************************************************************/
// Function required for mod configuration:
/*******************************************************************************/
function SCM_Config()
{
	global $context, $settings, $txt, $sourcedir, $modSettings;

	// Create the tabs for the template .
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['SCM_area'],
		'description' => $txt['SCM_description'],
		'tabs' => array(
			'settings' => array(
			),
			'skins' => array(
			),
			'playlists' => array(
			),
			'edit' => array(
			),
			'new' => array(
			),
		),
	);
	unset($context[$context['admin_menu_name']]['tab_data']['tabs'][$context['SCMP_decision'] ? 'new' : 'edit']);

	// Format: 'sub-action' => 'function'
	$subActions = array(
		'playlists' => 'SCMP_Lists',
		'skins' => 'SCMP_Skins',
		'settings' => 'SCMP_Settings',
		'new' => 'SCMP_Edit',
		'edit' => 'SCMP_Edit',
		'remove' => 'SCMP_Remove',
	);

	// Figure out what we are loading for the user:
	loadTemplate('SCMP');
	require_once($sourcedir . '/ManageServer.php');
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'settings';
	$context['page_title'] = $txt['SCM_area'];
	return $subActions[$_REQUEST['sa']]();
}

/*******************************************************************************/
// Function handling changing mod settings:
/*******************************************************************************/
function SCMP_Settings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	$config_vars = array(
		array('check', 'SCM_enabled'),
		array('permissions', 'listen_to_music'),
		'',
		array('check', 'SCM_autoplay'),
		array('int', 'SCM_autoplay_from'),
		array('int', 'SCM_autoplay_to'),
		'',
		array('int', 'SCM_volume'),
		array('check', 'SCM_shuffle'),
		array('select', 'SCM_repeat', array(0 => $txt['SCM_repeat0'], 1 => $txt['SCM_repeat1'], 2 => $txt['SCM_repeat2'])),
		array('select', 'SCM_placement', array('top' => $txt['SCM_top'], 'bottom' => $txt['SCM_bottom'])),
		array('check', 'SCM_show_playlist'),
		'',
		array('check', 'SCM_hide_boardindex', 'postinput' => $txt['SCM_boardindex']),
		array('check', 'SCM_hide_messageindex', 'postinput' => $txt['message_index']),
		array('check', 'SCM_hide_posts', 'postinput' => $txt['posts']),
		array('check', 'SCM_hide_profile', 'postinput' => $txt['profile']),
		array('check', 'SCM_hide_pm', 'postinput' => $txt['pm_short']),
		array('check', 'SCM_hide_members', 'postinput' => $txt['members']),
		array('check', 'SCM_hide_admin', 'postinput' => $txt['admin']),
		array('check', 'SCM_hide_calendar', 'postinput' => $txt['calendar']),
		array('check', 'SCM_hide_moderate', 'postinput' => $txt['moderate']),
	);
	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');

		// Save the settings for this mod:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=scm_media_player');
	}

	// Make sure certain settings are defaulted if not set:
	$modSettings['SCM_style'] = !empty($modSettings['SCM_style']) ? $modSettings['SCM_style'] : 'aquaBlue';
	$modSettings['SCM_volume'] = !isset($modSettings['SCM_volume']) ? 50 : $modSettings['SCM_volume'];

	// Get ready to show the settings to the user:
	$context['post_url'] = $scripturl . '?action=admin;area=scm_media_player;sa=settings;save';
	$context['settings_title'] = $txt['SCM_title'];
	$context['sub_template'] = 'show_settings';
	prepareDBSettingContext($config_vars);
}

/*******************************************************************************/
// Function handling changing the CSS skin used by the mod:
/*******************************************************************************/
function SCMP_Skins()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	$styles = array(
		'aquaBlue', 'aquaGreen', 'aquaOrange', 'aquaPink', 'aquaPurple', 'aquaPurpleReal',
		'black', 'blue', 'cyber', 'green', 'orange', 'pink',  'purple', 'tunes',
		'scmBlue', 'scmGreen', 'scmOrange', 'scmPurple', 'scmRed', 'simpleBlack',
		'simpleBlue', 'simpleGreen', 'simpleOrange', 'simplePurple', 'simpleRed',
	);
	$list = array();
	foreach ($styles as $style)
		$list[$style] = $style;
	$context['SCM_styles'] = $list;

	// Use the show_settings template with our callback function:
	$config_vars = array(
		array('callback', 'SCM_style'),
	);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Get the new style for the player:
		$_POST['SCM_custom_url'] = !empty($_POST['SCM_custom_url']) ? $_POST['SCM_custom_url'] : '';
		$config_vars[] = array('text', 'SCM_custom_url');
		$_POST['SCM_style'] = !empty($_POST['SCM_style']) ? $_POST['SCM_style'] : 'aquaBlue';
		$_POST['SCM_style'] = ($_POST['SCM_style'] == '_custom_' && !empty($_POST['SCM_custom_url']) ? '_custom' : (isset($list[$_POST['SCM_style']]) ? $_POST['SCM_style'] : 'aquaBlue'));
		$config_vars[] = array('text', 'SCM_style');
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');

		// Save the settings for this mod:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=scm_media_player;sa=skins');
	}

	// Get ready to show the settings to the user:
	$context['post_url'] = $scripturl . '?action=admin;area=scm_media_player;sa=skins;save';
	$context['settings_title'] = $txt['SCM_style_title'];
	$context['sub_template'] = 'show_settings';
	prepareDBSettingContext($config_vars);
}

/*******************************************************************************/
// Function handling listing all stored playlists:
/*******************************************************************************/
function SCMP_Lists()
{
	global $modSettings, $context, $txt, $scripturl;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Record which playlist is the forum default:
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');
		$_POST['SCM_selected_playlist'] = !empty($_POST['SCM_selected_playlist']) ? $_POST['SCM_selected_playlist'] : 0;
		$config_vars[] = array('int', 'SCM_selected_playlist');

		// Save the settings for this mod:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=scm_media_player;sa=playlists');
	}

	// Define the template stuff we need:
	loadLanguage('ManageBoards');
	$context['page_title' ] = $txt['SCM_playlists'];
	$context['sub_template'] = 'SCMP_playlist';
	$context['post_url'] = $scripturl . '?action=admin;area=scm_media_player;sa=' . $_REQUEST['sa'];
	$modSettings['SCM_selected_playlist'] = empty($modSettings['SCM_selected_playlist']) ? 0 : $modSettings['SCM_selected_playlist'];
}

/*******************************************************************************/
// Function handling editing the playlist:
/*******************************************************************************/
function SCMP_Edit()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	if ($_REQUEST['sa'] == 'new')
	{
		// Create a new playlist entry:
		$list = max(array_keys($context['SCMP_playlists'])) + 1;
		$context['SCMP_playlists'][$list] = array(
			'id' => $list,
			'name' => sprintf($txt['SCMP_playlist_which'], $list),
			'songs' => array()
		);
	}
	else
	{
		// Attempting to edit a playlist that doesn't exist?
		$list = (int) (!empty($_GET['list']) ? $_GET['list'] : false);
		if (!isset($context['SCMP_playlists'][$list]) && !isset($_GET['save']))
			fatal_lang_error('SCM_playlist_nonexistant', false);
	}
		

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Get the new playlist ready to store in the forum settings:
		$playlist = 'SCM_playlist' . (!empty($list) ? '_' . ((int) $list) : '');
		$songs = array(
			'__NAME__' => $_POST['SCM_playlists_name'],
		);
		foreach ($_POST['SCM_title'] as $id => $title)
		{
			if (empty($_POST['SCM_url'][$id]) || empty($_POST['SCM_title'][$id]))
				unset($_POST['SCM_url'][$id], $_POST['SCM_title'][$id]);
			else
			{
				$data = $_POST['SCM_url'][$id];
				$songs[$title] = (strpos($data, 'http://') === false && strpos($data, 'https://') === false && strpos($data, '//') === false ? 'http://' : '') . $data;
			}
		}
		$serialize = function_exists('safe_serialize') ? 'safe_serialize' : 'serialize';
		$_POST[$playlist] = empty($songs) ? false : $serialize($songs);
		$config_vars[] = array('text', $playlist);
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');

		// Update the cached playlists with the new information:
		$context['SCMP_playlists'][$list] = array(
			'id' => $list,
			'name' => $songs['__NAME__'],
		);
		unset($songs['__NAME__']);
		$context['SCMP_playlists'][$list]['songs'] = $songs;
		cache_put_data('SCMP_playlists', $context['SCMP_playlists'], 86400);

		// Save the settings for this mod before redirecting to playlists:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=scm_media_player;sa=playlists');
	}

	// Use the show_settings template with our callback function:
	$config_vars = array(
		array('text', 'SCM_playlists_name', 'size' => 40),
		'',
		array('callback', 'SCM_songs'),
	);

	// Get the playlist we are editing:
	$context['SCMP_songlist'] = &$context['SCMP_playlists'][$list];
	$modSettings['SCM_playlists_name'] = $context['SCMP_songlist']['name'];

	// Get ready to show the settings to the user:
	$context['post_url'] = $scripturl . '?action=admin;area=scm_media_player;sa=' . $_REQUEST['sa'] . ($_REQUEST['sa'] == 'edit' ? ';list=' . $list : '') . ';save';
	$context['settings_title'] = $txt['SCM_playlist'] . ': ' . $context['SCMP_songlist']['name'];
	$context['sub_template'] = 'show_settings';
	prepareDBSettingContext($config_vars);
}

/*******************************************************************************/
// Function handling delete a playlist:
/*******************************************************************************/
function SCMP_Remove()
{
	global $context, $modSettings, $smcFunc;

	// Attempting to access a playlist that doesn't exist or can't be deleted?
	$list = (int) (!empty($_GET['list']) ? $_GET['list'] : false);
	if ($list === false || $list === 0)
		fatal_lang_error('SCM_cannot_remove_default', false);
	if (!isset($context['SCMP_playlists'][$list]))
		fatal_lang_error('SCM_playlist_nonexistant', false);

	// We need to delete the setting from the database:
	unset($modSettings['SCM_playlist_' . $list]);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}settings
		WHERE variable = {string:variable}',
		array(
			'variable' => 'SCM_playlist_' . $list,
		)
	);

	// Save the revised playlist array in the SMF cache:
	unset($context['SCMP_playlists'][$list]);
	cache_put_data('SCMP_playlists', $context['SCMP_playlists'], 86400);

	// If this playlist is selected, change back to default playlist:
	if (!empty($modSettings['SCM_selected_playlist']) && $modSettings['SCM_selected_playlist'] == $list)
	{
		$_POST['SCM_selected_playlist'] = 0;
		$config_vars[] = array('int', 'SCM_selected_playlist');
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');
	}

	// Record changes to the database and/or cache:
	$_POST['SCM_last_update'] = time();
	$config_vars[] = array('int', 'SCM_last_update');
	saveDBSettings($config_vars);
	redirectexit('action=admin;area=scm_media_player;sa=playlists');
}

?>