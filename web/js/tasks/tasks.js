
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

function taskCreateClick(event){
	$.get("/tasks/get-create-modal").then(
		(res)=>{ openModal(res) }
	);
}

function changeUser(event){
	const user_id = $(event.currentTarget).val();
	$("#inner-tasks").load("/tasks/get-inner",{user_id});
}

$(document).on("change",".select-user-tasks", changeUser);
$(document).on("click",".button-save-task", {url:"/tasks/add"}, sendForm);
$(document).on("click", ".button-task-create", taskCreateClick);
$(document).on("click",".button-cancel-task", cancelTaskClick);
$(document).on("click",".button-confirm-task", confirmTaskClick);
$(document).on("click",".button-confirm-task-modal",{url:"/tasks/confirm"}, sendForm);