<?php
	/**
	 * eGroupWare eTemplate Widget for custom fields
	 *
	 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	 * @package etemplate
	 * @link http://www.egroupware.org
	 * @author Cornelius Weiss <egw@von-und-zu-weiss.de>
	 * @version $Id$
	 */

	/**
	 * This widget generates a template for customfields based on definitions in egw_config table
	 *
	 * @package etemplate
	 * @subpackage extensions
	 * @author RalfBecker-At-outdoor-training.de
	 * @author Cornelius Weiss <egw@von-und-zu-weiss.de>
	 * @license GPL - GNU General Public License
	 */
	class customfields_widget
	{
		var $public_functions = array(
			'pre_process' => True,
		);
		var $human_name = array(
			'customfields' => 'custom fields',
			'customfields-types' => 'custom field types',
			'customfields-list'  => 'custom field list',
		);
		
		/**
		* Allowd types of customfields
		* 
		* The additionally allowed app-names from the link-class, will be add by the edit-method only,
		* as the link-class has to be called, which can NOT be instanciated by the constructor, as 
		* we get a loop in the instanciation.
		* 
		* @var array
		*/
		var $cf_types = array(
			'text'     => 'Text',
			'label'    => 'Label',
			'select'   => 'Selectbox',
			'radio'    => 'Radiobutton',
			'checkbox' => 'Checkbox',
			'date'     => 'Date',
			'date-time'=> 'Date+Time',
			'select-account' => 'Select account',
			'button'   => 'Button',		// button to execute javascript
			'link-entry' => 'Select entry',		// should be last type, as the individual apps get added behind
		);

		/**
		* @var $prefix string Prefix for every custiomfield name returned in $content (# for general (admin) customfields)
		*/
		var $prefix = '#';

		function customfields_widget($ui)
		{
			$this->appname = $GLOBALS['egw_info']['flags']['currentapp'];
			$this->config =& CreateObject('phpgwapi.config',$this->appname);
			$this->config->appname = $this->appname;
			$config = $this->config->read_repository();
			//merge old config_name in egw_config table
			$config_name = isset($config['customfields']) ? 'customfields' : 'custom_fields';
			$this->customfields = $config[$config_name] ? $config[$config_name] : array('test'=>array('type'=>'label'));
			$this->types = $config['types'];
			$this->advanced_search = $GLOBALS['egw_info']['etemplate']['advanced_search'];

		}

		function pre_process($name,&$value,&$cell,&$readonlys,&$extension_data,&$tmpl)
		{
			$type2 = $cell['size'];

			switch($type = $cell['type'])
			{
				case 'customfields-types':
					$cell['type'] = 'select';
					foreach($this->cf_types as $name => $label) $cell['sel_options'][$name] = lang($label);
					$link_types = ExecMethod('phpgwapi.bolink.app_list','');
					ksort($link_types);
					foreach($link_types as $name => $label) $cell['sel_options'][$name] = '- '.$label;
					$cell['no_lang'] = true;
					return true;

				case 'customfields-list':
					foreach(array_reverse($this->customfields) as $name => $field)
					{
						if (!empty($field['type2']) && strpos(','.$field['type2'].',',','.$type2.',') === false) continue;	// not for our content type
						if (isset($value[$this->prefix.$name]) && $value[$this->prefix.$name] !== '') break;
						$stop_at_field = $name;
					}
					break;
			}
			$readonly = $cell['readonly'] || $readonlys[$name] || $type == 'customfields-list';

			if(!is_array($this->customfields))
			{
				$cell['type'] = 'label';
				return True;
			}
			// making the cell an empty grid
			$cell['type'] = 'grid';
			$cell['data'] = array(array());
			$cell['rows'] = $cell['cols'] = 0;
			
			$n = 1;
			foreach($this->customfields as $name => $field)
			{
				if ($stop_at_field && $name == $stop_at_field) break;	// no further row necessary

				// check if the customfield get's displayed for type $value, we can have multiple comma-separated types now
				if (!empty($field['type2']) && strpos(','.$field['type2'].',',','.$type2.',') === false)
				{
					continue;	// not for our content type
				}
				$new_row = null; etemplate::add_child($cell,$new_row);

				if ($type != 'customfields-list')
				{
					$row_class = 'row';
					etemplate::add_child($cell,$label =& etemplate::empty_cell('label','',array(
						'label' => $field['label'],
						'no_lang' => substr(lang($field['label']),-1) == '*' ? 2 : 0,
						'span' => $field['type'] === 'label' ? '2' : '',
					)));
				}
				switch ((string)$field['type'])
				{
					case 'select' :
						if (count($field['values']) == 1 && isset($field['values']['@']))
						{
							$field['values'] = $this->_get_options_from_file($field['values']['@']);
						}
						if($this->advanced_search && $field['rows'] <= 1) $field['values'][''] = lang('doesn\'t matter');
						foreach($field['values'] as $key => $val)
						{
							if (substr($val = lang($val),-1) != '*')
							{
								$field['values'][$key] = $val;
							}
						}
						$input =& etemplate::empty_cell('select',$this->prefix.$name,array(
							'sel_options' => $field['values'],
							'size'        => $field['rows'],
							'no_lang'     => True								
						));
						if($this->advanced_search)
						{
							$select =& $input; unset($input);
							$input =& etemplate::empty_cell('hbox');
							etemplate::add_child($input, $select); unset($select);
							etemplate::add_child($input, etemplate::empty_cell('select',$this->prefix.$name,array(
								'sel_options' => $field['values'],
								'size'        => $field['rows'],
								'no_lang'     => True
							)));
						}
						break;
					case 'label' :
						$row_class = 'th';
						break;
					case 'checkbox' :
						$input =& etemplate::empty_cell('checkbox',$this->prefix.$name);
						break;
					case 'radio' :
						if (count($field['values']) == 1 && isset($field['values']['@']))
						{
							$field['values'] = $this->_get_options_from_file($field['values']['@']);
						}
						$input =& etemplate::empty_cell('groupbox');
						$m = 0;
						foreach ($field['values'] as $key => $val)
						{
							$radio = etemplate::empty_cell('radio',$this->prefix.$name);
							$radio['label'] = $val;
							$radio['size'] = $key;
							etemplate::add_child($input,$radio);
							unset($radio);
						}
						break;
					case 'text' :
					case 'textarea' :
					case '' :	// not set
						$field['len'] = $field['len'] ? $field['len'] : 20;
						if($field['rows'] <= 1)
						{
							list($max,$shown) = explode(',',$field['len']);
							$input =& etemplate::empty_cell('text',$this->prefix.$name,array(
								'size' => intval($shown > 0 ? $shown : $max).','.intval($max)
							));
						}
						else
						{
							$input =& etemplate::empty_cell('textarea',$this->prefix.$name,array(
								'size' => $field['rows'].($field['len'] > 0 ? ','.(int)$field['len'] : '')
							));
						}
						break;
					case 'date':
					case 'date-time':
						$input =& etemplate::empty_cell($field['type'],$this->prefix.$name,array(
							'size' => $field['len'] ? $field['len'] : ($field['type'] == 'date' ? 'Y-m-d' : 'Y-m-d H:i:s'),
						));
						break;
					case 'select-account':
						list($opts) = explode('=',$field['values'][0]);
						$input =& etemplate::empty_cell('select-account',$this->prefix.$name,array(
							'size' => ($field['rows']>1?$field['rows']:lang('None')).','.$opts,
						));
						break;
					case 'link-entry':
					default :	// link-entry to given app
						$input =& etemplate::empty_cell('link-entry',$this->prefix.$name,array(
							'size' => $field['type'] == 'link-entry' ? '' : $field['type'],
						));
						break;
					case 'button':	// button(s) to execute javascript (label=onclick) or textinputs (empty label, readonly with neg. length)
						$input =& etemplate::empty_cell('hbox');
						foreach($field['values'] as $label => $js)
						{
							if (!$label)	// display an readonly input
							{
								$widget =& etemplate::empty_cell('text',$this->prefix.$name.$label,array(
									'size' => $field['len'] ? $field['len'] : 20,
									'readonly' => $field['len'] < 0,
									'onchange' => $js,
								));
							}
							else
							{
								if ($readonly) continue;	// dont display buttons if we're readonly
								$widget =& etemplate::empty_cell('buttononly',$this->prefix.$name.$label,array(
									'label' => $label ? $label : lang('Submit'),
									'onclick' => $js,
									'no_lang' => True								
								));
							}
							etemplate::add_child($input,$widget);
							unset($widget);
						}
						break;				
				}
				$cell['data'][0]['c'.$n++] = $row_class.',top';
				
				if (!is_null($input))
				{
					if ($readonly) $input['readonly'] = true;
					
					if (!empty($field['help']) && $row_class != 'th')
					{
						$input['help'] = $field['help'];
						$input['no_lang'] = substr(lang($help),-1) == '*' ? 2 : 0;
					}
					etemplate::add_child($cell,$input);
					unset($input);
				}
				unset($label);
			}
			if ($type != 'customfields-list')
			{
				$cell['data'][0]['A'] = '100';
			}
			list($span,$class) = explode(',',$cell['span']);	// msie (at least 5.5) shows nothing with div overflow=auto
			$cell['size'] = '100%,100%,0,'.$class.','.($type=='customfields-list'?',0':',').($tmpl->html->user_agent != 'msie' ? ',auto' : '');

			return True;	// extra Label is ok
		}
		
		/**
		 * Read the options of a 'select' or 'radio' custom field from a file
		 * 
		 * For security reasons that file has to be relative to the eGW root 
		 * (to not use that feature to explore arbitrary files on the server)
		 * and it has to be a php file setting one variable called options,
		 * (to not display it to anonymously by the webserver). 
		 * The $options var has to be an array with value => label pairs, eg:
		 * 
		 * <?php
		 * $options = array(
		 * 	'a' => 'Option A',
		 * 	'b' => 'Option B',
		 * 	'c' => 'Option C',
		 * );
		 *
		 * @param string $file file name inside the eGW server root, either relative to it or absolute
		 * @return array in case of an error we return a single option with the message
		 */
		function _get_options_from_file($file)
		{
			if (!($path = realpath($file{0} == '/' ? $file : EGW_SERVER_ROOT.'/'.$file)) ||	// file does not exist
				substr($path,0,strlen(EGW_SERVER_ROOT)+1) != EGW_SERVER_ROOT.'/' ||	// we are NOT inside the eGW root
				basename($path,'.php').'.php' != basename($path) ||	// extension is NOT .php
				basename($path) == 'header.inc.php')	// dont allow to include our header again
			{
				return array(lang("'%1' is no php file in the eGW server root (%2)!".': '.$path,$file,EGW_SERVER_ROOT));
			}
			include($path);
			
			return $options;
		}
	}
