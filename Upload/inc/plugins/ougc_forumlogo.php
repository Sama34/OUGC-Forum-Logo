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
		'version'		=> '1.8',
		'versioncode'	=> '1800',
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
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Add Textbox in Forum Management 
function ougc_forumlogo_edit(&$args)
{
	global $lang, $mybb;

	if($args['title'] == $lang->style_options && $lang->style_options && $mybb->get_input('module') == 'forum-management' && $mybb->get_input('action') == 'edit')
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

	$db->update_query('forums', array('ougc_logo' => $db->escape_string($mybb->get_input('ougc_logo'))), 'fid=\''.$fid.'\'');

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
				$pid = $mybb->get_input('pid', 1);
				$post = get_post($pid);
				$fid = intval($post['fid']);
				break;
			default;
				$fid = $mybb->get_input('fid', 1);
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

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}

global $mybb;

if(!method_exists($mybb, 'get_input'))
{
	control_object($mybb, 'function get_input($name, $type=0)
{
	switch($type)
	{
		case 2:
			if(!isset($this->input[$name]) || !is_array($this->input[$name]))
			{
				return array();
			}
			return $this->input[$name];
		case 1:
			if(!isset($this->input[$name]) || !is_numeric($this->input[$name]))
			{
				return 0;
			}
			return (int)$this->input[$name];
		default:
			if(!isset($this->input[$name]) || !is_scalar($this->input[$name]))
			{
				return \'\';
			}
			return $this->input[$name];
	}
}');
}

if(!method_exists($mybb->cache, 'delete'))
{
	control_object($mybb->cache, '
 function delete($name, $greedy = false)
 {
	 global $db, $mybb, $cache;

	// Prepare for database query.
	$dbname = $db->escape_string($name);
	$where = "title = \'{$dbname}\'";

	// Delete on-demand or handler cache
	if($this->handler)
	{
		get_execution_time();

		$hit = $this->handler->delete($name);

		$call_time = get_execution_time();
		$this->call_time += $call_time;
		$this->call_count++;

		if($mybb->debug_mode)
		{
			$this->debug_call(\'delete:\'.$name, $call_time, $hit);
		}
	}

	// Greedy?
	if($greedy)
	{
		$name .= \'_\';
		$names = array();
		$keys = array_keys($cache->cache);

		foreach($keys as $key)
		{
			if(strpos($key, $name) === 0)
			{
				$names[$key] = 0;
			}
		}

		$ldbname = strtr($dbname,
			array(
				\'%\' => \'=%\',
				\'=\' => \'==\',
				\'_\' => \'=_\'
			)
		);

		$where .= " OR title LIKE \'{$ldbname}=_%\' ESCAPE \'=\'";

		if($this->handler)
		{
			$query = $db->simple_select("datacache", "title", $where);

			while($row = $db->fetch_array($query))
			{
				$names[$row[\'title\']] = 0;
			}

			// ...from the filesystem...
			$start = strlen(MYBB_ROOT."cache/");
			foreach((array)@glob(MYBB_ROOT."cache/{$name}*.php") as $filename)
			{
				if($filename)
				{
					$filename = substr($filename, $start, strlen($filename)-4-$start);
					$names[$filename] = 0;
				}
			}

			foreach($names as $key => $val)
			{
				get_execution_time();

				$hit = $this->handler->delete($key);

				$call_time = get_execution_time();
				$this->call_time += $call_time;
				$this->call_count++;

				if($mybb->debug_mode)
				{
					$this->debug_call(\'delete:\'.$name, $call_time, $hit);
				}
			}
		}
	}

	// Delete database cache
	$db->delete_query("datacache", $where);
}');
}