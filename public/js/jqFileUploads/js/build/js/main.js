$(function(){"use strict";var a=$("#fileupload");a.bind("fileuploadsubmit",function(a,b){var c=$("meta[name=gid]").attr("content"),d=$("meta[name=_token]").attr("content"),e=$("meta[name=event_id]").attr("content");b.formData=[{name:"gid",value:c},{name:"event_id",value:e},{name:"_token",value:d}]}),a.fileupload({url:"/photo"}),a.addClass("fileupload-processing"),$.ajax({url:a.fileupload("option","url"),dataType:"json",context:a[0]}).always(function(){$(this).removeClass("fileupload-processing")}).done(function(a){$(this).fileupload("option","done").call(this,$.Event("done"),{result:a})})});