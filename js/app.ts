/*egw:uses
	/api/js/jsapi/egw_app.js;
	/etemplate/js/widget_browser.js;
 */
import {EgwApp} from "../../api/js/jsapi/egw_app";

class etemplateApp extends EgwApp
{
	widgetBrowser = null;

	/**
	 * Constructor
	 *
	 * @memberOf app.status
	 */
	constructor()
	{
		// call parent
		super('etemplate');
	}

	/**
	 * Destructor
	 */
	destroy(_app)
	{
		// call parent
		super.destroy(_app)
	}

	/**
	 * This function is called when the etemplate2 object is loaded
	 * and ready.  If you must store a reference to the et2 object,
	 * make sure to clean it up in destroy().
	 *
	 * @param {etemplate2} _et2 newly ready object
	 * @param {string} _name template name
	 */
	et2_ready(_et2, _name)
	{
		switch(_name)
		{
			case 'etemplate.widgetBrowser':

				this.widgetBrowser = new widget_browser(_et2);
		}
	}

	_widgetBrowser_dtd()
	{
		this.widgetBrowser._dtd_builder();
	}
}
app.classes.etemplate = etemplateApp;
