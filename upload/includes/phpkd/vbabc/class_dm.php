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


// No direct access! Should be accessed throuth the core class only!!
if (!defined('VB_AREA') OR !defined('PHPKD_VBABC') OR @get_class($this) != 'PHPKD_VBABC')
{
	echo 'Prohibited Access!';
	exit;
}


/**
 * Data Manager class
 *
 * @category	vB Automated Bookie Center 'Ultimate'
 * @package		PHPKD_VBABC
 * @subpackage	PHPKD_VBABC_DM
 * @copyright	Copyright ©2005-2013 PHP KingDom. All Rights Reserved. (http://www.phpkd.net)
 * @license		http://info.phpkd.net/en/license/commercial
 */
class PHPKD_VBABC_DM
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
	 * @return	PHPKD_VBABC
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

		return $this;
	}

	/**
	 * @param	array	UserInfo
	 *
	 * @return	int		Cash points
	 */
	public function cashbalance(array $userinfo = array())
	{
		if (!empty($userinfo['userid']))
		{
			$ubalance = $this->_registry->_vbulletin->db->query_first("
				SELECT " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']) . " AS cashpoints
				FROM " . TABLE_PREFIX . "user
				WHERE userid = " . intval($userinfo['userid']) . "
			");

			return $ubalance['cashpoints'];
		}
		else
		{
			return $this->_registry->_vbulletin->userinfo[($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash'])];
		}
	}

	/**
	 * @param	array	array(userid => amount)
	 *
	 * @return	void
	 */
	public function cashtake(array $data)
	{
		$userids = '0';
		$updatedusers = '';
		foreach ($data as $userid => $amount)
		{
			$userids .= ",$userid";
			$updatedusers .= " WHEN " . intval($userid) . " THEN " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']). " - " . intval($amount);
		}

		$this->_registry->_vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user SET " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']) . " = CASE userid " . $updatedusers . " ELSE " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']) . " END WHERE userid IN(" . $userids . ")");
	}

	/**
	 * @param	array	array(userid => amount)
	 *
	 * @return	void
	 */
	public function cashgive(array $data)
	{
		$userids = '0';
		$updatedusers = '';
		foreach ($data as $userid => $amount)
		{
			$userids .= ",$userid";
			$updatedusers .= " WHEN " . intval($userid) . " THEN " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']). " + " . intval($amount);
		}

		$this->_registry->_vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user SET " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']) . " = CASE userid " . $updatedusers . " ELSE " . ($this->_registry->_vbulletin->options['phpkd_vbabc_cash'] == 'custom' ? $this->_registry->_vbulletin->options['phpkd_vbabc_cash_custom'] : $this->_registry->_vbulletin->options['phpkd_vbabc_cash']) . " END WHERE userid IN(" . $userids . ")");
	}

	/**
	 * @param	array	UserInfo
	 * @param	array	EventInfo
	 *
	 * @return	int	Total user bets on the event
	 */
	public function uebets(array $userinfo, array $eventinfo)
	{
		$uebets = $this->_registry->_vbulletin->db->query_first("
			SELECT COUNT(betid) AS bets
			FROM " . TABLE_PREFIX . "phpkd_vbabc_bet
			WHERE userid = " . intval($userinfo['userid']) . " AND eventid = " . intval($eventinfo['eventid'])
		);

		return $uebets['bets'];
	}

	/**
	 * @param	array	EventInfo
	 *
	 * @return	void
	 */
	public function cashback(array $eventinfo)
	{
		$uebets = $this->_registry->_vbulletin->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "phpkd_vbabc_bet
			WHERE eventid = " . intval($eventinfo['eventid'])
		);

		$givenusers = array();
		while ($bet = $this->_registry->_vbulletin->db->fetch_array($uebets))
		{
			$givenusers["$bet[userid]"] += $bet['amount'];
		}

		if (!empty($givenusers))
		{
			$this->cashgive($givenusers);
		}
	}

	/**
	 * @param	array	EventInfo
	 *
	 * @return	void
	 */
	public function eventclose(array $eventinfo)
	{
		$this->_registry->_vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event
			SET status = 'closed'
			WHERE eventid = " . intval($eventinfo['eventid']) . "
		");
	}

	/**
	 * @param	array	EventInfo
	 *
	 * @return	void
	 */
	public function eventopen(array $eventinfo)
	{
		$this->_registry->_vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event
			SET status = 'open'
			WHERE eventid = " . intval($eventinfo['eventid']) . "
		");
	}

	/**
	 * @param	array	EventInfo
	 *
	 * @return	void
	 */
	public function eventabandon(array $eventinfo)
	{
		$this->_registry->_vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_event SET status = 'abandoned', staked = 0, bets = 0 WHERE eventid = " . intval($eventinfo['eventid']));
		$this->_registry->_vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "phpkd_vbabc_option SET staked = 0, bets = 0 WHERE eventid = " . intval($eventinfo['eventid']));

		$this->cashback($eventinfo);
	}

	/**
	 * @param	int	EventInfo
	 *
	 * @return	void
	 */
	public function eventdelete(array $eventinfo)
	{
		// If we're deleting an event that has bets on it but hasn't yet been settled, we must give people their money back first!
		if ($eventinfo['status'] == 'open' OR $eventinfo['status'] == 'closed')
		{
			$this->cashback($eventinfo);
		}

		$this->_registry->_vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "phpkd_vbabc_bet WHERE eventid = " . intval($eventinfo['eventid']));
		$this->_registry->_vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "phpkd_vbabc_option WHERE eventid = " . intval($eventinfo['eventid']));
		$this->_registry->_vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "phpkd_vbabc_event WHERE eventid = " . intval($eventinfo['eventid']));
		$this->_registry->_vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "thread SET phpkd_vbabc_eventid = 0 WHERE phpkd_vbabc_eventid = " . intval($eventinfo['eventid']));
		$this->_registry->_vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "phpkd_vbabc_news (type, eventid, userid, dateline) VALUES ('eventdelete', $eventinfo[eventid], " . $this->_registry->_vbulletin->userinfo['userid'] . ", " . TIMENOW . ")");
	}

	/**
	 * Shortcut function to make the $navbits for the navbar...
	 *
	 * @param	array	ForumInfo
	 * @param	array	ThreadInfo
	 *
	 * @return	array	NavBits
	 */
	public function construct_nav($foruminfo, $threadinfo)
	{
		$navbits = array();
		$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));

		foreach ($parentlist AS $forumID)
		{
			$forumTitle = $this->_registry->_vbulletin->forumcache["$forumID"]['title'];
			$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
		}
		$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];

		switch ($_REQUEST['do'])
		{
			case 'eventadd':  $navbits[''] = $this->_registry->_vbphrase['phpkd_vbabc_eventadd']; break;
			case 'eventedit': $navbits[''] = $this->_registry->_vbphrase['phpkd_vbabc_eventedit']; break;
			case 'showresults': $navbits[''] = $this->_registry->_vbphrase['phpkd_vbabc_results']; break;
			// are there more?
		}

		return construct_navbits($navbits);
	}
}
