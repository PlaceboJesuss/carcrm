function sendNote(event){
const $butt = $(event.currentTarget);
const value = $butt.parents("#send-note-container").find("textarea")[0].value;
const type = $("body").attr("data-type");
const id = $("body").attr("data-id");

$.post("/leads/send-note", {id, type, value}).then(()=>{
	location.reload();
});
}
function clearTextarea(event){
	const $butt = $(event.currentTarget);
	$butt.parents("#send-note-container").find("textarea")[0].value = "";
}
function buttonAddContactClick(event){
	$.post("/contacts/get-list", {is_select:true}).then(
	(html)=>
	{
		openModal(html);
	}
	)
}

function buttonDetachContactClick(event){
	const contact_id = $(event.currentTarget).attr("data-id");
	const lead_id = $("body").attr("data-id");
	const data = {contact_id, lead_id};
	$.post("/leads/detach-contact", data).then(
		(res)=>
		{	
			showAlert(res.result.text, res.status, 1000);
			setTimeout(()=>{location.reload()}, 1000);
		}
	)
}

function selectContactClick(event){
	const contact_id = $(event.currentTarget).attr("data-id");
	const lead_id = $("body").attr("data-id");
	const data = {contact_id, lead_id};
	console.log(data);
	$.post("/leads/attach-contact", data).then(
		(res)=>
			{	
				showAlert(res.result.text, res.status, 1000);
				setTimeout(()=>{location.reload()}, 1000);
			}
	)
}

function searchButton(event){
	const $butt = $(event.currentTarget);

	const value = $butt.parents(".field:first").find(".input-search").val();
	$("#list").load(`/contacts/get-list`, {search:value, inner:true,is_select:true},()=>{$butt.removeClass("is-loading");});
	$(".remove-list-button").addClass("is-hidden");
}

function searchInput(event){
	if(event.key == "Enter"){
		const $input = $(event.currentTarget);
		const $butt = $input.parents(".field:first").find(".button-input-search");
		const value = $input.val();

		$butt.addClass("is-loading");
		$("#list").load(`/contacts/get-list`, {search:value, inner:true,is_select:true},()=>{$butt.removeClass("is-loading");});
		$(".remove-list-button").addClass("is-hidden");
	}
}

function taskCreateClick(event){
	const lead_id = $(event.currentTarget).attr("data-id");
	$.get("/tasks/get-create-modal", {lead_id}).then(
		(res)=>{ openModal(res) }
	);
}

function confirmTaskClick(event){
	const id = $(event.currentTarget).attr("data-id");
	$.get("/tasks/get-confirm-modal", {id}).then(
		(res)=>{
			openModal(res);
		}
	)
}

function cancelTaskClick(event){
	const id = $(event.currentTarget).attr("data-id");
	$.post("/tasks/cancel",{id}).then((res)=>{
		showAlert(res.result.text, res.status, 1000);
		setTimeout(()=>{location.reload()}, 1000);
	})
}

function deleteLeadClick(event){
	openModalYesNo(()=>{
		const lead_id = $(event.currentTarget).attr("data-id");
		$.post("/leads/delete", {lead_id}).then((res)=>{
			showAlert(res.result.text, res.status, 1000);
			setTimeout(()=>{location.pathname = "/leads"}, 1000);
		})
	});

}

function detachCarClick(event){
	const lead_id = $("body").attr("data-id");
	$.post("/leads/detach-car", {lead_id}).then((res)=>{
		showAlert(res.result.text, res.status, 1000);
		setTimeout(()=>{location.reload()}, 1000);
	})
}

function addCarClick(event){
	$.post("/cars/get-list", {is_select:true}).then(
	(html)=>
		{
			openModal(html);
		}
	)
}

function selectCarClick(event){
	const car_id = $(event.currentTarget).attr("data-id");
	const lead_id = $("body").attr("data-id");
	const data = {car_id, lead_id};
	console.log(data);
	$.post("/leads/attach-car", data).then(
		(res)=>
			{	
				showAlert(res.result.text, res.status, 1000);
				setTimeout(()=>{location.reload()}, 1000);
			}
	)
}

$(document).on("click",".is-select-car", selectCarClick);
$(document).on("click",".button-detach-car", detachCarClick)
$(document).on("click",".button-add-car", addCarClick)
$(document).on("click",".button-lead-delete", deleteLeadClick);
$(document).on("click",".button-cancel-task", cancelTaskClick);
$(document).on("click",".button-confirm-task", confirmTaskClick);
$(document).on("click",".button-confirm-task-modal",{url:"/tasks/confirm"}, sendForm);
$(document).on("click",".button-save-task", {url:"/tasks/add"}, sendForm);
$(document).on("click", ".button-task-create", taskCreateClick);
$(document).on("keypress", ".input-search",searchInput);
$(document).on("click", ".button-input-search", searchButton);
$(document).on("click",".is-select-contact", selectContactClick);
$(document).on("click",".button-add-contact", buttonAddContactClick);
$(document).on("click",".button-detach-contact", buttonDetachContactClick);
$(document).on("click",".button-clear-textarea", clearTextarea);
$(document).on("click",".send-note",sendNote);
$(document).on("click",".save-lead",{url:"/leads/save-lead"},sendForm);