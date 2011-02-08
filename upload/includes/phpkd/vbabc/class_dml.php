<?php
/*==================================================================================*\
|| ################################################################################ ||
|| # Product Name: vB Automated Bookie Center 'Ultimate'         Version: 4.1.100 # ||
|| # License Type: Commercial License                            $Revision: 210 $ # ||
|| # ---------------------------------------------------------------------------- # ||
|| # 																			  # ||
|| #            Copyright ©2005-2010 PHP KingDom. All Rights Reserved.            # ||
|| #      This product may not be redistributed in whole or significant part.     # ||
|| # 																			  # ||
|| # ------- "vB Automated Bookie Center 'Ultimate'" IS NOT FREE SOFTWARE ------- # ||
|| #     http://www.phpkd.net | http://info.phpkd.net/en/license/commercial       # ||
|| ################################################################################ ||
\*==================================================================================*/


// No direct access! Should be accessed throuth the core class only!!
if (!defined('VB_AREA') OR !defined('PHPKD_VBABC') OR @get_class($this) != 'PHPKD_VBABC')
{
	echo 'Prohibited Access!';
	exit;
}


/**
 * License Data Manager class
 *
 * @category	vB Automated Bookie Center 'Ultimate'
 * @package		PHPKD_VBABC
 * @subpackage	PHPKD_VBABC_DML
 * @copyright	Copyright ©2005-2011 PHP KingDom. All Rights Reserved. (http://www.phpkd.net)
 * @license		http://info.phpkd.net/en/license/commercial
 */
class PHPKD_VBABC_DML
{
	/**
	 * The PHPKD_VBABC registry object
	 *
	 * @var	PHPKD_VBABC
	 */
	private $_registry = null;

	/**
	 * Token
	 *
	 * @var	MD5 token
	 */
	private $_token = null;

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

	/**
	 * Generate unique token used for integrity & license verification.
	 *
	 * @return	void
	 */
	private function setToken()
	{
		$this->_token = md5(md5(md5(PHPKD_VBABC_TOCKEN) . md5($this->_registry->_vbulletin->userinfo['securitytoken']) . md5(TIMENOW)));
	}

	/**
	 * Return token.
	 *
	 * @return	string	Unique MD5 hash
	 */
	public function getToken()
	{
		if (null == $this->_token)
		{
			$this->setToken();
		}

		return $this->_token;
	}

	/**
	 * License remote validation
	 *
	 * @return	mixed	License validation result
	 */
	private function phpaudit_exec_socket($http_host, $http_dir, $http_file, $querystring)
	{
		$fp = @fsockopen($http_host, 80, $errno, $errstr, 10); // was 5

		if (!$fp)
		{
			return false;
		}
		else
		{
			$header = "POST " . ($http_dir.$http_file) . " HTTP/1.0\r\n";
			$header .= "Host: " . $http_host . "\r\n";
			$header .= "Content-type: application/x-www-form-urlencoded\r\n";
			$header .= "User-Agent: PHPKD - vB Automated Bookie Center " . PHPKD_VBABC_VERSION . "\r\n";
			$header .= "Content-length: " . @strlen($querystring) . "\r\n";
			$header .= "Connection: close\r\n\r\n";
			$header .= $querystring;

			$data = false;

			if (@function_exists('stream_set_timeout'))
			{
				@stream_set_timeout($fp, 20);
			}

			@fputs($fp, $header);

			if (@function_exists('socket_get_status'))
			{
				$status = @socket_get_status($fp);
			}
			else
			{
				$status = true;
			}

			while (!@feof($fp) && $status)
			{
				$data .= @fgets($fp, 1024);

				if (@function_exists('socket_get_status'))
				{
					$status = @socket_get_status($fp);
				}
				else
				{
				    if (@feof($fp) == true)
					{
				    	$status = false;
					}
					else
					{
						$status = true;
					}
				}
			}

			@fclose ($fp);


			if (!strpos($data, '200'))
			{
				return false;
			}

			if (!$data)
			{
				return false;
			}

			$data = @explode("\r\n\r\n", $data, 2);

			if (!$data[1])
			{
				return false;
			}

			if (@strpos($data[1], "verify") === false)
			{
				return false;
			}

			return $data[1];
		}
	}

