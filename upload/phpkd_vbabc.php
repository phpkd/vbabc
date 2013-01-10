<?php
/*==================================================================================*\
|| ################################################################################ ||
|| # Product Name: vB Automated Bookie Center 'Ultimate'         Version: 4.1.100 # ||
|| # License Type: Creative Commons - Attribution-Noncommercial-Share Alike 3.0   # ||
|| # ---------------------------------------------------------------------------- # ||
|| # 																			  # ||
|| #            Copyright ©2005-2013 PHP KingDom. All Rights Reserved.            # ||
|| #       This product may be redistributed in whole or significant part.        # ||
|| # 																			  # ||
|| # -------- "vB Automated Bookie Center 'Ultimate'" IS A FREE SOFTWARE -------- # ||
|| #   http://www.phpkd.net | http://creativecommons.org/licenses/by-nc-sa/3.0/   # ||
|| ################################################################################ ||
\*==================================================================================*/


// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'phpkd_vbabc');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting', 'timezone');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'phpkd_vbabc_event',
	'phpkd_vbabc_eventpreview',
	'phpkd_vbabc_optionpreview',
	'phpkd_vbabc_option',
	'pollresult',
	'pollresults',
	'pollresults_table',
	'forumrules',
	'forumdisplay_loggedinuser',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_bbcode_alt.php');
require_once(DIR . '/includes/phpkd/vbabc/class_core.php');
$phpkd_vbabc = new PHPKD_VBABC($vbulletin, $vbphrase);

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'main';
}

$vbulletin->input->clean_array_gpc('r', array(
	'eventid' => TYPE_UINT,
	'userid'  => TYPE_UINT,
));

if ($vbulletin->GPC['eventid'])
{
	// get bookie event info
	$phpkd_vbabc_eventinfo = $vbulletin->db->query_first("
		SELECT events.*, groups.title AS grouptitle
		FROM " . TABLE_PREFIX . "phpkd_vbabc_event AS events
		LEFT JOIN " . TABLE_PREFIX . "phpkd_vbabc_group AS groups USING (groupid)
		WHERE events.eventid = " . $vbulletin->GPC['eventid']
	);

	$threadinfo = fetch_threadinfo($phpkd_vbabc_eventinfo['threadid']);
	$foruminfo = fetch_foruminfo($threadinfo['forumid']);
}

// Re-Execute the following hook: 'global_state_check' after supplying: $vbulletin->GPC['forumid']
$vbulletin->GPC['forumid'] = $foruminfo['forumid'];
extract($phpkd_vbabc->fetch_hook('global_state_check', array('show' => $show)));
if (!$show['phpkd_vbabc_active'])
{
	eval(standard_error($vbulletin->options['phpkd_vbabc_closedreason']));
}

