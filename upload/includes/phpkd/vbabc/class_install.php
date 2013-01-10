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


if (!defined('VB_AREA') OR !defined('IN_CONTROL_PANEL'))
{
	echo 'Can not be called from outside vBulletin Framework AdminCP!';
	exit;
}


/**
 * Core class
 *
 * @category	vB Automated Bookie Center 'Ultimate'
 * @package		PHPKD_VBABC
 * @subpackage	PHPKD_VBABC_Install
 * @copyright	Copyright ©2005-2013 PHP KingDom. All Rights Reserved. (http://www.phpkd.net)
 * @license		http://info.phpkd.net/en/license/commercial
 */
class PHPKD_VBABC_Install
{
	/**
	 * The vBulletin registry object
	 *
	 * @var	vB_Registry
	 */
	public $_vbulletin = null;

	/**
	 * Constructor - checks that vBulletin registry object has been passed correctly, and initialize requirements.
	 *
	 * @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its members ($this->db).
	 * @return	PHPKD_VBABC_Install
	 */
	public function __construct(&$registry)
	{
		if (is_object($registry))
		{
			$this->_vbulletin =& $registry;

			if (!is_object($registry->db))
			{
				trigger_error('vBulletin Database object is not an object!', E_USER_ERROR);
			}
		}
		else
		{
			trigger_error('vBulletin Registry object is not an object!', E_USER_ERROR);
		}

		return $this;
	}

	/**
	 * Initialize installation process
	 *
	 * @param	array		Array of product's info
	 * @return	void
	 */
	public function install_init($info)
	{
		if (!file_exists(DIR . '/includes/phpkd/vbabc/class_core.php') OR !file_exists(DIR . '/includes/phpkd/vbabc/class_dm.php') OR !file_exists(DIR . '/includes/phpkd/vbabc/class_hooks.php') OR !file_exists(DIR . '/includes/xml/bitfield_phpkd_vbabc.xml') OR !file_exists(DIR . '/includes/xml/cpnav_phpkd_vbabc.xml'))
		{
			print_dots_stop();
			print_cp_message('Please upload the files that came with "PHPKD - vB Automated Bookie Center" product before installing or upgrading!');
		}

		$this->_vbulletin->db->hide_errors();

		// ######################################################################
		// ## Debug Stuff: Begin                                               ##
		// ######################################################################

		// Import debug data in appropriate field
		$phpkdinfo['title'] = $info['title'];
		$phpkdinfo['version'] = $info['version'];
		$phpkdinfo['revision'] = trim(substr(substr('$Revision$', 10), 0, -1));
		$phpkdinfo['released'] = trim(substr(substr('$Date$', 6), 0, -1));
		$phpkdinfo['installdateline'] = TIMENOW;
		$phpkdinfo['author'] = trim(substr(substr('$Author$', 8), 0, -1));
		$phpkdinfo['vendor'] = trim(substr(substr('$Vendor: PHP KingDom $', 8), 0, -1));
		$phpkdinfo['url'] = $info['url'];
		$phpkdinfo['versioncheckurl'] = $info['versioncheckurl'];

		if ($this->_vbulletin->options['phpkd_commercial41_data'])
		{
			$holder = unserialize($this->_vbulletin->options['phpkd_commercial41_data']);
			$holder[$phpkdinfo['productid']] = $phpkdinfo;
			$data = $this->_vbulletin->db->escape_string(serialize($holder));
			$this->_vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "setting
				SET value = '$data'
				WHERE varname = 'phpkd_commercial41_data'
			");
		}
		else
		{
			$holder[$phpkdinfo['productid']] = $phpkdinfo;
			$data = $this->_vbulletin->db->escape_string(serialize($holder));

			$this->_vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "setting
					(varname, grouptitle, value, defaultvalue, datatype, optioncode, displayorder, advanced, volatile, validationcode, blacklist, product)
				VALUES
					('phpkd_commercial41_data', 'version', '$data', '', 'free', '', '41100', '0', '1', '', '0', 'phpkd_framework')
			");

			$this->_vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "phrase
					(languageid, fieldname, varname, text, product, username, dateline, version)
				VALUES
					('-1', 'vbsettings', 'setting_phpkd_commercial41_data_title', 'PHP KingDom (PHPKD) Commercial Products\' Data (4.1.x) [Sensitive]', 'phpkd_framework', '" . $this->_vbulletin->db->escape_string($this->_vbulletin->userinfo['username']) . "', " . TIMENOW . ", '4.1.100'),
					('-1', 'vbsettings', 'setting_phpkd_commercial41_data_desc', 'PHP KingDom (PHPKD) Commercial Products\' Data used for debugging purposes. <strong>[Sensitive Data, DON\'T ALTER]</strong>.', 'phpkd_framework', '" . $this->_vbulletin->db->escape_string($this->_vbulletin->userinfo['username']) . "', " . TIMENOW . ", '4.1.100')
				");
		}

		build_options();
		print_dots_start("Installing: \"" . $phpkdinfo['title'] . "\"<br />Version: " . $phpkdinfo['version'] . ", Revision: " . $phpkdinfo['revision'] . ", Released: " . $phpkdinfo['released'] . ".<br />Thanks for choosing PHP KingDom's Products. If you need any help or wish to try any other products we have, just give us a visit at <a href=\"http://www.phpkd.net\" target=\"_blank\">www.phpkd.net</a>. You are always welcomed.<br />Please Wait...", ':', 'phpkd_vbaddon_install_info');
		print_dots_stop();

		// ######################################################################
		// ## Debug Stuff: End                                                 ##
		// ######################################################################

		$this->_vbulletin->db->show_errors();
	}

