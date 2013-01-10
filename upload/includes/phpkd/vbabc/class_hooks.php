<?php
/*==================================================================================*\
|| ################################################################################ ||
|| # Product Name: vB Automated Bookie Center 'Ultimate'         Version: 4.2.100 # ||
|| # License Type: Creative Commons - Attribution-Noncommercial-Share Alike 3.0   # ||
|| # ---------------------------------------------------------------------------- # ||
|| # 																			  # ||
|| #           Copyright ©2005-2013 PHP KingDom. Some Rights Reserved.            # ||
|| #       This product may be redistributed in whole or significant part.        # ||
|| # 																			  # ||
|| # -------- "vB Automated Bookie Center 'Ultimate'" IS A FREE SOFTWARE -------- # ||
|| #   http://www.phpkd.net | http://creativecommons.org/licenses/by-nc-sa/3.0/   # ||
|| ################################################################################ ||
\*==================================================================================*/


// No direct access! Should be accessed throuth the core class only!!
if (!defined('VB_AREA') OR !defined('PHPKD_VBABC') OR @get_class($this) != 'PHPKD_VBABC')
{
	echo 'Prohibited Access!';
	exit;
}


/**
 * Hooks class
 *
 * @category	vB Automated Bookie Center 'Ultimate'
 * @package		PHPKD_VBABC
 * @subpackage	PHPKD_VBABC_Hooks
 * @copyright	Copyright ©2005-2013 PHP KingDom. Some Rights Reserved. (http://www.phpkd.net)
 * @license		http://creativecommons.org/licenses/by-nc-sa/3.0/
 */
class PHPKD_VBABC_Hooks
{
	/**
	 * The PHPKD_VBABC registry object
	 *
	 * @var	PHPKD_VBABC
	 */
	private $_registry = null;

