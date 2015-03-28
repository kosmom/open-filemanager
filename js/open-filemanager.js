var times=false;

function select(object,folder){
    $('.selected').removeClass('selected');
    $(object).addClass('selected');
    if (!times){
        times=true;
        setTimeout('times=false;', 1000);
    }else{
        // dblclick
        if ($(object).is('.folder')){
            set_folder(object,folder);
        }else if ($(object).data('choose')=='y'){
            set_image(object);
        }
    }
}
function set_folder(object,folder){
    var prmstr = window.location.search.substr(1);
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        params[tmparr[0]] = tmparr[1];
    }
	params['folder']=(typeof(folder)!='undefined'?folder:$(object).data('folder')+$(object).find('b').text());
	prmarr=[];
	for ( var i in params) {
		prmarr.push(i+'='+params[i]);
    }
	location.search='?'+prmarr.join('&');
}
function create_folder(){
    var name=prompt('Укажите название папки');
    if (!name)return ;
    $.post(location.href,{act:'add_folder',folder:name,csrf:csrf},function(data){
        if (data=='done')location.reload(); else alert('Ошибка: '+data);
    });
}
function delete_(){
    var name=$('.selected b').text();
    if (!name)return false;
    if (name=='..'){
        alert('Нельзя удалить эту папку');
        return ;
    }
    if (!confirm('Файл/папка будет удалена безвозвратно. Хотите продолжить?'))return false;
    $.post(location.href,{act:'delete',name:name,csrf:csrf},function(data){
        if (data=='done')$('.selected').remove(); else alert('Ошибка: '+data);
    });
}
function rename(){
    var name=$('.selected b').text();
    if (!name)return false;
    if (name=='..'){
        alert('Нельзя переименовывать эту папку');
        return ;
    }
    var newname=prompt('Укажите новое имя файла/папки',name);
    if (!newname)return false;
    $.post(location.href,{act:'rename',name:name,newname:newname,csrf:csrf},function(data){
        if (data=='done')$('.selected b').text(newname); else alert('Ошибка: '+data);
    });
}
function open_module(module_file){
	get_pic_win= window.open(module_file, "open-filemanager-module", "width=800,height=450,status=no,toolbar=no,menubar=no,scrollbars=no");
}
$(function(){
$('.dark').click(function(){
    $(this).fadeOut(1000);
})
})


// Some global instances
var tinymce = null, tinyMCEPopup, tinyMCE;

tinyMCEPopup = {
	init : function() {
		var t = this, w = t.getWin(), ti;

		// Find API
		tinymce = w.tinymce;
		tinyMCE = w.tinyMCE;
		t.editor = tinymce.EditorManager.activeEditor;
		t.params = t.editor.windowManager.params;

		// Setup local DOM
		t.dom = t.editor.windowManager.createInstance('tinymce.dom.DOMUtils', document);
		t.dom.loadCSS(t.editor.settings.popup_css);

		// Setup on init listeners
		t.listeners = [];
		t.onInit = {
			add : function(f, s) {
				t.listeners.push({func : f, scope : s});
			}
		};

		t.isWindow = !t.getWindowArg('mce_inline');
		t.id = t.getWindowArg('mce_window_id');
		t.editor.windowManager.onOpen.dispatch(t.editor.windowManager, window);
	},

	getWin : function() {
		return window.dialogArguments || opener || parent || top;
	},

	getWindowArg : function(n, dv) {
		var v = this.params[n];
		return tinymce.is(v) ? v : dv;
	},

	getParam : function(n, dv) {
		return this.editor.getParam(n, dv);
	},


	close : function() {
		var t = this;
		t.dom = t.dom.doc = null;
		t.editor.windowManager.close(window, t.id);
	}

};

function set_image(object){
    var val=$(object).data('folder')+$(object).find('b').text();
	if (typeof(choose_function)!='undefined'){
		if (typeof(opener[choose_function])=='function'){
			opener[choose_function](val);
			return window.close();
		}
	}else{
	try {
    var parentMCE = parent.tinymce;
	if (typeof(parentMCE)!=='undefined'){
        if (typeof(parentMCE.activeEditor)){
			if (typeof(parentMCE.activeEditor.windowManager.getParams)!='undefined'){
	            var args = parentMCE.activeEditor.windowManager.getParams();
	            parent.document.getElementById(args['input']).value = val;
	            parentMCE.activeEditor.windowManager.close();
	            return true;
	        }
		}
    }
	}catch (e) { void(e); }

	if (location.search.indexOf('CKEditorFuncNum')!=-1){
		var funcnum=getQueryStringParam('CKEditorFuncNum');
		opener.CKEDITOR.tools.callFunction(funcnum, val);
		close();
		return true;
	}

	tinyMCEPopup.init();
    var win = tinyMCEPopup.getWindowArg("window");
        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = val;
        //for image browsers
        try { win.ImageDialog.showPreviewImage(url); }
        catch (e) { void(e); }
        tinyMCEPopup.close();
	}
}
function getQueryStringParam(name) {
	var regex = new RegExp('[?&]' + name + '=([^&]*)'),
		result = window.location.search.match(regex);

	return (result && result.length > 1 ? decodeURIComponent(result[1]) : null);
}
