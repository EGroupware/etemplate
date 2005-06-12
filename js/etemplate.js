/**************************************************************************\
* eGroupWare - EditableTemplates - javascript support functions            *
* http://www.egroupware.org                                                *
* Written by Ralf Becker <RalfBecker@outdoor-training.de>                  *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id$ */

function submitit(form,name)
{
	//alert(name+' pressed');
	form.submit_button.value = name;
	form.submit();
	return false;
}

function set_element(form,name,value)
{
	//alert('set_element: '+name+'='+value);
	for (i = 0; i < form.length; i++)
	{
		if (form.elements[i].name == name)
		{
			//alert('set_element: '+name+'='+value);
			form.elements[i].value = value;
			//alert(name+'='+form.elements[i].value);
		}
	}
}

function set_element2(form,name,vname)
{
	//alert('set_element2: '+name+'='+vname);
	for (i = 0; i < form.length; i++)
	{
		if (form.elements[i].name == vname)
		{
			value = form.elements[i].value;
		}
	}
	//alert('set_element2: '+name+'='+value);
	for (i = 0; i < form.length; i++)
	{
		if (form.elements[i].name == name)
		{
			form.elements[i].value = value;
		}
	}
}

function activate_tab(tab,all_tabs,name)
{
	var tabs = all_tabs.split('|');
	var parts = tab.split('.');
	var last_part = parts.length-1;
	
	for (n = 0; n < tabs.length; n++)
	{
		var t = tabs[n];

		if (t.indexOf('.') < 0 && parts.length > 1) 
		{
			parts[last_part] = t;
			t = parts.join('.');
		}
		document.getElementById(t).style.display = t == tab ? 'inline' : 'none';
		document.getElementById(t+'-tab').className = 'etemplate_tab'+(t == tab ? '_active th' : ' row_on');
	}
	if (name) {
		set_element(document.eTemplate,name,tab);
	}
}

/* proxy to to add options to a selectbox, needed by IE, but works everywhere */
function selectbox_add_option(id,label,value,do_onchange)
{
	selectBox = document.getElementById(id);
	/*alert('selectbox_add_option('+id+','+label+','+value+') '+selectBox);*/
	for (i=0; i < selectBox.length; i++) {
		if (selectBox.options[i].value == value) {
			selectBox.options[i].selected = true;
			break;
		}
	}
	if (i >= selectBox.length) {
		selectBox.options[selectBox.length] = new Option(label,value,false,true);
	}
	if (selectBox.onchange && do_onchange) selectBox.onchange();
}

/* toggles all checkboxes named name in form form, to be used as custom javascript in onclick of a button/image */
function toggle_all(form,name)
{
	/* alert('toggle_all('+form+','+name+',elements[name].length='+form.elements[name].length+')'); */
	var all_set = true;

	for (var i = 0; i < form.elements[name].length; i++)
	{
		if (!form.elements[name][i].checked)
		{
			all_set = false;
			break;
		}
	}
	for (var i = 0; i < form.elements[name].length; i++)
	{
		form.elements[name][i].checked = !all_set;
	}
}