	/**
	 * Initialize uninstallation
	 *
	 * @return	void
	 */
	public function uninstall_init()
	{
		$this->_vbulletin->db->hide_errors();

		// ######################################################################
		// ## Debug Stuff: Begin                                               ##
		// ######################################################################

		if ($this->_vbulletin->options['phpkd_commercial41_data'])
		{
			$holder = unserialize($this->_vbulletin->options['phpkd_commercial41_data']);

			if ($holder[$this->_vbulletin->db->escape_string($this->_vbulletin->GPC['productid'])])
			{
				$phpkdinfo = $holder[$this->_vbulletin->db->escape_string($this->_vbulletin->GPC['productid'])];
				print_dots_start("Un-installing: \"" . $phpkdinfo['title'] . "\"<br />Version: " . $phpkdinfo['version'] . ", Revision: " . $phpkdinfo['revision'] . ", Released: " . $phpkdinfo['released'] . ".<br />We are sad to see you un-installing '" . $phpkdinfo['title'] . "'. Please if there is any thing we can do to keep you using this software product, just tell us at <a href=\"http://www.phpkd.net\" target=\"_blank\">www.phpkd.net</a>.<br />Please Wait...", ':', 'phpkd_vbaddon_uninstall_info');
				unset($holder[$this->_vbulletin->db->escape_string($this->_vbulletin->GPC['productid'])]);
			}

			if (is_array($holder) AND !empty($holder))
			{
				$data = $this->_vbulletin->db->escape_string(serialize($holder));
				$this->_vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "setting SET
					value = '$data'
					WHERE varname = 'phpkd_commercial41_data'
				");
			}
			else
			{
				// delete phrases
				$this->_vbulletin->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "phrase
					WHERE languageid IN (-1, 0) AND
						fieldname = 'vbsettings' AND
						varname IN ('setting_phpkd_commercial41_data_title', 'setting_phpkd_commercial41_data_desc')
				");

				// delete setting
				$this->_vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "setting WHERE varname = 'phpkd_commercial41_data'");
			}
		}

		build_options();

		// ######################################################################
		// ## Debug Stuff: End                                                 ##
		// ######################################################################

