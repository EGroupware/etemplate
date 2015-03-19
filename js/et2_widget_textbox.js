/**
 * EGroupware eTemplate2 - JS Textbox object
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @subpackage api
 * @link http://www.egroupware.org
 * @author Andreas Stöckel
 * @copyright Stylite 2011
 * @version $Id$
 */

"use strict";

/*egw:uses
	jquery.jquery;
	et2_core_inputWidget;
	et2_core_valueWidget;
*/

/**
 * Class which implements the "textbox" XET-Tag
 *
 * @augments et2_inputWidget
 */
var et2_textbox = et2_inputWidget.extend([et2_IResizeable],
{
	attributes: {
		"multiline": {
			"name": "multiline",
			"type": "boolean",
			"default": false,
			"description": "If true, the textbox is a multiline edit field."
		},
		"size": {
			"name": "Size",
			"type": "integer",
			"default": et2_no_init,
			"description": "Field width"
		},
		"maxlength": {
			"name": "Maximum length",
			"type": "integer",
			"default": et2_no_init,
			"description": "Maximum number of characters allowed"
		},
		"blur": {
			"name": "Placeholder",
			"type": "string",
			"default": "",
			"description": "This text get displayed if an input-field is empty and does not have the input-focus (blur). It can be used to show a default value or a kind of help-text."
		},
		// These for multi-line
		"rows": {
			"name": "Rows",
			"type": "integer",
			"default": -1,
			"description": "Multiline field height - better to use CSS"
		},
		"cols": {
			"name": "Size",
			"type": "integer",
			"default": -1,
			"description": "Multiline field width - better to use CSS"
		},
		"validator": {
			"name": "Validator",
			"type": "string",
			"default": et2_no_init,
			"description": "Perl regular expression eg. '/^[0-9][a-f]{4}$/i'"
		},
		"autocomplete": {
			"name": "Autocomplete",
			"type": "string",
			"default": "",
			"description": "Weither or not browser should autocomplete that field: 'on', 'off', 'default' (use attribute from form)"
		},
		onkeypress: {
			name: "onKeypress",
			type: "js",
			default: et2_no_init,
			description: "JS code or app.$app.$method called when key is pressed, return false cancels it."
		}
	},

	legacyOptions: ["size", "maxlength", "validator"],

	/**
	 * Constructor
	 *
	 * @memberOf et2_textbox
	 */
	init: function() {
		this._super.apply(this, arguments);

		this.input = null;

		this.createInputWidget();
	},

	createInputWidget: function() {
		if (this.options.multiline || this.options.rows > 1 || this.options.cols > 1)
		{
			this.input = $j(document.createElement("textarea"));

			if (this.options.rows > 0)
			{
				this.input.attr("rows", this.options.rows);
			}

			if (this.options.cols > 0)
			{
				this.input.attr("cols", this.options.cols);
			}
		}
		else
		{
			this.input = $j(document.createElement("input"));
			switch(this.options.type)
			{
				case "passwd":
					this.input.attr("type", "password");
					break;
				case "hidden":
					this.input.attr("type", "hidden");
					break;
			}
			if (this.options.autocomplete) this.input.attr("autocomplete", this.options.autocomplete);
		}

		if(this.options.size) {
			this.set_size(this.options.size);
		}
		if(this.options.blur) {
			this.set_blur(this.options.blur);
		}
		if(this.options.readonly) {
			this.set_readonly(true);
		}
		this.input.addClass("et2_textbox");
		this.setDOMNode(this.input[0]);
		if(this.options.value)
		{
			this.set_value(this.options.value);
		}
		if (this.options.onkeypress && typeof this.options.onkeypress == 'function')
		{
			var self = this;
			this.input.keypress(function(_ev)
			{
				return self.options.onkeypress.call(this, _ev, self);
			});
		}
	},

	destroy: function() {
		var node = this.getInputNode();
		if (node) $j(node).unbind("keypress");

		this._super.apply(this, arguments);
	},

	getValue: function()
	{
		if(this.options && this.options.blur && this.input.val() == this.options.blur) return "";
		return this._super.apply(this, arguments);
	},

	/**
	 * Clientside validation using regular expression in "validator" attribute
	 *
	 * @param {array} _messages
	 */
	isValid: function(_messages)
	{
		var ok = true;
		// Check input is valid
		if(this.options && this.options.validator && !this.options.readonly && !this.disabled)
		{
			if (typeof this.options.validator == 'string')
			{
				var parts = this.options.validator.split('/');
				var flags = parts.pop();
				if (parts.length < 2 || parts[0] !== '')
				{
					_messages.push(this.egw().lang("'%1' has an invalid format !!!", this.options.validator));
					return false;	// show invalid expression
				}
				parts.shift();
				this.options.validator = new RegExp(parts.join('/'), flags);
			}
			var value = this.getValue();
			if (!(ok = this.options.validator.test(value)))
			{
				_messages.push(this.egw().lang("'%1' has an invalid format !!!", value));
			}
		}
		return this._super.apply(this, arguments) && ok;
	},

	/**
	 * Set input widget size
	 * @param _size Rather arbitrary size units, approximately characters
	 */
	set_size: function(_size) {
		if (this.options.multiline || this.options.rows > 1 || this.options.cols > 1)
		{
			this.input.css('width', _size + "em");
		}
		else if (typeof _size != 'undefined' && _size != this.input.attr("size"))
		{
			this.size = _size;
			this.input.attr("size", this.size);
		}
	},

	/**
	 * Set maximum characters allowed
	 * @param _size Max characters allowed
	 */
	set_maxlength: function(_size) {
		if (typeof _size != 'undefined' && _size != this.input.attr("maxlength"))
		{
			this.maxLength = _size;
			this.input.attr("maxLength", this.maxLength);
		}
	},

	/**
	 * Set HTML readonly attribute.
	 * Do not confuse this with etemplate readonly, which would use et_textbox_ro instead
	 * @param _readonly Boolean
	 */
	set_readonly: function(_readonly) {
		this.input.attr("readonly", _readonly);
	},

	set_blur: function(_value) {
		if(_value) {
			this.input.attr("placeholder", this.egw().lang(_value) + "");	// HTML5
			if(!this.input[0].placeholder) {
				// Not HTML5
				if(this.input.val() == "") this.input.val(this.egw().lang(this.options.blur));
				this.input.focus(this,function(e) {
					if(e.data.input.val() == e.data.egw().lang(e.data.options.blur)) e.data.input.val("");
				}).blur(this, function(e) {
					if(e.data.input.val() == "") e.data.input.val(e.data.egw().lang(e.data.options.blur));
				});
			}
		} else {
			if (!this.getValue()) this.input.val('');
			this.input.removeAttr("placeholder");
		}
		this.options.blur = _value;
	},

	resize: function (_height)
	{
		if (_height && this.options.multiline)
		{
			// apply the ratio
			_height = (this.options.resize_ratio != '')? _height * this.options.resize_ratio: _height;
			if (_height != 0) this.input.height(this.input.height() + _height);
		}
	}
});
et2_register_widget(et2_textbox, ["textbox", "passwd", "hidden"]);

