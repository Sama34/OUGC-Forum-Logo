<?php

/***************************************************************************
 *
 *   OUGC Forum Logo plugin (/inc/plugins/ougc_forumlogo.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Use header images on a by forum basis.
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
    $lang->load('ougc_forumlogo');

	return array(
		'name'			=> 'OUGC Forum Logo',
		'description'	=> $lang->ougc_forumlogo_d,
		'website'		=> 'http://mods.mybb.com/view/ougc-forum-logo',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.0',
		'compatibility'	=> '16*',
		'guid'			=> 'e484d7a66b9c41db8c6617469443642b'
	);
}

// Install the plugin
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

// Check if installed
function ougc_forumlogo_is_installed()
{
	global $db;

	return $db->field_exists('ougc_logo', 'forums');
}

// Uninstall this plugin
function ougc_forumlogo_uninstall()
{
	global $db;

	if($db->field_exists('ougc_logo', 'forums'))
	{
		global $cache;

		$db->drop_column('forums', 'ougc_logo');
		$cache->update_forums();
	}
}

//Add Textbox in Forum Management 
function ougc_forumlogo_edit(&$args)
{
	global $lang, $mybb;

	if($args['title'] == $lang->style_options && $lang->style_options && $mybb->input['module'] == 'forum-management' && $mybb->input['action'] == 'edit')
	{
		global $form, $forum_data;
		$lang->load('ougc_forumlogo');

		$args['content'] .= '<div class="forum_settings_bit">'.$lang->ougc_forumlogo_title.'<br />'.$form->generate_text_box('ougc_logo', isset($forum_data['ougc_logo']) ? $forum_data['ougc_logo'] : '').'</div>';
	}
}

// Save the forum data.
function ougc_forumlogo_update()
{
	global $db, $mybb, $fid, $cache;

	$db->update_query('forums', array('ougc_logo' => $db->escape_string(trim($mybb->input['ougc_logo']))), 'fid=\''.$fid.'\'');
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

	// Lets figure out the image location. \\
	// The image is suppose to be external.
	if(my_strpos($forum['ougc_logo'], 'ttp:/') || my_strpos($forum['ougc_logo'], 'ttps:/')) 
	{
		$forum['ougc_logo'] = $forum['ougc_logo'];
	}
	// The image is suppose to be internal inside our images folder.
	elseif(!my_strpos($forum['ougc_logo'], '/') && !empty($forum['ougc_logo']) && file_exists(MYBB_ROOT.'/images/ougc_logos/'.$forum['ougc_logo'])) 
	{
		$forum['ougc_logo'] = $mybb->settings['bburl'].'/images/ougc_logos/'.htmlspecialchars_uni($forum['ougc_logo']);
	}
	// Image is suppose to be internal.
	elseif(!empty($forum['ougc_logo']) && file_exists(MYBB_ROOT.'/'.$forum['ougc_logo']))
	{
		$forum['ougc_logo'] = $mybb->settings['bburl'].'/'.htmlspecialchars_uni($forum['ougc_logo']);
	}

	// Output the logo only if no empty.
	if(!empty($forum['ougc_logo']))
	{
		global $theme, $header;

		$header = str_replace($theme['logo'], $forum['ougc_logo'], $header);
	}
}