		$this->_vbulletin->db->show_errors();
	}

	/**
	 * Install v4.1.100
	 *
	 * @return	void
	 */
	public function install_41100()
	{
		$this->_vbulletin->db->hide_errors();
		require_once(DIR . '/includes/class_dbalter.php');
		$db_alter = new vB_Database_Alter_MySQL($this->_vbulletin->db);

		if ($db_alter->fetch_table_info('administrator'))
		{
			$db_alter->add_field(array(
				'name'       => 'phpkd_vbabc',
				'type'       => 'int',
				'attributes' => 'unsigned',
				'default'    => 0
			));
		}

		if ($db_alter->fetch_table_info('forum'))
		{
			$db_alter->add_field(array(
				'name'       => 'phpkd_vbabc',
				'type'       => 'int',
				'attributes' => 'unsigned',
				'default'    => 0
			));
		}

		if ($db_alter->fetch_table_info('thread'))
		{
			$db_alter->add_field(array(
				'name'       => 'phpkd_vbabc_eventid',
				'type'       => 'int',
				'attributes' => 'unsigned',
				'default'    => 0
			));
		}

		if ($db_alter->fetch_table_info('user'))
		{
			$db_alter->add_field(array(
				'name'       => 'phpkd_vbabc',
				'type'       => 'int',
				'attributes' => 'unsigned',
				'default'    => 0
			));
		}

		if ($db_alter->fetch_table_info('usergroup'))
		{
			$db_alter->add_field(array(
				'name'       => 'phpkd_vbabc',
				'type'       => 'int',
				'attributes' => 'unsigned',
				'default'    => 0
			));
		}

		$this->_vbulletin->db->query_write("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "phpkd_vbabc_bet`
			(
				`betid` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`optionid` int(10) unsigned NOT NULL DEFAULT '0',
				`eventid` int(10) unsigned NOT NULL DEFAULT '0',
				`userid` int(10) unsigned NOT NULL DEFAULT '0',
				`dateline` int(10) unsigned NOT NULL DEFAULT '0',
				`amount` int(10) unsigned NOT NULL DEFAULT '1',
				`odds_against` int(10) unsigned NOT NULL DEFAULT '0',
				`odds_for` int(10) unsigned NOT NULL DEFAULT '0',
				`won` int(10) unsigned NOT NULL DEFAULT '0',
				`private` smallint(5) NOT NULL DEFAULT '0',
				`settled` smallint(5) NOT NULL DEFAULT '0',
				PRIMARY KEY (`betid`)
			)
		");

		$this->_vbulletin->db->query_write("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "phpkd_vbabc_event`
			(
				`eventid` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`groupid` int(10) unsigned NOT NULL DEFAULT '0',
				`threadid` int(10) unsigned NOT NULL DEFAULT '0',
				`userid` int(10) unsigned NOT NULL DEFAULT '0',
				`title` varchar(250) NOT NULL DEFAULT '',
				`dateline` int(10) unsigned NOT NULL DEFAULT '0',
				`timeout` int(10) unsigned NOT NULL DEFAULT '0',
				`payafter` int(10) unsigned NOT NULL DEFAULT '0',
				`status` enum('open','closed','settled','abandoned') NOT NULL DEFAULT 'open',
				`public` smallint(5) NOT NULL DEFAULT '1',
				`multiple` smallint(5) NOT NULL DEFAULT '0',
				`chalengedcount` int(10) unsigned NOT NULL DEFAULT '0',
				`chalengeduser` int(10) unsigned NOT NULL DEFAULT '0',
				`bets` int(10) unsigned NOT NULL DEFAULT '0',
				`staked` int(10) unsigned NOT NULL DEFAULT '0',
				`won` int(10) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`eventid`)
			)
		");

		$this->_vbulletin->db->query_write("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "phpkd_vbabc_group`
			(
				`groupid` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`title` varchar(250) NOT NULL,
				PRIMARY KEY (`groupid`),
				UNIQUE KEY `title` (`title`)
			)
		");

		$this->_vbulletin->db->query_write("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "phpkd_vbabc_news`
			(
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`type` enum('eventadd','eventedit','eventsettle','eventdelete','eventabandon','eventopen','eventclose','winner') DEFAULT 'eventadd',
				`eventid` int(10) unsigned NOT NULL DEFAULT '0',
				`userid` int(10) unsigned NOT NULL DEFAULT '0',
				`dateline` int(10) unsigned NOT NULL DEFAULT '0',
				`won` int(10) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			)
		");

		$this->_vbulletin->db->query_write("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "phpkd_vbabc_option`
			(
				`optionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`eventid` int(10) unsigned NOT NULL DEFAULT '0',
				`title` varchar(250) NOT NULL DEFAULT '',
				`odds_against` int(10) unsigned NOT NULL DEFAULT '0',
				`odds_for` int(10) unsigned NOT NULL DEFAULT '0',
				`pays` smallint(5) NOT NULL DEFAULT '0',
				`bets` int(10) unsigned NOT NULL DEFAULT '0',
				`staked` int(10) unsigned NOT NULL DEFAULT '0',
				`won` int(10) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`optionid`)
			)
		");

		$this->_vbulletin->db->show_errors();
	}

	/**
	 * Uninstall v4.1.100
	 *
	 * @return	void
	 */
	function uninstall_41100()
	{
		$this->_vbulletin->db->hide_errors();
		require_once(DIR . '/includes/class_dbalter.php');
		$db_alter = new vB_Database_Alter_MySQL($this->_vbulletin->db);

		if ($db_alter->fetch_table_info('administrator'))
		{
			$db_alter->drop_field('phpkd_vbabc');
		}

		if ($db_alter->fetch_table_info('forum'))
		{
			$db_alter->drop_field('phpkd_vbabc');
		}

		if ($db_alter->fetch_table_info('thread'))
		{
			$db_alter->drop_field('phpkd_vbabc_eventid');
		}

		if ($db_alter->fetch_table_info('user'))
		{
			$db_alter->drop_field('phpkd_vbabc');
		}

		if ($db_alter->fetch_table_info('usergroup'))
		{
			$db_alter->drop_field('phpkd_vbabc');
		}

		$this->_vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "phpkd_vbabc_bet");
		$this->_vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "phpkd_vbabc_event");
		$this->_vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "phpkd_vbabc_group");
		$this->_vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "phpkd_vbabc_news");
		$this->_vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "phpkd_vbabc_option");

		$this->_vbulletin->db->show_errors();
	}
}