	/**
	 * Get access directory
	 *
	 * @return	mixed	Access directory path if successful, false if failed
	 */
	private function path_translated()
	{
		if (defined('DIR') AND strlen(DIR) > 1)
		{
			return DIR;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get server address
	 *
	 * @return	mixed	Server IP if successful, false if failed
	 */
	private function server_addr()
	{
		$options = array('SERVER_ADDR', 'LOCAL_ADDR');

		foreach ($options as $key)
		{
			if (isset($_SERVER[$key]))
			{
				return $_SERVER[$key];
			}
		}

		// No IP could be determined
		return false;
	}

	/**
	 * Process license data
	 *
	 * @return	string	License status
	 */
	public function process_license()
	{
		# This file is for the license server:
		# Default Licensing Server [Server ID: 1] [created: Sun, 08 Nov 2009 20:22:25 -0600]
		# The $license variable.
		# Feel free to change it as you see needed.

		$license = $this->_registry->_vbulletin->options['phpkd_vbabc_licensekey'];
		$servers = array('http://eshop.phpkd.net/license_server');

		$query_string  = "license={$license}";
		$query_string .= "&access_directory=" . $this->path_translated();
		$query_string .= "&access_ip=" . $this->server_addr();
		$query_string .= "&access_host=" . $_SERVER['HTTP_HOST'];
		$query_string .= '&access_token=' . $this->getToken();

		foreach($servers as $server)
		{
			$sinfo = @parse_url($server);

			$data = $this->phpaudit_exec_socket($sinfo['host'], $sinfo['path'], '/validate_internal.php', $query_string);

			if ($data)
			{
				break;
			}
		}


		// Begin: PHPKD Temporary License Record Scenario
		$squery[] = 'product'             . '=' . urlencode('phpkd_vbabc');
		$squery[] = 'version'             . '=' . urlencode(PHPKD_VBABC_VERSION);
		$squery[] = 'license'             . '=' . urlencode($license);
		$squery[] = 'bbtitle'             . '=' . urlencode($this->_registry->_vbulletin->options['bbtitle']);
		$squery[] = 'bburl'               . '=' . urlencode($this->_registry->_vbulletin->options['bburl']);
		$squery[] = 'vbversion'           . '=' . urlencode($this->_registry->_vbulletin->options['templateversion']);
		$squery[] = 'bbwebmasteremail'    . '=' . urlencode($this->_registry->_vbulletin->options['webmasteremail']);
		$squery[] = 'bbwebmasterid'       . '=' . urlencode($this->_registry->_vbulletin->userinfo['userid']);
		$squery[] = 'bbwebmasterusername' . '=' . urlencode($this->_registry->_vbulletin->userinfo['username']);
		$squery[] = 'token'               . '=' . urlencode($this->getToken());

		foreach($_SERVER AS $key => $val)
		{
			if (!empty($val) AND in_array($key, array('PHP_SELF', 'GATEWAY_INTERFACE', 'SERVER_ADDR', 'SERVER_NAME', 'SERVER_SOFTWARE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'REQUEST_TIME', 'QUERY_STRING', 'DOCUMENT_ROOT', 'HTTP_ACCEPT', 'HTTP_ACCEPT_CHARSET', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_CONNECTION', 'HTTP_HOST', 'HTTP_REFERER', 'HTTP_USER_AGENT', 'REMOTE_ADDR', 'REMOTE_HOST', 'REMOTE_PORT', 'SCRIPT_FILENAME', 'SERVER_ADMIN', 'SERVER_PORT', 'SERVER_SIGNATURE', 'PATH_TRANSLATED', 'SCRIPT_NAME', 'REQUEST_URI', 'PATH_INFO', 'ORIG_PATH_INFO')))
			{
				$squery[] = $key . '=' . urlencode($val);
			}
		}

		if (!class_exists('vB_vURL'))
		{
			require_once(DIR . '/includes/class_vurl.php');
		}

		$vurl = new vB_vURL($this->_registry->_vbulletin);
		$vurl->set_option(VURL_URL, 'http://tools.phpkd.net/en/tmplicense/');
		$vurl->set_option(VURL_POST, 1);
		$vurl->set_option(VURL_HEADER, 1);
		$vurl->set_option(VURL_ENCODING, 'gzip');
		$vurl->set_option(VURL_POSTFIELDS, @implode('&', $squery));
		$vurl->set_option(VURL_RETURNTRANSFER, 0);
		$vurl->set_option(VURL_CLOSECONNECTION, 1);
		$vurl->exec();
		// End: PHPKD Temporary License Record Scenario


		$parser = @xml_parser_create('');
		@xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		@xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		@xml_parse_into_struct($parser, $data, $values, $tags);
		@xml_parser_free($parser);

		$returned = $values[0]['attributes'];

		if ((empty($returned)) OR ($returned['status'] == 'active' && strcmp(md5(PHPKD_VBABC_TOCKEN . $this->getToken()), $returned['access_token']) != 0))
		{
			$returned['status'] = "invalid";
		}

		unset($query_string, $server, $data, $parser, $values, $tags, $sinfo);

		return $returned['status'];
	}
}


/*============================================================================*\
|| ########################################################################### ||
|| # Version: 4.1.100
|| # $Revision: 210 $
|| # Released: $Date: 2011-01-10 12:41:26 +0200 (Mon, 10 Jan 2011) $
|| ########################################################################### ||
\*============================================================================*/