if ($threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts') AND $vbulletin->userinfo['userid'] != $threadinfo['postuserid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

if (!$foruminfo['forumid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
}

// If bookie event timeout (or payafter) has been passed & the event still openned, CLOSE IT! (Why 'payafter' also? Since payafter should be after timeout anyway & thus it means timeout already passed)
if ($phpkd_vbabc_eventinfo['timeout'] < TIMENOW OR $phpkd_vbabc_eventinfo['payafter'] < TIMENOW)
{
	if ($phpkd_vbabc_eventinfo['status'] == 'open')
	{
		$phpkd_vbabc->getDmhandle()->eventclose($phpkd_vbabc_eventinfo);
		$phpkd_vbabc_eventinfo['status'] = 'closed';
	}

	$phpkd_vbabc_eventinfo['mstatus'] = 1;
}

// Bookie event payafter date already passed & it isn't paid yet, additionally the grace period determined by staff for payment also passed without any action taken still! We've to abandon this bookie event! Bets get off & return back all stakes!!
if ($phpkd_vbabc_eventinfo['payafter'] < TIMENOW AND $phpkd_vbabc_eventinfo['status'] == 'closed' AND ($phpkd_vbabc_eventinfo['payafter'] + ($vbulletin->options['phpkd_vbabc_settle_graceperiod'] * 86400)) < TIMENOW)
{
	$phpkd_vbabc->getDmhandle()->eventabandon($phpkd_vbabc_eventinfo);
	$phpkd_vbabc_eventinfo['status'] = 'abandoned';
}

// Check if thread is open or not
if (!$threadinfo['open'])
{
	eval(standard_error(fetch_error('threadclosed')));
}

// check permissions
$forumperms = fetch_permissions($foruminfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

$timeout = array();
$payafter = array();
$varexist = array();
$phpkd_vbabc_daterange = @explode('-', $vbulletin->options['phpkd_vbabc_daterange']);
$months = array(
	1 => 'january',
	2 => 'february',
	3 => 'march',
	4 => 'april',
	5 => 'may',
	6 => 'june',
	7 => 'july',
	8 => 'august',
	9 => 'september',
	10 => 'october',
	11 => 'november',
	12 => 'december'
);
$timelist = array(
	'00.00' => '12:00 Midnight',
	'00.30' => '12:30 AM',
	'01.00' => '01:00 AM',
	'01.30' => '01:30 AM',
	'02.00' => '02:00 AM',
	'02.30' => '02:30 AM',
	'03.00' => '03:00 AM',
	'03.30' => '03:30 AM',
	'04.00' => '04:00 AM',
	'04.30' => '04:30 AM',
	'05.00' => '05:00 AM',
	'05.30' => '05:30 AM',
	'06.00' => '06:00 AM',
	'06.30' => '06:30 AM',
	'07.00' => '07:00 AM',
	'07.30' => '07:30 AM',
	'08.00' => '08:00 AM',
	'08.30' => '08:30 AM',
	'09.00' => '09:00 AM',
	'09.30' => '09:30 AM',
	'10.00' => '10:00 AM',
	'10.30' => '10:30 AM',
	'11.00' => '11:00 AM',
	'11.30' => '11:30 AM',
	'12.00' => '12:00 Midday',
	'12.30' => '12:30 PM',
	'13.00' => '01:00 PM',
	'13.30' => '01:30 PM',
	'14.00' => '02:00 PM',
	'14.30' => '02:30 PM',
	'15.00' => '03:00 PM',
	'15.30' => '03:30 PM',
	'16.00' => '04:00 PM',
	'16.30' => '04:30 PM',
	'17.00' => '05:00 PM',
	'17.30' => '05:30 PM',
	'18.00' => '06:00 PM',
	'18.30' => '06:30 PM',
	'19.00' => '07:00 PM',
	'19.30' => '07:30 PM',
	'20.00' => '08:00 PM',
	'20.30' => '08:30 PM',
	'21.00' => '09:00 PM',
	'21.30' => '09:30 PM',
	'22.00' => '10:00 PM',
	'22.30' => '10:30 PM',
	'23.00' => '11:00 PM',
	'23.30' => '11:30 PM'
);


// ############################### start post bookie event ###############################
if ($_POST['do'] == 'eventpost')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'          => TYPE_NOHTML,
		'group'          => TYPE_NOHTML,
		'preview'        => TYPE_STR,
		'updatenumber'   => TYPE_STR,
		'opts'           => TYPE_UINT,
		'public'         => TYPE_BOOL,
		'parseurl'       => TYPE_BOOL,
		'multiple'       => TYPE_BOOL,
		'chalengedcount' => TYPE_UINT,
		'options'        => TYPE_ARRAY_ARRAY,
		'timeout'        => TYPE_ARRAY_NUM,
		'payafter'       => TYPE_ARRAY_NUM
	));

	$timeout_time = @explode('.', $vbulletin->GPC['timeout']['time']);
	$payafter_time = @explode('.', $vbulletin->GPC['payafter']['time']);
	$timeout['stamp'] = @gmmktime($timeout_time[0], $timeout_time[1] . '0', 0, $vbulletin->GPC['timeout']['month'], $vbulletin->GPC['timeout']['day'], $vbulletin->GPC['timeout']['year']);
	$payafter['stamp'] = @gmmktime($payafter_time[0], $payafter_time[1] . '0', 0, $vbulletin->GPC['payafter']['month'], $vbulletin->GPC['payafter']['day'], $vbulletin->GPC['payafter']['year']);

	if ($vbulletin->GPC['eventid'])
	{
		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND ($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canedit'])))
		{
			print_no_permission();
		}

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND $vbulletin->options['phpkd_vbabc_timelimitedit'] AND TIMENOW - ($vbulletin->options['phpkd_vbabc_timelimitedit'] * 60) > $threadinfo['dateline'])
		{
			eval(standard_error(fetch_error('phpkd_vbabc_timelimitedit', $vbulletin->options['phpkd_vbabc_timelimitedit'])));
		}
	}
	else
	{
		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND ($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canadd'])))
		{
			print_no_permission();
		}

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND $vbulletin->options['phpkd_vbabc_timelimitadd'] AND TIMENOW - ($vbulletin->options['phpkd_vbabc_timelimitadd'] * 60) > $threadinfo['dateline'])
		{
			eval(standard_error(fetch_error('phpkd_vbabc_timelimitadd', $vbulletin->options['phpkd_vbabc_timelimitadd'])));
		}
	}


	if ($vbulletin->options['phpkd_vbabc_maxopts'] > 0 AND $vbulletin->GPC['opts'] > $vbulletin->options['phpkd_vbabc_maxopts'])
	{
		$vbulletin->GPC['opts'] = $vbulletin->options['phpkd_vbabc_maxopts'];
	}


	$counter = 0;
	$optioncount = 0;
	$badoption = '';
	require_once(DIR . '/includes/functions_newpost.php');

	while ($counter++ < $vbulletin->GPC['opts'])
	{
		$vbulletin->input->clean($vbulletin->GPC['options']["$counter"]['optionid'], TYPE_UINT);
		$vbulletin->input->clean($vbulletin->GPC['options']["$counter"]['title'], TYPE_NOHTML);
		$vbulletin->input->clean($vbulletin->GPC['options']["$counter"]['odds_against'], TYPE_UINT);
		$vbulletin->input->clean($vbulletin->GPC['options']["$counter"]['odds_for'], TYPE_UINT);

		if ($vbulletin->GPC['parseurl'] AND $foruminfo['allowbbcode'])
		{
			$vbulletin->GPC['options']["$counter"]['title'] = convert_url_to_bbcode($vbulletin->GPC['options']["$counter"]['title']);
		}

		if ($vbulletin->options['phpkd_vbabc_optionlength'] AND vbstrlen($vbulletin->GPC['options']["$counter"]['title']) > $vbulletin->options['phpkd_vbabc_optionlength'])
		{
			$badoption .= iif($badoption, ', ') . $counter;
		}

		if (!empty($vbulletin->GPC['options']["$counter"]['title']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_against']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_for']))
		{
			$optioncount++;
		}
	}

	if ($badoption)
	{
		eval(standard_error(fetch_error('phpkd_vbabc_optionlength', $vbulletin->options['phpkd_vbabc_optionlength'], $badoption)));
	}

	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	if ($vbulletin->GPC['preview'] != '' OR $vbulletin->GPC['updatenumber'] != '')
	{
		if ($vbulletin->GPC['preview'] != '')
		{
			$counter = 0;
			$eventpreview = '';
			$previewtitle = $bbcode_parser->parse(unhtmlspecialchars($vbulletin->GPC['title']), $foruminfo['forumid'], $foruminfo['allowsmilies']);
			$eventpreviewoptions = '';
			while ($counter++ < $vbulletin->GPC['opts'])
			{
				$option = $bbcode_parser->parse($vbulletin->GPC['options']["$counter"]['title'], $foruminfo['forumid'], $foruminfo['allowsmilies']);
				$templater = vB_Template::create('phpkd_vbabc_optionpreview');
					$templater->register('option', $option);
				$eventpreviewoptions .= $templater->render();
			}

			$templater = vB_Template::create('phpkd_vbabc_eventpreview');
				$templater->register('eventpreviewoptions', $eventpreviewoptions);
				$templater->register('previewtitle', $previewtitle);
			$eventpreview = $templater->render();
		}

		$checked = array(
			'multiple' => ($vbulletin->GPC['multiple'] ? 'checked="checked"' : ''),
			'public'   => ($vbulletin->GPC['public'] ? 'checked="checked"' : ''),
			'parseurl' => ($vbulletin->GPC['parseurl'] ? 'checked="checked"' : ''),
		);

		$_REQUEST['do'] = ($vbulletin->GPC['eventid'] ? 'eventedit' : 'eventadd');
	}
	else
	{
		if (empty($vbulletin->GPC['title']) OR empty($vbulletin->GPC['group']) OR $optioncount < 2)
		{
			eval(standard_error(fetch_error('phpkd_vbabc_missing')));
		}

		if ($timeout['stamp'] >= 2147483647)
		{ // maximuim size of a 32 bit integer
			eval(standard_error(fetch_error('phpkd_vbabc_maxtimeout')));
		}

		if ($payafter['stamp'] >= 2147483647)
		{ // maximuim size of a 32 bit integer
			eval(standard_error(fetch_error('phpkd_vbabc_maxpayafter')));
		}

		if ($timeout['stamp'] > $payafter['stamp'])
		{ // Bookie event should reach timeout first, then payafter second!
			eval(standard_error(fetch_error('phpkd_vbabc_timeoutpayafter')));
		}

		// check max images
		if ($vbulletin->options['maximages'])
		{
			$counter = 0;
			while ($counter++ < $vbulletin->GPC['opts'])
			{
				$maximgtest .= $vbulletin->GPC['options']["$counter"]['title'];
			}

			$img_parser = new vB_BbCodeParser_ImgCheck($vbulletin, fetch_tag_list());
			$parsedmessage = $img_parser->parse($maximgtest . $vbulletin->GPC['title'], $foruminfo['forumid'], $foruminfo['allowsmilies'], true);

			require_once(DIR . '/includes/functions_misc.php');
			$imagecount = fetch_character_count($parsedmessage, '<img');
			if ($imagecount > $vbulletin->options['maximages'])
			{
				eval(standard_error(fetch_error('toomanyimages', $imagecount, $vbulletin->options['maximages'])));
			}
		}

		$vbulletin->GPC['title'] = fetch_censored_text($vbulletin->GPC['title']);
		$vbulletin->GPC['group'] = fetch_censored_text($vbulletin->GPC['group']);

		$varexist['group'] = $vbulletin->db->query_first("SELECT groupid FROM " . TABLE_PREFIX . "phpkd_vbabc_group WHERE title = '" . $vbulletin->db->escape_string($vbulletin->GPC['group']) . "'");

		if (empty($varexist['group']['groupid']))
		{
			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_group (title) VALUES ('" . $vbulletin->GPC['group'] . "')");
			$varexist['group']['groupid'] = $vbulletin->db->insert_id();
		}

		if ($vbulletin->GPC['eventid'])
		{
			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET groupid = " . $varexist['group']['groupid'] . ", title = '" . $vbulletin->db->escape_string($vbulletin->GPC['title']) . "', timeout = $timeout[stamp], payafter = $payafter[stamp], public = " . $vbulletin->GPC['public'] . ", multiple = " . $vbulletin->GPC['multiple'] . ", chalengedcount = " . $vbulletin->GPC['chalengedcount'] . " WHERE eventid = " . $vbulletin->GPC['eventid']);
			$varexist['event']['eventid'] = $vbulletin->GPC['eventid'];
		}
		else
		{
			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_event (groupid, threadid, title, dateline, timeout, payafter, public, multiple, chalengedcount, chalengeduser, userid) VALUES (" . $varexist['group']['groupid'] . ", $threadinfo[threadid], '" . $vbulletin->db->escape_string($vbulletin->GPC['title']) . "', " . TIMENOW . ", $timeout[stamp], $payafter[stamp], " . $vbulletin->GPC['public'] . ", " . $vbulletin->GPC['multiple'] . ", " . $vbulletin->GPC['chalengedcount'] . ", " . $vbulletin->GPC['userid'] . ", " . $vbulletin->userinfo['userid'] . ")");
			$varexist['event']['eventid'] = $vbulletin->db->insert_id();
		}

		$counter = 0;
		while ($counter++ < $vbulletin->GPC['opts'])
		{
			$vbulletin->GPC['options']["$counter"]['title'] = fetch_censored_text($vbulletin->GPC['options']["$counter"]['title']);

			if ($vbulletin->GPC['eventid'])
			{
				if (!empty($vbulletin->GPC['options']["$counter"]['title']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_against']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_for']))
				{
					$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_option SET title = '" . $vbulletin->db->escape_string($vbulletin->GPC['options']["$counter"]['title']) . "', odds_against = " . $vbulletin->GPC['options']["$counter"]['odds_against'] . ", odds_for = " . $vbulletin->GPC['options']["$counter"]['odds_for'] . " WHERE optionid = " . $vbulletin->GPC['options']["$counter"]['optionid']);
				}
			}
			else
			{
				if (!empty($vbulletin->GPC['options']["$counter"]['title']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_against']) AND !empty($vbulletin->GPC['options']["$counter"]['odds_for']))
				{
					$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_option (eventid, title, odds_against, odds_for) VALUES (" . $varexist['event']['eventid'] . ", '" . $vbulletin->db->escape_string($vbulletin->GPC['options']["$counter"]['title']) . "', " . $vbulletin->GPC['options']["$counter"]['odds_against'] . ", " . $vbulletin->GPC['options']["$counter"]['odds_for'] . ")");
				}
			}
		}

		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('" . ($vbulletin->GPC['eventid'] ? 'eventedit' : 'eventadd') . "', " . $varexist['event']['eventid'] . ", " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");

		// update thread
		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
		$threadman->set_existing($threadinfo);
		$threadman->set('phpkd_vbabc_eventid', $varexist['event']['eventid']);
		$threadman->save();

		// update last post icon (if necessary)
		cache_ordered_forums(1);

		if ($vbulletin->forumcache["$threadinfo[forumid]"]['lastthreadid'] == $threadinfo['threadid'])
		{
			$forumdm =& datamanager_init('Forum', $vbulletin, ERRTYPE_SILENT);
			$forumdm->set_existing($vbulletin->forumcache["$threadinfo[forumid]"]);
			$forumdm->set('lasticonid', '-1');
			$forumdm->save();
			unset($forumdm);
		}

		// redirect
		if ($threadinfo['visible'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
		{
			$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		}
		else
		{
			$vbulletin->url = fetch_seo_url('forum', $foruminfo);
		}


		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
		{
			eval(print_standard_redirect('redirect_postthanks_nopermission'));
		}
		else
		{
			eval(print_standard_redirect('redirect_postthanks'));
		}
	}
}

// ############################### start new bookie event ###############################
if ($_REQUEST['do'] == 'eventadd' OR $_REQUEST['do'] == 'eventedit')
{
	if ($_REQUEST['do'] == 'eventedit')
	{
		if (!$phpkd_vbabc_eventinfo['eventid'])
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
		}

		$phpkd_vbabc_opts = $vbulletin->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "phpkd_vbabc_option
			WHERE eventid = $phpkd_vbabc_eventinfo[eventid]
		");

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND ($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canedit'])))
		{
			print_no_permission();
		}

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND $vbulletin->options['phpkd_vbabc_timelimitedit'] AND TIMENOW - ($vbulletin->options['phpkd_vbabc_timelimitedit'] * 60) > $phpkd_vbabc_eventinfo['dateline'])
		{
			eval(standard_error(fetch_error('phpkd_vbabc_timelimitedit', $vbulletin->options['phpkd_vbabc_timelimitedit'])));
		}

		$timeout['date'] = array(
			'year'  => vbdate('Y', $phpkd_vbabc_eventinfo['timeout'], false, false),
			'month' => vbdate('n', $phpkd_vbabc_eventinfo['timeout'], false, false),
			'day'   => vbdate('j', $phpkd_vbabc_eventinfo['timeout'], false, false),
			'time'  => vbdate('H', $phpkd_vbabc_eventinfo['timeout'], false, false) . '.' . (vbdate('i', $phpkd_vbabc_eventinfo['timeout'], false, false) > 0 ? '30' : '00'),
		);

		$payafter['date'] = array(
			'year'  => vbdate('Y', $phpkd_vbabc_eventinfo['payafter'], false, false),
			'month' => vbdate('n', $phpkd_vbabc_eventinfo['payafter'], false, false),
			'day'   => vbdate('j', $phpkd_vbabc_eventinfo['payafter'], false, false),
			'time'  => vbdate('H', $phpkd_vbabc_eventinfo['payafter'], false, false) . '.' . (vbdate('i', $phpkd_vbabc_eventinfo['payafter'], false, false) > 0 ? '30' : '00'),
		);

		$phpkd_vbabc_eventinfo['title'] = ($vbulletin->GPC['title'] ? $vbulletin->GPC['title'] : $phpkd_vbabc_eventinfo['title']);
		$phpkd_vbabc_eventinfo['group'] = ($vbulletin->GPC['group'] ? $vbulletin->GPC['group'] : $phpkd_vbabc_eventinfo['grouptitle']);
		$phpkd_vbabc_eventinfo['chalengedcount'] = ($vbulletin->GPC['chalengedcount'] ? $vbulletin->GPC['chalengedcount'] : $phpkd_vbabc_eventinfo['chalengedcount']);
		$phpkd_vbabc_eventinfo['chalengeduser'] = ($vbulletin->GPC['userid'] ? $vbulletin->GPC['userid'] : $phpkd_vbabc_eventinfo['chalengeduser']);
		$phpkd_vbabc_eventinfo['opts'] = ($vbulletin->GPC['opts'] ? $vbulletin->GPC['opts'] : $vbulletin->db->num_rows($phpkd_vbabc_opts));
		$phpkd_vbabc_eventinfo['pagetitle'] = $vbphrase['phpkd_vbabc_eventedit'];

		if (!isset($checked['parseurl']))
		{
			$checked['parseurl'] = 'checked="checked"';
		}

		if ($phpkd_vbabc_eventinfo['multiple'] AND !isset($checked['multiple']))
		{
			$checked['multiple'] = 'checked="checked"';
		}

		if ($phpkd_vbabc_eventinfo['public'] AND !isset($checked['public']))
		{
			$checked['public'] = 'checked="checked"';
		}
	}
	else
	{
		$vbulletin->input->clean_gpc('r', 'opts', TYPE_UINT);

		if ($threadinfo['phpkd_vbabc_eventid'])
		{
			eval(standard_error(fetch_error('phpkd_vbabc_already')));
		}

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND ($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canadd'])))
		{
			print_no_permission();
		}

		if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND $vbulletin->options['phpkd_vbabc_timelimitadd'] AND TIMENOW - ($vbulletin->options['phpkd_vbabc_timelimitadd'] * 60) > $threadinfo['dateline'])
		{
			eval(standard_error(fetch_error('phpkd_vbabc_timelimitadd', $vbulletin->options['phpkd_vbabc_timelimitadd'])));
		}

		$timeout['date'] = array(
			'year'  => vbdate('Y', TIMENOW, false, false),
			'month' => vbdate('n', TIMENOW, false, false),
			'day'   => vbdate('j', TIMENOW, false, false),
			'time'  => vbdate('H', TIMENOW, false, false) . '.' . (vbdate('i', TIMENOW, false, false) > 0 ? '30' : '00'),
		);

		$payafter['date'] = array(
			'year'  => vbdate('Y', TIMENOW, false, false),
			'month' => vbdate('n', TIMENOW, false, false),
			'day'   => vbdate('j', TIMENOW, false, false),
			'time'  => vbdate('H', TIMENOW, false, false) . '.' . (vbdate('i', TIMENOW, false, false) > 0 ? '30' : '00'),
		);

		$phpkd_vbabc_eventinfo = array(
			'title'          => $vbulletin->GPC['title'],
			'group'          => $vbulletin->GPC['group'],
			'opts'           => $vbulletin->GPC['opts'],
			'pagetitle'      => $vbphrase['phpkd_vbabc_eventadd'],
			'chalengedcount' => $vbulletin->GPC['chalengedcount'],
			'chalengeduser'  => $vbulletin->GPC['userid'],
		);

		if (!isset($checked['parseurl']))
		{
			$checked['parseurl'] = 'checked="checked"';
		}

		if (!isset($checked['public']))
		{
			$checked['public'] = 'checked="checked"';
		}
	}

	$show['canmoderate'] = iif($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate'], true, false);

	if ($phpkd_vbabc_eventinfo['chalengeduser'])
	{
		$userinfo = verify_id('user', $phpkd_vbabc_eventinfo['chalengeduser'], 0, 1);
	}

	// Stop there being too many
	if ($vbulletin->options['phpkd_vbabc_maxopts'] > 0 AND $phpkd_vbabc_eventinfo['opts'] > $vbulletin->options['phpkd_vbabc_maxopts'])
	{
		$phpkd_vbabc_eventinfo['opts'] = $vbulletin->options['phpkd_vbabc_maxopts'];
	}

	// Stop there being too few
	if ($phpkd_vbabc_eventinfo['opts'] <= 1)
	{
		$phpkd_vbabc_eventinfo['opts'] = 2;
	}

	for ($gyear = $phpkd_vbabc_daterange[0]; $gyear <= $phpkd_vbabc_daterange[1]; $gyear++)
	{
		$timeout['arr']['years'] .= render_option_template($gyear, $gyear, ($gyear == $vbulletin->GPC['timeout']['year']) ? 'selected="selected"' : ($gyear == $timeout['date']['year'] ? 'selected="selected"' : ''));
		$payafter['arr']['years'] .= render_option_template($gyear, $gyear, ($gyear == $vbulletin->GPC['payafter']['year']) ? 'selected="selected"' : ($gyear == $payafter['date']['year'] ? 'selected="selected"' : ''));
	}

	foreach ($months as $gmonthid => $gmonth)
	{
		$timeout['arr']['months'] .= render_option_template(ucfirst($gmonth), $gmonthid, ($gmonthid == $vbulletin->GPC['timeout']['month']) ? 'selected="selected"' : ($gmonthid == $timeout['date']['month'] ? 'selected="selected"' : ''));
		$payafter['arr']['months'] .= render_option_template(ucfirst($gmonth), $gmonthid, ($gmonthid == $vbulletin->GPC['payafter']['month']) ? 'selected="selected"' : ($gmonthid == $payafter['date']['month'] ? 'selected="selected"' : ''));
	}

	for ($gday = 1; $gday <= 31; $gday++)
	{
		$timeout['arr']['days'] .= render_option_template($gday, $gday, ($gday == $vbulletin->GPC['timeout']['day']) ? 'selected="selected"' : ($gday == $timeout['date']['day'] ? 'selected="selected"' : ''));
		$payafter['arr']['days'] .= render_option_template($gday, $gday, ($gday == $vbulletin->GPC['payafter']['day']) ? 'selected="selected"' : ($gday == $payafter['date']['day'] ? 'selected="selected"' : ''));
	}

	foreach ($timelist as $timeitemid => $timeitem)
	{
		$timeout['arr']['times'] .= render_option_template($timeitem, $timeitemid, ($timeitemid == $vbulletin->GPC['timeout']['time']) ? 'selected="selected"' : ($timeitemid == $timeout['date']['time'] ? 'selected="selected"' : ''));
		$payafter['arr']['times'] .= render_option_template($timeitem, $timeitemid, ($timeitemid == $vbulletin->GPC['payafter']['time']) ? 'selected="selected"' : ($timeitemid == $payafter['date']['time'] ? 'selected="selected"' : ''));
	}

	/*
	// select correct timezone and build timezone options
	require_once(DIR . '/includes/functions_misc.php');
	foreach (fetch_timezone() AS $timezonevalue => $timezonephrase)
	{
		$timeout['arr']['zones'] .= render_option_template($vbphrase["$timezonephrase"], $timezonevalue, ($timezonevalue == $vbulletin->GPC['timeout']['zone']) ? 'selected="selected"' : '');
		$payafter['arr']['zones'] .= render_option_template($vbphrase["$timezonephrase"], $timezonevalue, ($timezonevalue == $vbulletin->GPC['payafter']['zone']) ? 'selected="selected"' : '');
	}
	*/


	$counter = 0;
	$option = array();
	while ($counter++ < $phpkd_vbabc_eventinfo['opts'])
	{
		$option['number'] = $counter;

		if (is_array($vbulletin->GPC['options']))
		{
			$option['optionid']     = $vbulletin->GPC['options']["$counter"]['optionid'];
			$option['title']        = $vbulletin->GPC['options']["$counter"]['title'];
			$option['odds_against'] = $vbulletin->GPC['options']["$counter"]['odds_against'];
			$option['odds_for']     = $vbulletin->GPC['options']["$counter"]['odds_for'];
		}
		else if ($_REQUEST['do'] == 'eventedit' AND $phpkd_vbabc_opt = $vbulletin->db->fetch_array($phpkd_vbabc_opts))
		{
			$option['optionid']     = $phpkd_vbabc_opt['optionid'];
			$option['title']        = htmlspecialchars_uni($phpkd_vbabc_opt['title']);
			$option['odds_against'] = $phpkd_vbabc_opt['odds_against'];
			$option['odds_for']     = $phpkd_vbabc_opt['odds_for'];
		}

		$templater = vB_Template::create('phpkd_vbabc_option');
			$templater->register('option', $option);
		$eventoptions .= $templater->render();
	}


	// draw nav bar
	$navbits = $phpkd_vbabc->getDmhandle()->construct_nav($foruminfo, $threadinfo);
	$navbar = render_navbar_template($navbits);

	require_once(DIR . '/includes/functions_bigthree.php');
	construct_forum_rules($foruminfo, $forumperms);

	$show['parseurl'] = $foruminfo['allowbbcode'];

	$templater = vB_Template::create('phpkd_vbabc_event');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('forumrules', $forumrules);
		$templater->register('navbar', $navbar);
		$templater->register('eventoptions', $eventoptions);
		$templater->register('eventpreview', $eventpreview);
		$templater->register('eventinfo', $phpkd_vbabc_eventinfo);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('userinfo', $userinfo);
		$templater->register('timeout', $timeout['arr']);
		$templater->register('payafter', $payafter['arr']);
	print_output($templater->render());
}

// ############################### start bet on bookie event ###############################
if ($_POST['do'] == 'eventbet')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'stake'      => TYPE_ARRAY_ARRAY,
		'privatebet' => TYPE_BOOL,
	));

	if (!$phpkd_vbabc_eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
	}

	if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canbet']))
	{
		print_no_permission();
	}

	// Check if bookie event is open or not
	if ($phpkd_vbabc_eventinfo['status'] != 'open')
	{
		eval(standard_error(fetch_error('phpkd_vbabc_eventnotopen')));
	}


	// Check if an option was selected
	if (!empty($vbulletin->GPC['stake']))
	{
		$stakes = array();
		foreach ($vbulletin->GPC['stake'] as $key => $value)
		{
			$vbulletin->input->clean($value, TYPE_ARRAY_UINT);

			if (!empty($value['amount']))
			{
				$stakes["$key"] = $value;
			}
		}

		if (empty($stakes))
		{
			eval(standard_error(fetch_error('phpkd_vbabc_invalidbet')));
		}

		$betsql = '';
		$totalstaked = 0;
		$update_ids = '0';
		$betstake = '';
		$betadd = '';
		foreach ($stakes AS $option => $stake)
		{
			$update_ids .= ",$option";
			$totalstaked += $stake['amount'];
			$betsql .= '(' . intval($option) . ', ' . $phpkd_vbabc_eventinfo['eventid'] . ', ' . $vbulletin->userinfo['userid'] . ', ' . TIMENOW . ', ' . $stake['amount'] . ', ' . $stake['odds_against'] . ', ' . $stake['odds_for'] . ', ' . iif($vbulletin->GPC['privatebet'] AND (($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) OR ($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canprivatebet'])), 1, 0) . '), ';
			$betstake .= " WHEN $option THEN staked + " . $stake['amount'];
			$betadd .= " WHEN $option THEN bets + 1";
		}

		if ($phpkd_vbabc->getDmhandle()->cashbalance() < $totalstaked)
		{
			eval(standard_error(fetch_error('phpkd_vbabc_noenoughcash')));
		}

		if (!$vbulletin->userinfo['userid'])
		{
			$betted = intval(fetch_bbarray_cookie('phpkd_vbabc_betted', $phpkd_vbabc_eventinfo['eventid']));
			if ($betted)
			{
				// The user has betted before
				eval(standard_error(fetch_error('phpkd_vbabc_betted')));
			}
			else
			{
				set_bbarray_cookie('phpkd_vbabc_betted', $phpkd_vbabc_eventinfo['eventid'], 1, 1);
			}
		}
		else if ($userbet = $vbulletin->db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "phpkd_vbabc_bet
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND eventid = $phpkd_vbabc_eventinfo[eventid]
		") AND !$phpkd_vbabc_eventinfo['multiple'])
		{
			// The user has betted before
			eval(standard_error(fetch_error('phpkd_vbabc_betted')));
		}
		else if (count($stakes) > 1 AND !$phpkd_vbabc_eventinfo['multiple'])
		{
			// Multiple bets not allowed, only one!
			eval(standard_error(fetch_error('phpkd_vbabc_onlyonebet')));
		}
		else if ($usersbets = $vbulletin->db->query_first("
			SELECT COUNT(DISTINCT userid) AS count
			FROM " . TABLE_PREFIX . "phpkd_vbabc_bet
			WHERE eventid = $phpkd_vbabc_eventinfo[eventid]
		") AND $phpkd_vbabc_eventinfo['chalengedcount'] AND $usersbets['count'] >= $phpkd_vbabc_eventinfo['chalengedcount'])
		{
			// Maximum betters reached, no more betting allowed!
			eval(standard_error(fetch_error('phpkd_vbabc_maximumbetters')));
		}
		else if ($phpkd_vbabc_eventinfo['chalengeduser'] AND $vbulletin->userinfo['userid'] != $phpkd_vbabc_eventinfo['chalengeduser'])
		{
			// Only one specific user allowed to bet, it's not you!
			eval(standard_error(fetch_error('phpkd_vbabc_notyou')));
		}

		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_bet (optionid, eventid, userid, dateline, amount, odds_against, odds_for, private) VALUES " . substr($betsql, 0, -2));
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_option SET bets = CASE optionid $betadd ELSE bets END, staked = CASE optionid $betstake ELSE staked END WHERE optionid IN ($update_ids)");
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET bets = bets + " . count($stakes) . ", staked = staked + " . $totalstaked . " WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);
		$phpkd_vbabc->getDmhandle()->cashtake(array($vbulletin->userinfo['userid'] => $totalstaked));

		// Make last reply date == last vote date
		if ($vbulletin->options['phpkd_vbabc_updatelastpost'])
		{
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->set('lastpost', TIMENOW);
			$threadman->save();
		}

		// Redirect
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		eval(print_standard_redirect('redirect_phpkd_vbabc_betthanks'));
	}
	else
	{
		eval(standard_error(fetch_error('phpkd_vbabc_invalidbet')));
	}
}

// ############################### start settle a bookie event ###############################
if ($_POST['do'] == 'eventsettle')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'settle'    => TYPE_ARRAY_BOOL,
		'nowinners' => TYPE_BOOL,
	));

	if (!$phpkd_vbabc_eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
	}

	if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']) AND !($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['cansettle']))
	{
		print_no_permission();
	}

	// Check if bookie event is closed or not
	if ($phpkd_vbabc_eventinfo['status'] != 'closed')
	{
		eval(standard_error(fetch_error('phpkd_vbabc_eventnotclosed')));
	}

	// Check if an option was selected
	if (!empty($vbulletin->GPC['nowinners']))
	{
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_bet SET settled = 1 WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET status = 'settled' WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);
		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventsettle', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");

		// Redirect
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		eval(print_standard_redirect('redirect_phpkd_vbabc_eventsettle'));
	}
	else if (!empty($vbulletin->GPC['settle']))
	{
		$settles = array();
		foreach ($vbulletin->GPC['settle'] as $key => $value)
		{
			if (!empty($value))
			{
				$settles["$key"] = $key;
			}
		}

		if (empty($settles))
		{
			eval(standard_error(fetch_error('phpkd_vbabc_invalidsettle')));
		}

		$betsql = $vbulletin->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "phpkd_vbabc_bet AS bet
			WHERE optionid IN (" . implode(',', array_keys($settles)) . ")
			ORDER BY optionid ASC
		");

		if ($vbulletin->db->num_rows($betsql))
		{
			$winningbets = '';
			$totalwon_option = array();
			$totalwon_event = array();
			$givenusers = array();
			$update_betids = '0';
			while ($bet = $vbulletin->db->fetch_array($betsql))
			{
				$bet['amount_won']  = (($bet['amount'] * $bet['odds_against']) / $bet['odds_for']);
				$bet['amount_paid'] = $bet['amount_won'] + $bet['amount'];
				$winningbets .= " WHEN $bet[betid] THEN " . $bet['amount_won'];
				$totalwon_option["$bet[optionid]"] += $bet['amount_won'];
				$totalwon_event["$bet[eventid]"] += $bet['amount_won'];
				$givenusers["$bet[userid]"] += $bet['amount_paid'];
				$update_betids .= ",$bet[betid]";
			}

			$winningopts = '';
			foreach ($settles AS $option)
			{
				$winningopts .= " WHEN $option THEN won + " . $totalwon_option["$option"];
			}

			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_bet SET settled = 1 WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);
			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_bet SET won = CASE betid $winningbets ELSE won END WHERE betid IN ($update_betids)");
			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_option SET won = CASE optionid $winningopts ELSE won END WHERE optionid IN (0," . implode(',', $settles) . ")");
			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET won = " . $totalwon_event["$phpkd_vbabc_eventinfo[eventid]"] . " WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);

			$phpkd_vbabc->getDmhandle()->cashgive($givenusers);
			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline, won) VALUES ('winner', $phpkd_vbabc_eventinfo[eventid], " . max(array_keys($givenusers)) . ", " . TIMENOW . ", " . max($givenusers) . ")");
		}

		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_option SET pays = 1 WHERE optionid IN (0," . implode(',', $settles) . ")");
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET status = 'settled' WHERE eventid = " . $phpkd_vbabc_eventinfo['eventid']);
		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventsettle', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");

		// Make last reply date == last vote date
		if ($vbulletin->options['phpkd_vbabc_updatelastpost'])
		{
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->set('lastpost', TIMENOW);
			$threadman->save();
		}

		// Redirect
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		eval(print_standard_redirect('redirect_phpkd_vbabc_eventsettle'));
	}
	else
	{
		eval(standard_error(fetch_error('phpkd_vbabc_invalidsettle')));
	}
}

