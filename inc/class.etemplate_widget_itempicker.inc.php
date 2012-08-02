<?php
/**
 * EGroupware - eTemplate serverside itempicker widget
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @subpackage api
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker@outdoor-training.de>
 * @author Christian Binder <christian@jaytraxx.de>
 * @copyright 2002-11 by RalfBecker@outdoor-training.de
 * @copyright 2012 by Christian Binder <christian@jaytraxx.de>
 * @version $Id: class.etemplate_widget_itempicker.inc.php 36221 2011-08-20 10:27:38Z jaytraxx $
 */

/**
 * eTemplate itempicker widget
 */
class etemplate_widget_itempicker extends etemplate_widget
{
	/**
	 * Constructor
	 *
	 * @param string|XMLReader $xml string with xml or XMLReader positioned on the element to construct
	 * @throws egw_exception_wrong_parameter
	 */
	public function __construct($xml = '')
	{
		if($xml) {
			parent::__construct($xml);
		}
	}
	
	/**
	 * Find items that match the given parameters
	 * using the egw_link class
	 */
	public static function ajax_item_search($app, $type, $pattern, $options=array()) {
		$options['type'] = $type ? $type : $options['type'];
		$items = egw_link::query($app, $pattern, $options);

		$response = egw_json_response::get();
		$response->data($items);
	}
}

etemplate_widget::registerWidget('etemplate_widget_itempicker', array('itempicker'));