	/**
	 * Constructor - checks that PHPKD_VBABC registry object including vBulletin registry oject has been passed correctly.
	 *
	 * @param	PHPKD_VBABC	Instance of the main product's data registry object - expected to have both vBulletin data registry & database object as two of its members.
	 * @return	void
	 */
	public function __construct(&$registry)
	{
		if (is_object($registry))
		{
			$this->_registry =& $registry;

			if (is_object($registry->_vbulletin))
			{
				if (!is_object($registry->_vbulletin->db))
				{
					trigger_error('vBulletin Database object is not an object!', E_USER_ERROR);
				}
			}
			else
			{
				trigger_error('vBulletin Registry object is not an object!', E_USER_ERROR);
			}
		}
		else
		{
			trigger_error('PHPKD_VBABC Registry object is not an object!', E_USER_ERROR);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * (vB_DataManager_Admin) $there
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function admindata_start($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			$there->validfields['phpkd_vbabc'] = array(TYPE_UINT, REQ_NO);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * $vbphrase
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $user
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function admin_permissions_form($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			print_yes_no_row($this->_registry->_vbphrase['can_administer_phpkd_vbabc'], "phpkd_vbabc[1]", ($user['phpkd_vbabc'] & 1 ? 1 : 0));
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $admindm
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function admin_permissions_process($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			$phpkd_vbabc_perms = 0;
			$this->_registry->_vbulletin->input->clean_array_gpc('p', array(
				'phpkd_vbabc' => TYPE_ARRAY_UINT
			));

			foreach ($this->_registry->_vbulletin->GPC['phpkd_vbabc'] as $bit => $value)
			{
				if ($value)
				{
					$phpkd_vbabc_perms += $bit;
				}
			}

			$admindm->set('phpkd_vbabc', $phpkd_vbabc_perms);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show, $cache
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $cache
	 *
	 */
	public function cache_templates($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($show['phpkd_vbabc_active'])
			{
				switch (THIS_SCRIPT)
				{
					case 'newthread':
						$cache[] = 'phpkd_vbabc_newthread';
						break;

					case 'showthread':
						$cache[] = 'phpkd_vbabc_optionopen';
						$cache[] = 'phpkd_vbabc_eventopen';
						break;
				}
			}

			return array('cache' => $cache);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $do, $admin
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $return_value
	 *
	 */
	public function can_administer($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			$bits = array(
				'phpkd_vbabc' => 1
			);

			foreach($do as $field)
			{
				if (isset($bits["$field"]) AND ($admin['phpkd_vbabc'] & $bits["$field"]))
				{
					$return_value = true;
					return array('return_value' => $return_value);
				}
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $forumid
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function fetch_foruminfo($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if (isset($this->_registry->_vbulletin->bf_misc['phpkd_vbabc']))
			{
				// decipher 'phpkd_vbabc' bitfield
				$this->_registry->_vbulletin->forumcache["$forumid"]['phpkd_vbabc'] = intval($this->_registry->_vbulletin->forumcache["$forumid"]['phpkd_vbabc']);

				foreach($this->_registry->_vbulletin->bf_misc_phpkd_vbabc as $optionname => $optionval)
				{
					$this->_registry->_vbulletin->forumcache["$forumid"]["phpkd_vbabc_$optionname"] = (($this->_registry->_vbulletin->forumcache["$forumid"]['phpkd_vbabc'] & $optionval) ? 1 : 0);
				}
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * $vbphrase
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $forum
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function forumadmin_edit_form($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if (isset($this->_registry->_vbulletin->bf_misc['phpkd_vbabc']))
			{
				print_table_header($this->_registry->_vbphrase['phpkd_vbabc_options']);
				print_yes_no_row($this->_registry->_vbphrase['phpkd_vbabc_active'], 'phpkd_vbabc[active]', $forum['phpkd_vbabc_active']);
				print_yes_no_row($this->_registry->_vbphrase['phpkd_vbabc_oneonone'], 'phpkd_vbabc[oneonone]', $forum['phpkd_vbabc_oneonone']);
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $forumdata
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function forumadmin_update_save($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if (isset($this->_registry->_vbulletin->bf_misc['phpkd_vbabc']))
			{
				$this->_registry->_vbulletin->input->clean_array_gpc('p', array('phpkd_vbabc' => TYPE_ARRAY_BOOL));

				foreach ($this->_registry->_vbulletin->GPC['phpkd_vbabc'] AS $varname => $value)
				{
					if (isset($this->_registry->_vbulletin->GPC['phpkd_vbabc']["$varname"]))
					{
						$forumdata->set_bitfield('phpkd_vbabc', $varname, $value);
					}
				}
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * (vB_DataManager_Forum) $there
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function forumdata_start($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if (isset($this->_registry->_vbulletin->bf_misc['phpkd_vbabc']))
			{
				$there->bitfields['phpkd_vbabc'] =& $this->_registry->_vbulletin->bf_misc['phpkd_vbabc'];
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show, $hook_query_fields
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $hook_query_fields
	 *
	 */
	public function forumdisplay_query($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($show['phpkd_vbabc_active'])
			{
				$hook_query_fields .= ', phpkd_vbabc_eventid';
			}

			return array('hook_query_fields' => $hook_query_fields);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $output
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $output
	 *
	 */
	public function global_complete($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);
			$output = preg_replace('#All rights reserved.#i', 'All rights reserved.<div style="text-align: center">Bookie Center by <a href="http://www.vbulletin.org/forum/misc.php?do=producthelp&pid=phpkd_vbabc" target="_blank" rel="nofollow">PHPKD - vB Automated Bookie Center</a>.</div>', $output, 1, $count);

			if (empty($count))
			{
				$output = preg_replace('#<div id="footer_copyright"#i', '<div style="text-align: center" class="shade footer_copyright">Bookie Center by <a href="http://www.vbulletin.org/forum/misc.php?do=producthelp&pid=phpkd_vbabc" target="_blank" rel="nofollow">PHPKD - vB Automated Bookie Center</a>.</div><div id="footer_copyright"', $output, 1, $count);

				if (empty($count))
				{
					$output = preg_replace('#<div class="below_body">#i', '<div style="text-align: center" class="shade footer_copyright">Bookie Center by <a href="http://www.vbulletin.org/forum/misc.php?do=producthelp&pid=phpkd_vbabc" target="_blank" rel="nofollow">PHPKD - vB Automated Bookie Center</a>.</div><div class="below_body">', $output, 1, $count);

					if (empty($count))
					{
						$output = preg_replace('#Powered by vBulletin&trade;#i', '<div style="text-align: center" class="shade footer_copyright">Bookie Center by <a href="http://www.vbulletin.org/forum/misc.php?do=producthelp&pid=phpkd_vbabc" target="_blank" rel="nofollow">PHPKD - vB Automated Bookie Center</a>.</div>Powered by vBulletin&trade;', $output, 1, $count);

						if (empty($count))
						{
							$output = preg_replace('#</body>#i', '<div style="text-align: center" class="shade footer_copyright">Bookie Center by <a href="http://www.vbulletin.org/forum/misc.php?do=producthelp&pid=phpkd_vbabc" target="_blank" rel="nofollow">PHPKD - vB Automated Bookie Center</a>.</div></body>', $output, 1, $count);
						}
					}
				}
			}

			return array('output' => $output);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $show
	 *
	 */
	public function global_state_check($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($this->_registry->_vbulletin->options['phpkd_vbabc_active'] AND (($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canview']) OR ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canmoderate'])) AND $this->_registry->_vbulletin->forumcache[$this->_registry->_vbulletin->GPC['forumid']]['phpkd_vbabc_active'])
			{
				$show['phpkd_vbabc_active']  = true;
				$phpkd_vbabc_inex_users_ids  = @explode(',', trim($this->_registry->_vbulletin->options['phpkd_vbabc_inex_users_ids']));

				switch ($this->_registry->_vbulletin->options['phpkd_vbabc_inex_users'])
				{
					case 1:
						if (is_array($phpkd_vbabc_inex_users_ids) AND !empty($phpkd_vbabc_inex_users_ids) AND !in_array($this->_registry->_vbulletin->userinfo['userid'], $phpkd_vbabc_inex_users_ids))
						{
							$show['phpkd_vbabc_active']  = false;
						}
						break;

					case 2:
						if (is_array($phpkd_vbabc_inex_users_ids) AND !empty($phpkd_vbabc_inex_users_ids) AND in_array($this->_registry->_vbulletin->userinfo['userid'], $phpkd_vbabc_inex_users_ids))
						{
							$show['phpkd_vbabc_active']  = false;
						}
						break;
				}
			}

			return array('show' => $show);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show, $newpost, $checked, $threadmanagement
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $show, $threadmanagement
	 *
	 */
	public function newthread_form_complete($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($show['phpkd_vbabc_active'] AND (($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canadd']) OR ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canmoderate'])))
			{
				$show['additional_options'] = true;
				$checked['phpkd_vbabc'] = ($newpost['phpkd_vbabc'] ? ' checked="checked"' : '');
				$phpkd_vbabc_opts = ($newpost['phpkd_vbabc_opts'] ? $newpost['phpkd_vbabc_opts'] : 2);

				$templater = vB_Template::create('phpkd_vbabc_newthread');
					$templater->register('checked', $checked);
					$templater->register('phpkd_vbabc_opts', $phpkd_vbabc_opts);
				$threadmanagement .= $templater->render();
			}

			return array('show' => $show, 'threadmanagement' => $threadmanagement);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $newpost, $forumperms
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function newthread_post_complete($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($newpost['phpkd_vbabc'])
			{
				$this->_registry->_vbulletin->url = $this->_registry->_vbulletin->options['phpkd_vbabc_script'] . '?' . $this->_registry->_vbulletin->session->vars['sessionurl'] . "do=eventadd&t=$newpost[threadid]&opts=$newpost[phpkd_vbabc_opts]";

				if ($forumperms & $this->_registry->_vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				{
					eval(print_standard_redirect('redirect_postthanks', true, true));
				}
				else
				{
					eval(print_standard_redirect('redirect_postthanks_nopermission', true, true));
				}
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $newpost
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $newpost
	 *
	 */
	public function newthread_post_start($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if (($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canadd']) OR ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canmoderate']))
			{
				$this->_registry->_vbulletin->input->clean_array_gpc('p', array(
					'phpkd_vbabc'      => TYPE_BOOL,
					'phpkd_vbabc_opts' => TYPE_UINT,
				));

				$newpost['phpkd_vbabc']      =& $this->_registry->_vbulletin->GPC['phpkd_vbabc'];
				$newpost['phpkd_vbabc_opts'] =& $this->_registry->_vbulletin->GPC['phpkd_vbabc_opts'];
			}

			return array('newpost' => $newpost);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show, $threadinfo, $foruminfo, $session, $template_hook
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $template_hook
	 *
	 */
	public function showthread_complete($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($show['phpkd_vbabc_active'])
			{
				if ($threadinfo['phpkd_vbabc_eventid'])
				{
					$phpkd_vbabc = '';
					$phpkd_vbabc_optionstpl = '';
					$phpkd_vbabc_cash = $this->_registry->getDmhandle()->cashbalance();

					$show['phpkd_vbabc_canmoderate'] = iif($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canmoderate'], true, false);
					$show['phpkd_vbabc_canedit'] = iif(($show['phpkd_vbabc_canmoderate'] OR ($this->_registry->_vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canedit']))), true, false);

					// get bookie event info
					$phpkd_vbabc_eventinfo = $this->_registry->_vbulletin->db->query_first("
						SELECT events.*, groups.title AS grouptitle, COUNT(DISTINCT bet.userid) AS betters
						FROM " . TABLE_PREFIX . "phpkd_vbabc_event AS events
						LEFT JOIN " . TABLE_PREFIX . "phpkd_vbabc_group AS groups USING (groupid)
						LEFT JOIN " . TABLE_PREFIX . "phpkd_vbabc_bet AS bet USING (eventid)
						WHERE events.eventid = " . $threadinfo['phpkd_vbabc_eventid']
					);

					$phpkd_vbabc_optquery = $this->_registry->_vbulletin->db->query_read("
						SELECT options.*
						FROM " . TABLE_PREFIX . "phpkd_vbabc_option AS options
						WHERE options.eventid = " . $phpkd_vbabc_eventinfo['eventid'] . "
						ORDER BY options.optionid
					");


					// If bookie event timeout (or payafter) has been passed & the event still openned, CLOSE IT! (Why 'payafter' also? Since payafter should be after timeout anyway & thus it means timeout already passed)
					if ($phpkd_vbabc_eventinfo['timeout'] < TIMENOW OR $phpkd_vbabc_eventinfo['payafter'] < TIMENOW)
					{
						if ($phpkd_vbabc_eventinfo['status'] == 'open')
						{
							$this->_registry->getDmhandle()->eventclose($phpkd_vbabc_eventinfo);
							$phpkd_vbabc_eventinfo['status'] = 'closed';
						}

						$phpkd_vbabc_eventinfo['mstatus'] = 1;
					}

					// Bookie event payafter date already passed & it isn't paid yet, additionally the grace period determined by staff for payment also passed without any action taken still! We've to abandon this bookie event! Bets get off & return back all stakes!!
					if ($phpkd_vbabc_eventinfo['payafter'] < TIMENOW AND $phpkd_vbabc_eventinfo['status'] == 'closed' AND ($phpkd_vbabc_eventinfo['payafter'] + ($this->_registry->_vbulletin->options['phpkd_vbabc_settle_graceperiod'] * 86400)) < TIMENOW)
					{
						$this->_registry->getDmhandle()->eventabandon($phpkd_vbabc_eventinfo);
						$phpkd_vbabc_eventinfo['status'] = 'abandoned';
					}

					require_once(DIR . '/includes/class_bbcode.php');
					$bbcode_parser = new vB_BbCodeParser($this->_registry->_vbulletin, fetch_tag_list());
					$phpkd_vbabc_eventinfo['title'] = $bbcode_parser->parse(unhtmlspecialchars($phpkd_vbabc_eventinfo['title']), $foruminfo['forumid'], true);
					$phpkd_vbabc_eventinfo['fstatus'] = ucfirst($phpkd_vbabc_eventinfo['status']);
					$phpkd_vbabc_eventinfo['betters'] = vb_number_format($phpkd_vbabc_eventinfo['betters']);

					$phpkd_vbabc_options = array();
					$phpkd_vbabc_lowestodd = intval(2147483647);
					while ($phpkd_vbabc_opt = $this->_registry->_vbulletin->db->fetch_array($phpkd_vbabc_optquery))
					{
						$phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]] = $phpkd_vbabc_opt;
						$phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['title'] = $bbcode_parser->parse(unhtmlspecialchars($phpkd_vbabc_opt['title']), $foruminfo['forumid'], true);
						$phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['decimal'] = (float) $phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['odds_against'] / (float) $phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['odds_for'];

						if ($phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['decimal'] < $phpkd_vbabc_lowestodd)
						{
							$phpkd_vbabc_lowestodd = $phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['decimal'];
							$phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['favorite'] = ' F';
						}
						else if ($phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['decimal'] == $phpkd_vbabc_lowestodd)
						{
							// Joint favorite
							$phpkd_vbabc_options[$phpkd_vbabc_opt[optionid]]['favorite'] = ' F';
						}
					}

					$show['phpkd_vbabc_betallow'] = iif($phpkd_vbabc_eventinfo['status'] != 'open' OR !$threadinfo['open'] OR (!$show['phpkd_vbabc_canmoderate'] AND !($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canbet'])) OR $phpkd_vbabc_cash < 1 OR (!$phpkd_vbabc_eventinfo['multiple'] AND $this->_registry->getDmhandle()->uebets($this->_registry->_vbulletin->userinfo, $phpkd_vbabc_eventinfo) > 0) OR ($foruminfo['phpkd_vbabc_oneonone'] AND $phpkd_vbabc_eventinfo['chalenged'] AND $this->_registry->_vbulletin->userinfo['userid'] != $phpkd_vbabc_eventinfo['chalenged']), false, true);
					$show['phpkd_vbabc_betprivateallow'] = iif(!$show['phpkd_vbabc_betallow'] OR !($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canprivatebet']), false, true);
					$show['phpkd_vbabc_settleallow'] = iif($phpkd_vbabc_eventinfo['status'] == 'closed' AND ($show['phpkd_vbabc_canmoderate'] OR ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['cansettle'])), true, false);

					if ($phpkd_vbabc_eventinfo['timeout'])
					{
						$phpkd_vbabc_eventinfo['timeouttime'] = vbdate($this->_registry->_vbulletin->options['timeformat'], $phpkd_vbabc_eventinfo['timeout']);
						$phpkd_vbabc_eventinfo['timeoutdate'] = vbdate($this->_registry->_vbulletin->options['dateformat'], $phpkd_vbabc_eventinfo['timeout']);
					}

					if ($phpkd_vbabc_eventinfo['payafter'])
					{
						$phpkd_vbabc_eventinfo['payaftertime'] = vbdate($this->_registry->_vbulletin->options['timeformat'], $phpkd_vbabc_eventinfo['payafter']);
						$phpkd_vbabc_eventinfo['payafterdate'] = vbdate($this->_registry->_vbulletin->options['dateformat'], $phpkd_vbabc_eventinfo['payafter']);
					}


					$counter = 1;
					foreach ($phpkd_vbabc_options AS $optionid => $option)
					{
						if ($option['bets'] <= 0)
						{
							$option['percent'] = 0;
						}
						else if ($phpkd_vbabc_eventinfo['multiple'])
						{
							$option['percent'] = vb_number_format(($option['bets'] < $phpkd_vbabc_eventinfo['betters']) ? $option['bets'] / $phpkd_vbabc_eventinfo['betters'] * 100 : 100, 2);
						}
						else
						{
							$option['percent'] = vb_number_format(($option['bets'] < $phpkd_vbabc_eventinfo['bets']) ? $option['bets'] / $phpkd_vbabc_eventinfo['bets'] * 100 : 100, 2);
						}

						$option['decimal'] = vb_number_format($option['decimal'], 2);
						$option['odds_against'] = vb_number_format($option['odds_against']);
						$option['odds_for'] = vb_number_format($option['odds_for']);
						$option['bets'] = vb_number_format($option['bets']);
						$option['staked'] = vb_number_format($option['staked']);
						$option['graphicnumber'] = $counter % 6 + 1;

						$templater = vB_Template::create('phpkd_vbabc_optionopen');
							$templater->register('show', $show);
							$templater->register('option', $option);
							$templater->register('eventinfo', $phpkd_vbabc_eventinfo);
						$phpkd_vbabc_optionstpl .= $templater->render();

						$counter++;
					}


					$templater = vB_Template::create('phpkd_vbabc_eventopen');
						$templater->register('show', $show);
						$templater->register('options', $phpkd_vbabc_optionstpl);
						$templater->register('eventinfo', $phpkd_vbabc_eventinfo);
						$templater->register('threadinfo', $threadinfo);
					$phpkd_vbabc = $templater->render();

					$template_hook['showthread_above_posts'] .= $phpkd_vbabc;
				}
				else if ((($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) OR ($this->_registry->_vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND ($this->_registry->_vbulletin->userinfo['permissions']['phpkd_vbabc'] & $this->_registry->_vbulletin->bf_ugp_phpkd_vbabc['canadd']) AND (!$this->_registry->_vbulletin->options['phpkd_vbabc_timelimitadd'] OR ($this->_registry->_vbulletin->options['phpkd_vbabc_timelimitadd'] AND (TIMENOW - ($this->_registry->_vbulletin->options['phpkd_vbabc_timelimitadd'] * 60)) < $threadinfo['dateline'])))))
				{
					$this->_registry->_vbulletin->templatecache['SHOWTHREAD'] = str_replace('vB_Template_Runtime::parsePhrase("show_printable_version") . \'', 'vB_Template_Runtime::parsePhrase("show_printable_version") . \'</a></li><li><a href="\' . $vbulletin->options[\'phpkd_vbabc_script\'] . \'?\' . $session[\'sessionurl\'] . \'do=eventadd&amp;t=\' . $threadinfo[\'threadid\'] . \'&amp;opts=2">\' . vB_Template_Runtime::parsePhrase("phpkd_vbabc_add") . \'&hellip;', $this->_registry->_vbulletin->templatecache['SHOWTHREAD']);
				}
			}

			return array('template_hook' => $template_hook);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * $vbphrase
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $only
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $only
	 *
	 */
	public function template_groups($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			$only['phpkd_vbabc'] = $this->_registry->_vbphrase['phpkd_vbabc'];

			return array('only' => $only);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * $vbphrase
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $show, $thread, $allowicons
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * $show, $thread
	 *
	 */
	public function threadbit_process($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($show['phpkd_vbabc_active'])
			{
				// thread not moved
				if ($thread['open'] != 10)
				{
					// allow thread icons?
					if ($allowicons)
					{
						// show bookie event icon
						if ($thread['phpkd_vbabc_eventid'] != 0)
						{
							$show['threadicon'] = true;
							$thread['threadiconpath'] = vB_Template_Runtime::fetchStyleVar('imgdir_phpkd_vbabc') . "/money.png";
							$thread['threadicontitle'] = $this->_registry->_vbphrase['phpkd_vbabc_event'];
						}
					}
				}
			}

			return array('show' => $show, 'thread' => $thread);
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * $physicaldel, (vB_DataManager_Thread) $there
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function threaddata_delete($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			if ($physicaldel AND $phpkd_vbabc_eventinfo = $this->_registry->_vbulletin->db->query_first("SELECT thread.phpkd_vbabc_eventid, event.* FROM " . TABLE_PREFIX . "thread AS thread LEFT JOIN " . TABLE_PREFIX . "phpkd_vbabc_event AS event ON (thread.phpkd_vbabc_eventid = event.eventid) WHERE thread.threadid = " . intval($there->fetch_field('threadid'))))
			{
				if ($phpkd_vbabc_eventinfo['eventid'])
				{
					$this->_registry->getDmhandle()->eventdelete($phpkd_vbabc_eventinfo);
				}
			}
		}
	}

	/*
	 * Required Initializations
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 * Input Parameters:
	 * ~~~~~~~~~~~~~~~~~~
	 * (vB_DataManager_Thread) $there
	 *
	 * Output Parameters:
	 * ~~~~~~~~~~~~~~~~~~~
	 * NULL
	 *
	 */
	public function threaddata_start($params)
	{
		// Parameters required!
		if ($this->_registry->verify_hook_params($params))
		{
			@extract($params);

			$there->validfields['phpkd_vbabc_eventid'] = array(TYPE_UINT, REQ_NO);
		}
	}
}