// ############################### start settle a bookie event ###############################
if ($_REQUEST['do'] == 'eventdelete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'confirm' => TYPE_BOOL,
	));

	if (!$phpkd_vbabc_eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
	}

	if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']))
	{
		print_no_permission();
	}

	// This action should be confirmed first!
	if (!$vbulletin->GPC['confirm'])
	{
		eval(standard_error(fetch_error('phpkd_vbabc_shouldconfirm')));
	}

	$phpkd_vbabc->getDmhandle()->eventdelete($phpkd_vbabc_eventinfo);

	$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventdelete', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");

	// Redirect
	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	eval(print_standard_redirect('redirect_phpkd_vbabc_eventdelete'));
}

// ############################### start settle a bookie event ###############################
if ($_REQUEST['do'] == 'eventabandon')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'confirm' => TYPE_BOOL,
	));

	if (!$phpkd_vbabc_eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
	}

	if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']))
	{
		print_no_permission();
	}

	// This action should be confirmed first!
	if (!$vbulletin->GPC['confirm'])
	{
		eval(standard_error(fetch_error('phpkd_vbabc_shouldconfirm')));
	}

	$phpkd_vbabc->getDmhandle()->eventabandon($phpkd_vbabc_eventinfo);

	$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventabandon', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");

	// Redirect
	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	eval(print_standard_redirect('redirect_phpkd_vbabc_eventabandon'));
}

