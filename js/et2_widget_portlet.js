/**
 * EGroupware eTemplate2 - JS Portlet object - used by Home
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package home
 * @package etemplate
 * @subpackage api
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @version $Id$
 */

"use strict";

/*egw:uses
        jquery.jquery;
        et2_core_baseWidget;
*/

/**
 * Class which implements the UI of a Portlet
 *
 * This manages the frame and decoration, but also provides the UI for properties.
 *
 * Portlets are only internal to EGroupware.  
 *
 * Home does not fully implement WSRP, but tries not to conflict, ether.
 * @link http://docs.oasis-open.org/wsrp/v2/wsrp-2.0-spec-os-01.html
 * @augments et2_baseWidget
 */
var et2_portlet = et2_valueWidget.extend(
{
	attributes: {
		"title": {
			"name": "Title",
			"description": "Goes in the little bit at the top with the icons",
			"type": "string",
			"default": ""
		},
		"edit_template": {
			"name": "Edit template",
			"description": "Custom eTemplate used to customize / set up the portlet",
			"type": "string",
			"default": window.egw_webserverUrl+"/home/templates/default/edit.xet"
		},
		"settings": {
			"name": "Customization settings",
			"description": "Array of customization settings, similar to preference settings",
			"type": "any",
			"default": et2_no_init
		},
		"width": { "default": 2, "ignore": true},
		"height": { "default": 1, "ignore": true},
		"rows": {"ignore": true},
		"cols": {"ignore": true},
		"row": { "default": 1},
		"col": {"default": 1}
	},

	createNamespace: true,
	RESIZE_TIMEOUT: 5000,
	GRID: 50,

	/**
	 * These are the "normal" actions that every portlet is expected to have.
	 * The widget provides default actions for all of these, but they can
	 * be added to or overridden if needed by setting the action attribute.
	 */
	default_actions: {
		edit_settings: {
			icon:	"edit",
			caption: "Configure",
			default: true,
			hideOnDisabled: true,
			group:	"portlet"
		},
		remove_portlet: {
			icon:	"delete",
			caption: "Remove",
			group:	"portlet"
		}
	},

	/**
	 * Constructor
	 * 
	 * @memberOf et2_portlet
	 */
	init: function()
	{
		this._super.apply(this, arguments);

		var self = this;
		
		// Create DOM nodes
		this.div = $j(document.createElement("div"))
			.addClass("ui-widget ui-widget-content ui-corner-all")
			.addClass("et2_portlet")
			/* Gridster */
			.attr("data-sizex", this.options.width)
			.attr("data-sizey", this.options.height)
			.attr("data-row", this.options.row)
			.attr("data-col", this.options.col)

			// Shapeshift
			.width(this.options.width * this.GRID)
			.height(this.options.height * this.GRID)
			.attr("data-ss-rowspan", this.options.row)
			.attr("data-ss-colspan", this.options.col)

			.resizable( {
				autoHide: true,
				grid:	this.GRID,
				//containment: this.getParent().getDOMNode(),
				stop: function(event, ui) {
					self.set_width(Math.round(ui.size.width / self.GRID));
					self.set_height(Math.round(ui.size.height / self.GRID));
					self.egw().json("home.home_ui.ajax_set_properties",[self.id, self.options.settings,{
							width: self.options.width,
							height: self.options.height
						}],
						null,
						self, true, self
					).sendRequest();
				}
			});
		this.header = $j(document.createElement("div"))
			.addClass("ui-widget-header ui-corner-all")
			.appendTo(this.div)
			.html(this.options.title);
		this.content = $j(document.createElement("div"))
			.appendTo(this.div)
			.html(this.options.value);

		this.setDOMNode(this.div[0]);
	},

	destroy: function()
	{
		this._super.apply(this, arguments);
	},

	/**
	 * Overriden from parent to add in default actions
	 */
	set_actions: function(actions)
	{
		// Set targets for actions
		var defaults = {};
		for(var action_name in this.default_actions)
		{
			defaults[action_name] = this.default_actions[action_name];
			if(typeof this[action_name] == "function")
			{
				defaults[action_name].onExecute = jQuery.proxy(this[action_name],this);
			}
		}

		// Add in defaults, but let provided actions override them
		this.options.actions = jQuery.extend(true,{},defaults,actions);
		this._super.apply(this, [this.options.actions]);
	},

	/**
	 * Override _link_actions to remove edit action, if there is no settings
	 */
	_link_actions: function(parsed_actions)
	{
		// Get the top level element
		var objectManager = egw_getAppObjectManager(true);
		var widget_object = objectManager.getObjectById(this.id);
		if (widget_object == null) {
			// Add a new container to the object manager which will hold the widget
			// objects
			widget_object = objectManager.insertObject(false, new egwActionObject(
				this.id, objectManager, new et2_action_object_impl(this), 
				objectManager.manager.getActionById(this.id) || objectManager.manager
			));
		}

		// Delete all old objects
		widget_object.clear();

		// Go over the widget & add links - this is where we decide which actions are
		// 'allowed' for this widget at this time
		var action_links = [];
		for(var i = 0; i < parsed_actions.length; i++)
		{
			var action = {
				actionId: parsed_actions[i].id,
				enabled: true
			};

			// If there are no settings, there can be no customization, so remove the edit action
			if(parsed_actions[i].id == 'edit_settings' && (!this.options.settings || jQuery.isEmptyObject(this.options.settings)))
			{
				this.egw().debug("log", "No settings for portlet %o, edit_settings action removed", this);
				action.enabled = false;
			}
			action_links.push(action);
		}

		widget_object.updateActionLinks(action_links);
	},

	/**
	 * Create & show a dialog for customizing this portlet
	 *
	 * Properties for customization are sent in the 'settings' attribute
	 */
	edit_settings: function(action, sender)
	{
		var dialog = et2_createWidget("dialog", {
			callback: jQuery.proxy(this._process_edit, this),
			template: this.options.edit_template,
			value: {
				content: this.options.settings
			},
			buttons: et2_dialog.BUTTONS_OK_CANCEL
		},this);
		// Set seperately to avoid translation
		dialog.set_title(this.egw().lang("Edit") + " " + this.options.title);
	},

	_process_edit: function(button_id, value)
	{
		if(button_id != et2_dialog.OK_BUTTON) return;

		
		// Save settings - server will reply with new content, if the portlet needs an update
		this.div.addClass("loading");
		this.egw().json("home.home_ui.ajax_set_properties",[this.id, this.options.settings || {}, value], 
			function(data) {
				this.div.removeClass("loading");
				this.set_value(data.content);
				for(var key in data.attributes)
				{
					if(typeof this["set_"+key] == "function")
					{
						this["set_"+key].call(this, data.attributes[key]);
					}
					else if (this.attributes[key])
					{
						this.options[key] = data.attributes[key];
					}
				}

				// Flagged as needing to edit settings?  Open dialog
				if(typeof data.edit_settings != 'undefined' && data.edit_settings)
				{
					this.edit_settings();
				}
			},
			this, true, this
		).sendRequest();

		// Extend, not replace, because settings has types while value has just value
		jQuery.extend(this.options.settings, value);
	},

	/**
	 * Remove this portlet from the home page
	 */
	remove_portlet: function() {
		var self = this;
		et2_dialog.show_dialog(function(button_id) {
				if(button_id != et2_dialog.OK_BUTTON) return;
				self._process_edit(button_id, '~remove~');
				self._parent.removeChild(self);
				self.destroy();
			},"Remove", this.options.title,{},
			et2_dialog.BUTTONS_OK_CANCEL, et2_dialog.QUESTION_MESSAGE
		);
	},

	/**
	 * Set the HTML content of the portlet
	 *
	 * @param value String HTML fragment
	 */
	set_value: function(value)
	{
		this.content.html(value);
	},

	/**
	 * Set the content of the header
	 *
	 * @param value String HTML fragment
	 */
	set_title: function(value)
	{
		this.options.title = value;
		this.header.html(value);
	},

	/**
	 * Set the number of grid cells this widget spans
	 * 
	 * @param value int Number of horizontal grid cells
	 */
	set_width: function(value)
	{
		this.options.width = value;
		this.div.attr("data-sizex", value);
	},
	
	/**
	 * Set the number of vertical grid cells this widget spans
	 * 
	 * @param value int Number of vertical grid cells
	 */
	set_height: function(value)
	{
		this.options.height = value;
		this.div.attr("data-sizey", value);
	}
	
});
et2_register_widget(et2_portlet, ["portlet"]);
