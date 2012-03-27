/**
 * eGroupWare eTemplate2 - Contains interfaces used inside the dataview
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package etemplate
 * @subpackage dataview
 * @link http://www.egroupware.org
 * @author Andreas Stöckel
 * @copyright Stylite 2012
 * @version $Id$
 */

"use strict";

/*egw:uses
	egw_action.egw_action_common;
	egw_action.egw_action;
*/

/**
 * Contains the action object interface implementation for the nextmatch widget
 * row.
 */

var EGW_SELECTMODE_DEFAULT = 0;
var EGW_SELECTMODE_TOGGLE = 1;

/**
 * An action object interface for each nextmatch widget row - "inherits" from 
 * egwActionObjectInterface
 */
function et2_dataview_rowAOI(_node)
{
	var aoi = new egwActionObjectInterface();

	aoi.node = _node;

	aoi.selectMode = EGW_SELECTMODE_DEFAULT;

	aoi.checkBox = null; //($j(":checkbox", aoi.node))[0];

	// Rows without a checkbox OR an id set are unselectable
	aoi.doGetDOMNode = function() {
		return aoi.node;
	}

	// Prevent the browser from selecting the content of the element, when
	// a special key is pressed.
	$j(_node).mousedown(egwPreventSelect);

	// Now append some action code to the node
	var selectHandler = function(e) {
		// Reset the focus so that keyboard navigation will work properly
		// after the element has been clicked
		egwUnfocus();

		// Reset the prevent selection code (in order to allow wanted
		// selection of text)
		_node.onselectstart = null;

		if (e.target != aoi.checkBox)
		{
			var selected = egwBitIsSet(aoi.getState(), EGW_AO_STATE_SELECTED);
			var state = egwGetShiftState(e);

			switch (aoi.selectMode)
			{
			case EGW_SELECTMODE_DEFAULT:
				aoi.updateState(EGW_AO_STATE_SELECTED,
					!egwBitIsSet(state, EGW_AO_SHIFT_STATE_MULTI) || !selected,
					state);
				break;
			case EGW_SELECTMODE_TOGGLE:
				aoi.updateState(EGW_AO_STATE_SELECTED, !selected,
					egwSetBit(state, EGW_AO_SHIFT_STATE_MULTI, true));
				break;
			}
		}
	};

	if (egwIsMobile()) {
		_node.ontouchend = selectHandler;
	} else {
		$j(_node).click(selectHandler);
	}

	$j(aoi.checkBox).change(function() {
		aoi.updateState(EGW_AO_STATE_SELECTED, this.checked, EGW_AO_SHIFT_STATE_MULTI);
	});

	aoi.doSetState = function(_state) {
		var selected = egwBitIsSet(_state, EGW_AO_STATE_SELECTED);

		if (this.checkBox)
		{
			this.checkBox.checked = selected;
		}

		$j(this.node).toggleClass('focused',
			egwBitIsSet(_state, EGW_AO_STATE_FOCUSED));
		$j(this.node).toggleClass('selected',
			selected);
	}

	return aoi;
}

