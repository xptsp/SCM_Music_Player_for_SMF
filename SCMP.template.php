<?php
/********************************************************************************
* SCMP.template.php - Subs of the SCM Music Player for SMF mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

function template_SCMP_playlist()
{
	global $context, $txt, $modSettings, $boardurl, $forum_version, $scripturl;

	// Flag that will be used to add SMF 2.1 elements to the template:
	$smf21 = (substr($forum_version, 0, 7) == 'SMF 2.1');

	// Let's get this template started:
	echo '
	<div id="manage_boards">
		<form action="', $context['post_url'], ';save" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['SCM_playlist_title'], '</h3>
			</div>
			<div class="windowbg">
				<span class="topslice"><span></span></span>';
	if ($smf21)
		echo '
				<div class="sub_bar">
					<h3 class="subbg">
						', $txt['SCM_playlists'], '
					</h3>
				</div>';
	echo '
				<div class="content">
					<ul style="width:100%;">';

	// List through every playlist, printing its name and link to modify the playlist:
	$alternate = false;
	$count = count($context['SCMP_playlists']);
	foreach ($context['SCMP_playlists'] as $id => $playlist)
	{
		$alternate = !$alternate;
		echo '
						<li class="windowbg', $alternate ? '' : '2', '" style="padding-', ($context['right_to_left'] ? 'right' : 'left'), ': 5px;">
							<span class="floatleft">' . ($count > 1 ? '<input type="radio" name="SCM_selected_playlist" value="' . $id . '"' . ($modSettings['SCM_selected_playlist'] == $id ? ' checked="checked"' : '') . ' />' : ''), $playlist['name'], '</span>
							<span class="floatright">';
		if ($id > 0)
			echo '
								<span class="modify_boards"><a href="', $scripturl, '?action=admin;area=scm_media_player;sa=remove;list=', $id, '"', ($smf21 ? ' class="button"' : ''), '>', $txt['SCMP_remove'], '</a></span>';
		echo '
								<span class="modify_boards"><a href="', $scripturl, '?action=admin;area=scm_media_player;sa=edit;list=', $id, '"', ($smf21 ? ' class="button"' : ''), '>', $txt['mboards_modify'], '</a></span>
							</span>
							<br style="clear: right;" />
						</li>';
	}

	// Let's finish this template:
	echo '
					</ul>
					<div class="righttext">', ($count > 1 ? '
						<input type="submit" name="save" value="' . $txt['save'] . '" class="button_submit" />' : ''), '
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					</div>
				</div>
				<span class="botslice"><span></span></span>
			</div>
		</form>
	</div>';
}

function template_callback_SCM_songs()
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
	$counter = 0;
	foreach ($context['SCMP_songlist']['songs'] as $title => $url)
	{
		$counter++;
		echo '
				<dt id="title_', $counter, '">
					', $counter, ') <input type="text" name="SCM_title[]" value=', $title, ' size="25" class="input_text" />
				</dt>
				<dd id="url_', $counter, '">
					<input type="text" name="SCM_url[]" value="', $url, '" size="40" class="input_text" />
					<a href="javascript:void(0);" onclick="removeTrack(', $counter .'); return false;"><img src="', $boardurl, '/Themes/default/images/icons/quick_remove.gif"></a>
				</dd>';
	}

	// Add the button and Javascript to add a track to the playlist:
	echo '				<div id="insert_playlist"></div>
						<script type="text/javascript">
							var counter = ', ++$counter, ';
							function addTrack()
							{
								setOuterHTML(document.getElementById("insert_playlist"), \'<dt id="title_\' + counter + \'">\' + counter + \') <input type="text" name="SCM_title[]" size="25" class="input_text" /></dt><dd id="url_\' + counter + \'"><input type="text" name="SCM_url[]" size="40" class="input_text" /> <a href="javascript:void(0);" onclick="removeTrack(\' + counter + \'); return false;"><img src="' . $boardurl . '/Themes/default/images/icons/quick_remove.gif"></a></dd><div id="insert_playlist"></div>\');
								counter++;
							}
							function removeTrack(track)
							{
								setOuterHTML(document.getElementById("title_" + track), "");
								setOuterHTML(document.getElementById("url_" + track), "");
							}
							document.write(\'<dt><input type="submit" name="addtrack" id="addtrack" value="', $txt['SCM_add_track'], '" onclick="addTrack(); return false;" class="button_submit" /></dt>\');
							addTrack();
						</script>';
}

function template_callback_SCM_style()
{
	global $modSettings, $txt, $context;

	echo '
						<table style="margin-left:auto; margin-right: auto;">';
	foreach ($context['SCM_styles'] as $style)
		echo '
							<tr>
								<td><input type="radio" name="SCM_style"', (isset($modSettings['SCM_style']) && $modSettings['SCM_style'] == $style ? ' checked="checked"' : ''), ' value="', $style, (!empty($modSettings['SCM_enabled']) ? '" onclick="SCM.skin(\'skins/' . $style . '/skin.css\');" ' : '"'), '/>', $style, '</td>
								<td><iframe src="http://scmplayer.net/skinPreview.html#skins/', $style, '/skin.css" frameborder="0" width="400" height="25" style="padding: 1px;"></iframe></option></td>
							</tr>';
	echo '
							<tr>
								<td><input type="radio" name="SCM_style"', (isset($modSettings['SCM_style']) && $modSettings['SCM_style'] == '_custom_' ? ' checked="checked"' : ''), ' value="_custom_" />', $txt['SCM_custom_style'], '</td>
								<td><input type="text" name="SCM_custom_url" size="50"', (isset($modSettings['SCM_custom_url']) && $modSettings['SCM_style'] == '_custom_' ? 'value="' . $modSettings['SCM_custom_url'] . '"' : ''), ' /></td>
							</tr>
						</table>';
}

?>