/**
 * et2_textbox_ro is the dummy readonly implementation of the textbox.
 *
 * @augments et2_valueWidget
 */
var et2_textbox_ro = et2_valueWidget.extend([et2_IDetachedDOM],
{
	/**
	 * Ignore all more advanced attributes.
	 */
	attributes: {
		"multiline": {
			"ignore": true
		},
		"maxlength": {
			"ignore": true
		},
		"onchange": {
			"ignore": true
		},
		"rows": {
			"ignore": true
		},
		"cols": {
			"ignore": true
		},
		"size": {
			"ignore": true
		},
		"needed": {
			"ignore": true
		}
	},

	/**
	 * Constructor
	 *
	 * @memberOf et2_textbox_ro
	 */
	init: function() {
		this._super.apply(this, arguments);

		this.value = "";
		this.span = $j(document.createElement("label"))
			.addClass("et2_label");
		this.value_span = $j(document.createElement("span"))
			.addClass("et2_textbox_ro")
			.appendTo(this.span);

		this.setDOMNode(this.span[0]);
	},

	set_label: function(label)
	{
		// Remove current label
		this.span.contents()
			.filter(function(){ return this.nodeType == 3; }).remove();

		var parts = et2_csvSplit(label, 2, "%s");
		this.span.prepend(parts[0]);
		this.span.append(parts[1]);
		this.label = label;
	},
	set_value: function(_value)
	{
		this.value = _value;

		if(!_value)
		{
			_value = "";
		}
		if (this.label !="")
		{
			this.span.removeClass('et2_label_empty');
		}
		else
		{
			this.span.addClass('et2_label_empty');
		}
		this.value_span.text(_value);
	},
	/**
	 * Code for implementing et2_IDetachedDOM
	 *
	 * @param {array} _attrs array to add further attributes to
	 */
	getDetachedAttributes: function(_attrs)
	{
		_attrs.push("value", "label");
	},

	getDetachedNodes: function()
	{
		return [this.span[0], this.value_span[0]];
	},

	setDetachedAttributes: function(_nodes, _values)
	{
		this.span = jQuery(_nodes[0]);
		this.value_span = jQuery(_nodes[1]);
		if(typeof _values["label"] != 'undefined')
		{
			this.set_label(_values["label"]);
		}
		if(typeof _values["value"] != 'undefined')
		{
			this.set_value(_values["value"]);
		}
	}
});
et2_register_widget(et2_textbox_ro, ["textbox_ro"]);

