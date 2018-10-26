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
	global $txt;
	return;
	loadLanguage('SCMP');
	$areas['config']['areas']['scm_media_player'] = array(
		'label' => $txt['SCM_area'],
		'function' => 'SCM_Modify',
		'icon' => 'modifications.gif',
		'subsections' => array(
			'settings' => array($txt['language_settings']),
			'skins' => array($txt['SCM_skins']),
			'playlists' => array($txt['SCM_playlist']),
		),
	);		
}

function SCM_Permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$permissionList['membergroup']['listen_to_music'] = array(false, 'general', 'view_basic_info');
}

/*******************************************************************************/
// Functions required for mod configuration:
/*******************************************************************************/
function SCM_Modify($return_config = false)
{
	global $context, $settings, $sourcedir, $txt;

	// Create the tabs for the template .
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['SCM_area'],
		'description' => $txt['SCM_description'],
		'tabs' => array(
			'settings' => array(
			),
			'playlists' => array(
			),
			'skins' => array(
			),
		),
	);

	// Format: 'sub-action' => 'function'
	$subActions = array(
		'playlists'   => 'SCMP_playlists',
		'skins'  => 'SCMP_Skins',
		'settings' => 'SCMP_Settings',
	);

	// Figure out what we are loading for the user:
	loadTemplate('SCMP');
	require_once($sourcedir . '/ManageServer.php');
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'settings';
	$context['page_title'] = $txt['SCM_area'];
	return $subActions[$_REQUEST['sa']]($return_config);
}

function SCMP_Settings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings, $sourcedir;

	$config_vars = array(
		array('check', 'SCM_enabled'),
		array('permissions', 'listen_to_music'),
		'',
		array('int', 'SCM_volume'),
		array('check', 'SCM_autoplay'),
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

function SCMP_Skins($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings, $sourcedir;

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

	$config_vars = array(
		array('callback', 'SCM_style'),
	);
	if ($return_config)
		return $config_vars;

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

function SCMP_playlists($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings, $sourcedir;

	$context['html_headers'] .= '
	<link rel="stylesheet" type="text/css" href="' . $settings['theme_url'] . '/css/SCMP.css" />';

	$config_vars = array(
		array('callback', 'SCM_playlists'),
	);
	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

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
		$seralize = function_exists('safe_serialize') ? 'safe_serialize' : 'serialize';
		$_POST['SCM_playlist'] = empty($playlist) ? false : $seralize($playlist);
		$config_vars[] = array('text', 'SCM_playlist');
		$_POST['SCM_last_update'] = time();
		$config_vars[] = array('int', 'SCM_last_update');

		// Save the settings for this mod:
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=scm_media_player;sa=playlists');
	}

	// Get ready to show the settings to the user:
	$context['post_url'] = $scripturl . '?action=admin;area=scm_media_player;sa=playlists;save';
	$context['settings_title'] = $txt['SCM_style_title'];
	$context['sub_template'] = 'show_settings';
	prepareDBSettingContext($config_vars);
}

?>