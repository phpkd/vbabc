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


if (!defined('VB_AREA'))
{
	echo 'Can not be called from outside vBulletin Framework!';
	exit;
}

define('PHPKD_VBABC_DEBUG',          false);
define('PHPKD_VBABC_VERSION',        '4.1.100');
define('PHPKD_VBABC_TOCKEN',         'b8dedd16971d9b442ac8e7a3baa226e6');
define('PHPKD_VBABC_LICENSE_PREFIX', 'VBABC');


/**
 * Core class
 *
 * @category	vB Automated Bookie Center 'Ultimate'
 * @package		PHPKD_VBABC
 * @copyright	Copyright ©2005-2011 PHP KingDom. All Rights Reserved. (http://www.phpkd.net)
 * @license		http://info.phpkd.net/en/license/commercial
 */
class PHPKD_VBABC
{
	/**
	 * vBulletin phrases
	 *
	 * @var	array
	 */
	public $_vbphrase;

	/**
	 * The vBulletin registry object
	 *
	 * @var	vB_Registry
	 */
	public $_vbulletin = null;

	/**
	 * The DataManager Object Handler
	 *
	 * @var	PHPKD_VBABC_DM
	 */
	private $_dmhandle = null;

	/**
	 * The Hooks Object Handler
	 *
	 * @var	PHPKD_VBABC_Hooks
	 */
	private $_hookshandle;

	/**
	 * Constructor - checks that vBulletin registry object has been passed correctly, and initialize requirements.
	 *
	 * @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its members ($this->db).
	 * @param	array		vBphrase array
	 * @param	integer		One of the ERRTYPE_x constants
	 * @return	PHPKD_VBABC
	 */
	public function __construct(&$registry, $phrases = array())
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

		$this->_vbphrase = $phrases;
		defined('PHPKD_VBABC') || define('PHPKD_VBABC', true);

		return $this;
	}

	/**
	 * Initiate PHPKD_VBABC_DM
	 *
	 * @return	void
	 */
	private function setDmhandle()
	{
		if (!class_exists('PHPKD_VBABC_DM'))
		{
			if (file_exists(DIR . '/includes/phpkd/vbabc/class_dm.php'))
			{
				require_once(DIR . '/includes/phpkd/vbabc/class_dm.php');
			}
			else
			{
				eval(standard_error(fetch_error('phpkd_vbabc_initialization_failed_file', 'class_dm.php')));
			}
		}

		$this->_dmhandle = new PHPKD_VBABC_DM($this);
	}

	/**
	 * Return PHPKD_VBABC_DM object
	 *
	 * @return	PHPKD_VBABC_DM
	 */
	public function getDmhandle()
	{
		if (null == $this->_dmhandle)
		{
			$this->setDmhandle();
		}

		return $this->_dmhandle;
	}

	/**
	 * Initiate PHPKD_VBABC_Hooks
	 *
	 * @return	void
	 */
	private function setHookshandle()
	{
		if (!class_exists('PHPKD_VBABC_Hooks'))
		{
			if (file_exists(DIR . '/includes/phpkd/vbabc/class_hooks.php'))
			{
				require_once(DIR . '/includes/phpkd/vbabc/class_hooks.php');
			}
			else
			{
				eval(standard_error(fetch_error('phpkd_vbabc_initialization_failed_file', 'class_hooks.php')));
			}
		}

		$this->_hookshandle = new PHPKD_VBABC_Hooks($this);
	}

	/**
	 * Return PHPKD_VBABC_Hooks object
	 *
	 * @return	PHPKD_VBABC_Hooks
	 */
	private function getHookshandle()
	{
		if (null == $this->_hookshandle)
		{
			$this->setHookshandle();
		}

		return $this->_hookshandle;
	}

	/**
	 * Verify hook parameters
	 *
	 * @param	array	Input parameters
	 * @return	boolean	Returns true if valid, false if not
	 */
	public function verify_hook_params($params)
	{
		if (is_array($params) AND count($params) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fetch requested hook
	 *
	 * @param	string	Hook name
	 * @param	array	Optional parameters used within the requested hook's code
	 * @return	mixed	Return boolean if there's nothing to get back, otherwise return array
	 */
	public function fetch_hook($hookname, $params = array())
	{
		$hooksmethods = get_class_methods($this->getHookshandle());

		if (in_array($hookname, $hooksmethods))
		{
			return $this->getHookshandle()->$hookname($params);
		}
	}
}
