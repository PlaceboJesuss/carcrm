function openInfo(event) {
    let data_id = $(event.target).attr('data-id');
    $.post('settings/get-settings-modal', { data: { id: data_id } }).then(
        data => {
            openModal(data);
        }
    )

}

function saveUsersSettings(event) {
    const item = $(event.target).parents('.edit-user').find("table");
    if (validate(item, item.attr('data-id') == "")) {
        data = [];
        $(event.target).addClass('is-loading');
        data_item = {
            id: item.attr('data-id'),
            login: item.find("#login").val(),
            password: item.find("#password").val(),
            name: item.find("#name").val(),
            is_admin: item.find("#is_admin")[0].checked,
        }
        $.post("/settings/save", { data: data_item }).then(() => location.reload());
    }
}





function saveSelfUserSettings(event) {
    const item = $(event.target).parents('.edit-user').find("table");
    if (validateSelf(item)) {s
        data = [];
        $(event.target).addClass('is-loading');
        data_item = {
            id: item.attr('data-id'),
            login: item.find("#login").val(),
            old_password: item.find("#old_password").val(),
            new_password: item.find("#new_password").val(),
            name: item.find("#name").val(),
            is_admin: item.find("#is_admin")[0].checked,
        }
        $.post("/settings/save", { data: data_item }).then((a, b, c) =>{
            if(a.show){
                //showAlert(a.)
            }
        });
    }
}

function addUserSettings() {
    $.get("settings/get-settings-modal").then(
        function(data) {
            openModal(data);
        }
    )
}

function validateSelf(table) {
    const name = table.find("#name");
    const login = table.find("#login");
    const old_password = table.find("#old_password");
    const new_password = table.find("#new_password");
    let ok = true;
    if (name.val().length == 0) {
        name.addClass('is-danger');
        ok = false;
    }
    if (login.val().length == 0) {
        login.addClass('is-danger');
        ok = false;
    }
    if (!(old_password.val().length == 0 || new_password.val().length == 0)) {
        old_password.addClass('is-danger');
        new_password.addClass('is-danger');
        ok = false;
    }
    return ok;
}


function validate(table, is_new) {
    const name = table.find("#name").val();
    const login = table.find("#login").val();
    const password = table.find("#password").val();
    let ok = true;
    if (name.length == 0) {
        table.find("#name").addClass('is-danger');
        ok = false;
    }
    if (login.length == 0) {
        table.find("#login").addClass('is-danger');
        ok = false;
    }
    if (password.length == 0 && is_new) {
        table.find("#password").addClass('is-danger');
        ok = false;
    }
    return ok;
}

function deleteUserSettings(event) {
    function deleteUser(id) {
        $.post("/settings/delete-user", { data: { id: id } }).then(
            (data) => {
                data = JSON.parse(data)
                switch (data.status) {
                    case "OK":
                        location.reload();
                        break;
                    case "error":
                        showAlert(data.result, "error", 1000);
                        break;
                }
            }
        );
    }
    const button = $(event.currentTarget);
    openModalYesNo(deleteUser, button.attr('data-id'));
}



function addOption(event){
    $butt = $(event.currentTarget);
    $.post("/settings/get-option").then((res)=>{
        $butt.before($(res));
    });
}

function deleteObject(event){
    const $butt = $(event.currentTarget);
    const $delete = $butt.parents(`${event.data.deleteSelector}:first`);
    const id = $butt[0].id;
    if(id == ""){
        $delete.remove();
        return;
    }
    const data = {
        id:id
    }
    $.post(`/settings/delete-${event.data.type}`, data).then((res)=>{
        if(res.status == "success"){
            $delete.remove();
        }
    });
}

function addCustomField(event){
    entity_id = $(event.currentTarget).parents("article").attr("data_id");
    $.post("/custom-field/get-modal", {entity_id:entity_id}).then((res)=>{openModal(res)});
}

function editCustomField(event){
    $butt = $(event.currentTarget);
    entity_id = $butt.parents("article").attr("data_id");
    id = $butt.parents("tr")[0].id.substring(2);
    $.post("/custom-field/get-modal", {id:id, entity_id:entity_id}).then((res)=>{openModal(res)});
}

function optionSelect(event){
	if(event.target.value == "select"){
		$(".modal .options").removeClass("is-hidden");
	}else{
		$(".modal .options").addClass("is-hidden");

	}
}

function deleteCustomFieldClick(event){
    openModalYesNo(()=>{
        const $butt = $(event.currentTarget);
    const id = $butt.attr("data-id");
    $.post("/custom-field/delete", {id:id}).then((res)=>{
        $butt.parents("tr:first").remove();
    })
});
}

function deleteHookFieldClick(event){
   const id =  $(event.currentTarget).attr("data-id");
   $.post("/hook/delete",{id}).then((res)=>{
    showAlert(res.result.text, res.status, 1000);
    setTimeout(()=>{location.reload()}, 1000);
})
}

function plusHookFieldClick(){
    $.post("/hook/get-add").then((res)=>{
        openModal(res);
    })
}

function generateForm(event){
    $.get("/hook/get-form").then((res)=>{
        openModal(res);
        $(".select-status").trigger("change");
    })
}


function selectStatus(event){
    const k = $(event.currentTarget).val();
    const texarea = $(`.modal textarea`);
    const texarea_content = texarea.val();
    const inner = $(texarea_content);
    inner.find(`input[name="status_id"]`).attr("value",k);
    texarea.val(inner.prop("outerHTML"));
}

function plusVacation(){
    $.get("/settings/get-new-vacation").then((res)=>{ openModal(res) });
}

function editVacation(event){
    id = $(event.currentTarget).attr("data-id");
    $.post("/settings/get-edit-vacation", {id}).then((res)=>{ openModal(res) });
}

function deleteVacation(event){
    id = $(event.currentTarget).attr("data-id");
    $.post("/settings/delete-vacation", {id}).then((res)=>{ 
        showAlert(res.result.text, res.status, 1000);
		setTimeout(()=>{location.reload()}, 1000);
    });
}


$(document).on("click",".button-save-auto-field", {url:"/settings/save-auto-field"}, sendForm);
$(document).on("click",".button-delete-vacation", deleteVacation);
$(document).on("click",".button-edit-vacation", editVacation);
$(document).on("click",".button-save-vacation",{url:"/settings/save-vacation"}, sendForm);
$(document).on("click",".button-plus-vacation", plusVacation);
$(document).on("change",".select-status", selectStatus);
$(document).on("click",".button-generate-form", generateForm);
$(document).on("click",".button-save-hook-field",{url:"/hook/save"}, sendForm)
$(document).on("click",".button-delete-hook-field", deleteHookFieldClick);
$(document).on("click",".button-plus-hook-field", plusHookFieldClick);
$(document).on("click",".button-delete-custom-field", deleteCustomFieldClick);
$(document).on("change",".select-type", optionSelect);
$(document).on("click", ".add-custom-field",addCustomField);
$(document).on("click",".button-delete-option",{type:"option", deleteSelector:".option"}, deleteObject);
$(document).on("click",".add-option", addOption);
$(document).on("click","#save-custom-field", {url:"/custom-field/save"} ,sendForm)
$(document).on('click', ".edit-custom-field", editCustomField);
$(document).on('click', ".users-settings-label", openInfo)
$(document).on('click', ".delete-user-button", deleteUserSettings);
$(document).on("click", "#settings-users-save-button", {url:"/settings/save"} ,sendForm);
$(document).on("click", "#settings-user-save-button",{url:"/settings/save"} ,sendForm);
$(document).on("click", "#settings-user-add-button", addUserSettings);