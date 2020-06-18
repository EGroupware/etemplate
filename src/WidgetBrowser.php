<?php
/**
 * EGroupware  eTemplate2 widget browser
 *
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @copyright 2013 Nathan Gray
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @subpackage tools
 * @version $Id$
 */

namespace EGroupware\etemplate;

use EGroupware\Api;

/**
 * eTemplate2 widget browser
 *
 * View & play with et2 widgets.  Most of the good stuff happens
 * on the client side via js, this is the server side.
 */
class WidgetBrowser
{

	public $public_functions = array(
		'index'	=>	true
	);

	public static function index()
	{
		$tpl = new Api\Etemplate('etemplate.widgetBrowser');
		// Widget browser code
		Api\Framework::includeJS('/etemplate/js/widget_browser.js');

		Api\Framework::includeCSS('etemplate','widget_browser',false);

		$tpl->exec('etemplate.EGroupware\\Etemplate\\WidgetBrowser.index', [],[]);
	}
}
