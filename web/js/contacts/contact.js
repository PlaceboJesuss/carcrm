function sendNote(event){
	const $butt = $(event.currentTarget);
	const value = $butt.parents("#send-note-container").find("textarea")[0].value;
	const type = $("body").attr("data-type");
	const id = $("body").attr("data-id");
	
	$.post("/contacts/send-note", {id, type, value}).then(()=>{
		location.reload();
	});
}
function clearTextarea(event){
	const $butt = $(event.currentTarget);
	$butt.parents("#send-note-container").find("textarea")[0].value = "";
}

$(document).on("click",".button-merge-contacts", {url:"/contacts/merge-doubles"}, sendForm);
$(document).on("click",".button-clear-textarea", clearTextarea);
$(document).on("click",".send-note",sendNote);
$(document).on("click",".save-contact",{url:"save-contact"},sendForm);