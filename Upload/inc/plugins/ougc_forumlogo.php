<?php

/***************************************************************************
 *
 *	OUGC Forum Logo plugin (/inc/plugins/ougc_forumlogo.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Use header images on a by forum basis.
 *
 ***************************************************************************
 
****************************************************************************
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
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run our hook.
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_formcontainer_output_row', 'ougc_forumlogo_edit');
	$plugins->add_hook('admin_forum_management_edit_commit', 'ougc_forumlogo_update');
}
else
{
	$plugins->add_hook('forumdisplay_start','ougc_forumlogo_run');
	$plugins->add_hook('showthread_start','ougc_forumlogo_run');
	$plugins->add_hook('newthread_start','ougc_forumlogo_run');
	$plugins->add_hook('newreply_start','ougc_forumlogo_run');
	$plugins->add_hook('editpost_start','ougc_forumlogo_run');
}

//Necessary plugin information for the ACP plugin manager.
function ougc_forumlogo_info()
{
	global $lang;
    isset($lang->ougc_forumlogo) or $lang->load('ougc_forumlogo');

	return array(
		'name'			=> 'OUGC Forum Logo',
		'description'	=> $lang->ougc_forumlogo_desc,
		'website'		=> 'http://mods.mybb.com/view/ougc-forum-logo',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8.1',
		'versioncode'	=> '1801',
		'compatibility'	=> '16*,18*',
		'guid'			=> 'e484d7a66b9c41db8c6617469443642b'
	);
}

// _activate() routine
function ougc_forumlogo_activate()
{
	global $cache;

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_forumlogo_info();

	if(!isset($plugins['forumlogo']))
	{
		$plugins['forumlogo'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['forumlogo'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _install() routine
function ougc_forumlogo_install()
{
	global $db;

	if(!$db->field_exists('ougc_logo', 'forums'))
	{
		global $cache;

		$db->add_column('forums', 'ougc_logo', "varchar(255) NOT NULL DEFAULT ''");
		$cache->update_forums();
	}
}

// _is_installed() routine
function ougc_forumlogo_is_installed()
{
	global $db;

	return $db->field_exists('ougc_logo', 'forums');
}

// _uninstall() routine
function ougc_forumlogo_uninstall()
{
	global $db, $cache;

	if($db->field_exists('ougc_logo', 'forums'))
	{
		$db->drop_column('forums', 'ougc_logo');
		$cache->update_forums();
	}

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['forumlogo']))
	{
		unset($plugins['forumlogo']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	elseif(method_exists($cache, 'delete'))
	{
		$cache->delete('ougc_plugins');
	}
	else
	{
		global $db;

		!is_object($cache->handler) or $cache->handler->delete('ougc_plugins');
		$db->delete_query('datacache', 'title=\'ougc_plugins\'');
	}
}

// Add Textbox in Forum Management 
function ougc_forumlogo_edit(&$args)
{
	global $lang, $mybb;

	if($args['title'] == $lang->style_options && $lang->style_options && (string)$mybb->input['module'] == 'forum-management' && (string)$mybb->input['action'] == 'edit')
	{
		global $form, $forum_data;
		$lang->load('ougc_forumlogo');

		$args['content'] .= '<div class="forum_settings_bit">'.$lang->ougc_forumlogo_title.'<br />'.$form->generate_text_box('ougc_logo', isset($forum_data['ougc_logo']) ? $forum_data['ougc_logo'] : '').'</div>';
	}
}

// Save Forum Data
function ougc_forumlogo_update()
{
	global $db, $mybb, $fid, $cache;

	$logo = (string)$mybb->input['ougc_logo'];

	$db->update_query('forums', array('ougc_logo' => $db->escape_string($logo)), 'fid=\''.$fid.'\'');

	$cache->update_forums();
}

// Output the logo
function ougc_forumlogo_run()
{
	global $forum, $mybb;

	if(empty($forum['fid']))
	{
		switch(THIS_SCRIPT)
		{
			case 'editpost.php';
				$pid = (int)$mybb->input['pid'];
				$post = get_post($pid);
				$fid = intval($post['fid']);
				break;
			default;
				$fid = (int)$mybb->input['fid'];
				break;
		}
		$forum = get_forum($fid);
	}

	if(!$forum['fid'] || !$forum['ougc_logo'])
	{
		return; // wat?
	}

	global $theme;

	$replaces = array(
		'{bburl}'	=> $mybb->settings['bburl'],
		'{homeurl}'	=> $mybb->settings['homeurl'],
		'{imgdir}'	=> $theme['imgdir']
	);

	$forum['ougc_logo'] = str_replace(array_keys($replaces), array_values($replaces), $forum['ougc_logo']);

	// Output the logo only if no empty.
	if(!empty($forum['ougc_logo']))
	{
		global $theme, $header;

		$header = str_replace($theme['logo'], $forum['ougc_logo'], $header);
	}
}