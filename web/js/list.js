function searchButton(event){
	const $butt = $(event.currentTarget);

	const value = $butt.parents(".field:first").find(".input-search").val();
	$("#list").load(`${location.href}/get-list`, {search:value, inner:true},()=>{$butt.removeClass("is-loading");});
	$(".remove-list-button").addClass("is-hidden");
}

function searchInput(event){
	if(event.key == "Enter"){
		const $input = $(event.currentTarget);
		const $butt = $input.parents(".field:first").find(".button-input-search");
		const value = $input.val();

		$butt.addClass("is-loading");
		$("#list").load(`${location.href}/get-list`, {search:value, inner:true},()=>{$butt.removeClass("is-loading");});
		$(".remove-list-button").addClass("is-hidden");
	}
}

function headerCheckboxChange(event){
	const checked = event.currentTarget.checked;
	if(checked){
		$(".checkbox-list").prop('checked', true);
		$(".remove-list-button").removeClass("is-hidden");
	}
	else{
		$(".checkbox-list").prop('checked', false);
		$(".remove-list-button").addClass("is-hidden");
	}
}

function listCheckboxChange(event){
	const count_checked = $(".checkbox-list:checked").length
	if(count_checked > 0){
		$(".remove-list-button").removeClass("is-hidden");
	}else{
		$(".remove-list-button").addClass("is-hidden");
	}

	if(count_checked >= 2){
		$("#header-checkbox").prop('checked', true);
	}else{
		$("#header-checkbox").prop('checked', false);
	}
}

function openItemClick(event){
	const $target = $(event.target);
	if($target.is("input:checkbox") || $target.find("input:checkbox").length > 0)return true;
	const $tr = $(event.currentTarget);
	const id = $tr.attr("data-id");
	location.href += `/${id}`;
}

function newItem(event){
	const type = $("body").attr("data-type");
	$.get(`/${type}/get-new-item`).then(
		(res)=>{
			openModal(res);
		}
	)
}

function clickDelete(event){
	openModalYesNo(sendForm,event,"Все данные будут утеряны. Вы уверены?");
}



$(document).on("click",".button-add-lead-save",{url:"/leads/add-new"}, sendForm);
$(document).on("click",".button-add-contact-save",{url:"/contacts/add-new"}, sendForm);

$(document).on("click",".add-list-button", newItem);

$(document).on("click",".remove-list-button",{url:"delete"} ,clickDelete);
$(document).on("click",".open-item", openItemClick);
$(document).on("input",".checkbox-list", listCheckboxChange)
$(document).on("input","#header-checkbox", headerCheckboxChange);
$(document).on("keypress", ".input-search",searchInput);
$(document).on("click", ".button-input-search", searchButton);