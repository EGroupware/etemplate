<?php
/**
 * EGroupware - eTemplate serverside date widget
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @subpackage api
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker@outdoor-training.de>
 * @copyright 2002-11 by RalfBecker@outdoor-training.de
 * @author Nathan Gray
 * @copyright 2011 Nathan Gray
 * @version $Id$
 */

/**
 * eTemplate date widget
 *
 * Deals with date and time.  Overridden to handle date-houronly as a transform
 *
 * Supported attributes: dataformat[,mode]
 *  dataformat: '' = timestamps or automatic conversation, or eg. 'Y-m-d H:i:s' for 2002-12-31 23:59:59
 *  mode: &1 = year is int-input not selectbox, &2 = show a [Today] button, (html-UI always uses jscal and dont care for &1+&2)
 *           &4 = 1min steps for time (default is 5min, with fallback to 1min if value is not in 5min-steps),
 *           &8 = dont show time for readonly and type date-time if time is 0:00,
 *           &16 = prefix r/o display with dow
 *           &32 = prefix r/o display with week-number
 *			 &64 = prefix r/o display with weeknumber and dow
 *           &128 = no icon to trigger popup, click into input trigers it, also removing the separators to save space
 *
 * @todo validation of date-duration
 *
 * @info Communication between client and server is always done as a string in ISO8601/W3C
 * format ("Y-m-d\TH:i:sP").  If the application specifies a different format
 * for the field, the conversion is done as needed understand what the application
 * sends, and to give the application what it wants when the form is submitted.
 */
class etemplate_widget_date extends etemplate_widget_transformer
{
	protected static $transformation = array(
		'type' => array('date-houronly' => 'select-hour')
	);

	/**
	 * (Array of) comma-separated list of legacy options to automatically replace when parsing with set_attrs
	 *
	 * @var string|array
	 */
	protected $legacy_options = 'dataformat,mode';


	/**
	 * Convert the provided date into the format needed for unambiguous communication
	 * with browsers (Javascript).  We use W3C format to avoid timestamp issues.
	 */
	public function beforeSendToClient($cname)
	{
		if($this->type == 'date-houronly')
		{
			return parent::beforeSendToClient($cname);
		}

		$form_name = self::form_name($cname, $this->id);
		$value =& self::get_array(self::$request->content, $form_name, false, true);
		
		if($this->type != 'date-duration' && $value)
		{
			// string with formatting letters like for php's date() method
			if ($this->attrs['dataformat'] && !is_numeric($value))
			{
				$date = date_create_from_format($this->attrs['dataformat'], $value, new DateTimeZone('UTC'));
			}
			else
			{
				$date = new egw_time((int)$value, new DateTimeZone('UTC'));
			}
			if($this->type == 'date-timeonly')
			{
				$date->setDate(1970, 1, 1);
			}
			if($date)
			{
				// Set timezone to UTC so javascript doesn't add/subtract anything
				$date->setTimezone(new DateTimeZone('UTC'));
				$value = $date->format(egw_time::W3C);
			}
		}
	}

	/**
	 * Validate input
	 *
	 * For dates (except duration), it is always a full timestamp in W3C format,
	 * which we then convert to the format the application is expecting.  This can
	 * be either a unix timestamp, just a date, just time, or whatever is
	 * specified in the template.
	 *
	 * @param string $cname current namespace
	 * @param array $expand values for keys 'c', 'row', 'c_', 'row_', 'cont'
	 * @param array $content
	 * @param array &$validated=array() validated content
	 * @return boolean true if no validation error, false otherwise
	 */
	public function validate($cname, array $expand, array $content, &$validated=array())
	{
		$form_name = self::form_name($cname, $this->id, $expand);

		if (!$this->is_readonly($cname, $form_name) && $this->type != 'date-since')	// date-since is always readonly
		{
			$value = self::get_array($content, $form_name);
			$valid =& self::get_array($validated, $form_name, true);
			
			if ((string)$value === '' && $this->attrs['needed'])
			{
				self::set_validation_error($form_name,lang('Field must not be empty !!!'));
			}
			elseif (is_null($value))
			{
				$valid = null;
			}
			elseif ($this->type == 'date-duration')
			{
				$valid = (string)$value === '' ? '' : (int)$value;
			}
			if($value)
			{
				$date = new egw_time($value);
			}
			if(!$value)
			{
				// Not null, blank
				$value = '';
			}
			elseif (empty($this->attrs['dataformat']))	// integer timestamp
			{
				$valid = $date->format('ts');
			}
			// string with formatting letters like for php's date() method
			elseif (($valid = $date->format($this->attrs['dataformat'])))
			{
				// Nothing to do here
			}
			else
			{
				// this is not really a user error, but one of the clientside engine
				self::set_validation_error($form_name,lang("'%1' is not a valid date !!!", $value).' '.$this->dataformat);
			}
			//error_log("$this : ($valid)" . egw_time::to($valid));
		}
	}
}