// ############################### start settle a bookie event ###############################
if ($_REQUEST['do'] == 'eventopenclose')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'confirm' => TYPE_BOOL,
	));

	if (!$phpkd_vbabc_eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['phpkd_vbabc_event'], $vbulletin->options['contactuslink'])));
	}

	if (!($vbulletin->userinfo['permissions']['phpkd_vbabc'] & $vbulletin->bf_ugp_phpkd_vbabc['canmoderate']))
	{
		print_no_permission();
	}

	// This action should be confirmed first!
	if (!$vbulletin->GPC['confirm'])
	{
		eval(standard_error(fetch_error('phpkd_vbabc_shouldconfirm')));
	}

	if (!$phpkd_vbabc_eventinfo['mstatus'])
	{
		if ($phpkd_vbabc_eventinfo['status'] == 'open')
		{
			$action = $vbphrase['closed'];
			$phpkd_vbabc->getDmhandle()->eventclose($phpkd_vbabc_eventinfo);
			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventclose', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");
		}
		else if ($phpkd_vbabc_eventinfo['status'] == 'closed')
		{
			$action = $vbphrase['opened'];
			$phpkd_vbabc->getDmhandle()->eventopen($phpkd_vbabc_eventinfo);
			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventopen', $phpkd_vbabc_eventinfo[eventid], " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");
		}
	}

	// Redirect
	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	eval(print_standard_redirect('redirect_phpkd_vbabc_eventopenclose'));
}

?>