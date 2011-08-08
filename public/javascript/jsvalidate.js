//
// +----------------------------------------------------------------------+
// | Unobtrusive Javascript Validation for Prototype. v2.0 (2007-03-04)   |
// | http://blog.jc21.com                                                 |
// +----------------------------------------------------------------------+
// | Attaches Events to all forms on a page and checks their form         |
// | elements classes to provide some validation.                         |
// +----------------------------------------------------------------------+
// | Copyright: jc21.com 2008                                             |
// +----------------------------------------------------------------------+
// | Licence: Absolutely free. Don't mention it.                          |
// +----------------------------------------------------------------------+
// | Author: Jamie Curnow <jc@jc21.com>                                   |
// +----------------------------------------------------------------------+
//
//


if (typeof(JSV) == 'undefined') {
    var JSV = false;

    /* :NOTE: All advanced JSV JS functionality is assumed to require the Prototype JS library */
    if (typeof($) == 'function') {
        JSV = {
            Init:     { },
            Validate: { },
            Lang:     { }
        };
    }



    if (JSV) {

        JSV.Init = {
            initialisers: [ ],
            unloaders: [ ],

            add: function(add_fn) {
                JSV.Init.initialisers.push(add_fn);
            },

            addUnloader: function(add_fn) {
                JSV.Init.unloaders.push(add_fn);
            },

            remove: function(remove_fn) {
                var last_init = JSV.Init.initialisers.length - 1;

                for (var i = last_init; i >= 0; i--) {
                    if (JSV.Init.initialisers[i] === remove_fn) {
                        JSV.Init.initialisers[i] = null;
                    }
                }
            },

            removeUnloader: function(remove_fn) {
                var last_unloader = JSV.Init.unloaders.length - 1;

                for (var i = last_unloader; i >= 0; i--) {
                    if (JSV.Init.unloaders[i] === remove_fn) {
                        JSV.Init.unloaders[i] = null;
                    }
                }
            },

            run: function() {
                var last_init = JSV.Init.initialisers.length - 1;

                for (var i = 0; i <= last_init; i++) {
                    if (typeof(JSV.Init.initialisers[i]) == 'function') {
                        JSV.Init.initialisers[i]();
                    }
                }
            },

            runUnload: function() {
                var last_unloader = JSV.Init.unloaders.length - 1;

                for (var i = 0; i <= last_unloader; i++) {
                    if (typeof(JSV.Init.unloaders[i]) == 'function') {
                        JSV.Init.unloaders[i]();
                    }
                }
            }
        };


        JSV.Validate = {

            initialised: false,

            init: function() {
                if (!JSV.Validate.initialised) {
                    $$('form').each(function(elm) {
                        Event.observe(elm, 'submit', JSV.Validate.checkForm);
                    });
                    JSV.Validate.initialised = true;
                }
            },

            checkForm: function(e) {
                var all_valid = true;
                var errs      = new Array();
            	var frm       = e.element();
                var frm_elms  = frm.getElements();

            	frm_elms.each(function(elm) {
            	    var apply_classes = true;
            	    var valid         = true;

            	    if (JSV.Validate.isVisible(elm)) {
                	    if (elm.nodeName.toLowerCase() == 'input') {
                	        var type  = elm.type.toLowerCase();
                	        if (type == 'text' || type == 'password') {
                                valid = JSV.Validate.input(elm);
                            } else if (type == 'radio' || type == 'checkbox') {
                                valid = JSV.Validate.radio(elm, frm);
                            }
                	    } else if (elm.nodeName.toLowerCase() == 'textarea') {
                            valid = JSV.Validate.input(elm);
                        } else if (elm.nodeName.toLowerCase() == 'select') {
                            valid = JSV.Validate.select(elm);
                        } else {
                            apply_classes = false;
                        }

                        if (valid && apply_classes) {
        					elm.removeClassName('validation-failed');
        					elm.addClassName('validation-passed');
        				} else if (apply_classes) {
        					elm.removeClassName('validation-passed');
        					elm.addClassName('validation-failed');
        					//try to get title for error message
        					if (elm.getAttribute('title')){
        						errs[errs.length] = elm.getAttribute('title');
        					}
        					all_valid = false;
        				}
            	    }
            	});

            	if (!all_valid) {
            		if (errs.length > 0){
            			alert(JSV.Lang.getString('error_message_default') + "\n\n  * "+errs.join("\n  * ")+"\n\n"+JSV.Lang.getString('error_message_end'));
            		} else {
            			alert(JSV.Lang.getString('error_message_default'));
            		}
            		Event.stop(e);
            	}
            	return all_valid;
            },

            isVisible: function(elm) {
                if (typeof elm == "string") {
                    elm = $(elm);
            	}

            	while (elm.nodeName.toLowerCase() != 'body' && elm.getStyle('display').toLowerCase() != 'none' && elm.getStyle('visibility').toLowerCase() != 'hidden') {
            		elm = elm.parentNode;
            		Element.extend(elm);
            	}
            	if (elm.nodeName.toLowerCase() == 'body') {
            		return true;
            	} else{
            		return false;
            	}
            },

            input: function(elm) {
            	var text  = elm.value.strip();

            	if (elm.hasClassName('required') && text.length == 0) {
            	    return false;
            	} else if (elm.hasClassName('required')) {
                    var m = elm.getAttribute('minlength');
            		if (m && Math.abs(m) > 0){
            			if (text.length < Math.abs(m)){
            				return false;
            			}
            		}
                } else if (text.length == 0) {
                    return true;
                }

                //search for validate-
                if (elm.hasClassName('validate-number') && isNaN(text) && text.match(/[^\d]/)) {
                    //number bad
                    return false;
                } else if (elm.hasClassName('validate-digits') && text.replace(/ /,'').match(/[^\d]/)) {
                    return false;
                } else if (elm.hasClassName('validate-alpha') && !text.match(/^[a-zA-Z]+$/)) {
                    return false;
                } else if (elm.hasClassName('validate-alphanum') && text.match(/[\W]/)) {
                    return false;
                } else if (elm.hasClassName('validate-date')) {
            		var d = new date(text);
            		if (isNaN(d)) {
            			return false;
            		}
            	} else if (elm.hasClassName('validate-email') && !text.match(/\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/)) {
            		return false;
            	} else if (elm.hasClassName('validate-url') && !text.match(/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i)) {
            		return false;
            	} else if (elm.hasClassName('validate-date-au') && !text.match(/^(\d{2})\/(\d{2})\/(\d{4})$/)) {
            		return false;
            	} else if (elm.hasClassName('validate-currency-dollar') && !text.match(/^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/)) {
            		return false;
                } else if (elm.hasClassName('validate-regex')) {
                    var r = RegExp(elm.getAttribute('regex'));
                    if (r && ! text.match(r)) {
                        return false;
                        
                    }
                }
                return true;
            },

            radio: function(elm, frm) {
                var valid = true;
            	//search for required
            	if (elm.hasClassName('validate-one-required')) {
            		//check if other checkboxes or radios have been selected.
            		valid = false;
            		frm.select('input[name="'+elm.name+'"]').each(function(inp) {
            		    if (inp.checked) {
            		        valid = true;
            		    }
            		});
            	}
            	return valid;
            },

            select: function(elm) {
                if (elm.hasClassName('validate-not-first') && elm.selectedIndex == 0) {
                    return false;
                } else if (elm.hasClassName('validate-not-empty') && elm.options[elm.selectedIndex].value.length == 0) {
                    return false;
                }
                return true;
            }

        };


        JSV.Lang = {
            strings: {
                'error_message_start':      'Os seguinte erros foram encontrados:',
                'error_message_end':        'Verifique o campo em destaque e tente novamente.',
                'error_message_default':    'Os campos em destaque não foram preencidos da forma correta, preencha-os adequadamente antes de continuar.'
            },

            getString: function(string_code, params) {
                var orig_code = string_code;
                if (typeof(JSV.Lang.strings[string_code]) != 'undefined') {
                    return JSV.Lang.strings[string_code];
                } else {
                    return '(Unknown String: ' + (orig_code != string_code ? string_code + ' via ' : '') + orig_code + ')';
                }
            }
        };



        JSV.Init.add(JSV.Validate.init);

        Event.observe(window, 'load',   JSV.Init.run);
        Event.observe(window, 'unload', JSV.Init.runUnload);
    }
}

function load() {
    $('loading').show();
}
