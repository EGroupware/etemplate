<?php
/**
 * eGroupWare  Baseclass for SiteMgr Modules written with eTemplate
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker@outdoor-training.de>
 * @version $Id$
 */

/**
 * Baseclass for SiteMgr Modules written with eTemplate
 *
 * To create a SiteMgr module from an eTemplate app, you need to:
 *	- extend this class and set the $etemplate_method class-var to a method/menuaction of the app
 *	- the app need to return etemplate::exec (otherwise the content is empty)!!!
 *	- the app need to avoid redirects or links, as this would leave sitemgr!!!
 *
 * @package etemplate
 * @subpackage api
 * @author RalfBecker-AT-outdoor-training.de
 * @license GPL
 */
class sitemgr_module extends Module // the Module class get automatic included by SiteMgr
{
	/**
	 * @var string $etemplate_method Method/menuaction of an eTemplate app to be used as module
	 */
	var $etemplate_method;

	/**
	 * generate the module content AND process submitted forms
	 *
	 * @param array &$arguments $arguments['arg1']-$arguments['arg3'] will be passed for non-submitted forms (first call)
	 * @param array $properties
	 * @return string the html content
	 */
	function get_content(&$arguments,$properties)
	{
		list($app) = explode('.',$this->etemplate_method);
		$GLOBALS['egw']->translation->add_app($app);

		$extra = "<style type=\"text/css\">\n<!--\n@import url(".$GLOBALS['egw_info']['server']['webserver_url'].
			"/etemplate/templates/default/app.css);\n";

		if ($app != 'etemplate' && file_exists(EGW_SERVER_ROOT.'/'.$app.'/templates/default/app.css'))
		{
			$extra .= "@import url(".$GLOBALS['egw_info']['server']['webserver_url'].
				'/'.$app."/templates/default/app.css);\n";
		}
		$extra .= "-->\n</style>\n";
		$extra .= '<script src="'.$GLOBALS['egw_info']['server']['webserver_url'].'/etemplate/js/etemplate.js" type="text/javascript"></script>'."\n";
		$ret = false;
		if($_POST['etemplate_exec_id'])
		{
			$ret = ExecMethod('etemplate.etemplate.process_exec');
		}
		return $extra.($ret ? $ret : ExecMethod2($this->etemplate_method,null,$arguments['arg1'],$arguments['arg2'],$arguments['arg3'],$arguments['arg4'],$arguments['arg5'],$arguments['arg6'],$arguments['arg7']));
	}

	/**
	 *  Check if recaptcha keys are set
	 *
	 * @return boolean|array returns an array consist of recaptcha secret key and site key
	 */
	static function get_recaptcha()
	{
		if ($GLOBALS['egw_info']['server']['recaptcha_site'] && $GLOBALS['egw_info']['server']['recaptcha_secret'])
		{
			return array(
				'secret' => $GLOBALS['egw_info']['server']['recaptcha_secret'],
				'site' => $GLOBALS['egw_info']['server']['recaptcha_site']
			);
		}
		else
		{
			return false;
		}

	}

	/**
	 * Verify recaptcha response from client with recaptcha api server
	 *
	 * @param string $recaptcha_response g-recaptcha-response tocken
	 *
	 * @return boolean|object returns response object of recaptcha or false if recaptcha is not set
	 * response object : {
	 *		"success": true|false,
	 *		"challenge_ts": timestamp,  // timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
	 *		"hostname": string,         // the hostname of the site where the reCAPTCHA was solved
	 *		"error-codes": [...]        // optional
	 * }
	 */
	static function verify_recaptcha($recaptcha_response)
	{
		if (($recaptcha = self::get_recaptcha()))
		{
			$verify  = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={".
					$recaptcha['secret']."}&response=".$recaptcha_response);
			$success = json_decode($verify);
			return $success;
		}
		else
		{
			return false;
		}
	}
}
