function openModal(html) {
    $('body').append(
        `
		<div class="modal">
			<div class="modal-background"></div>
            <div class="columns">
			<div class="modal-content column is-10">
				<div class="box">` + html + `</div>
			</div>
            </div>
			<button class="modal-close is-large" aria-label="close"></button>
		</div>
		`
    );
}

function openModalYesNo(func, parameters = {}, label = "Вы уверены, что хотите выполнить это действие?") {
    const text = `
	<div class="modal">
		<div class="modal-background"></div>
		<div class="modal-content">
			<div class="box">
				<label class="box subtitle has-text-danger">
					${label}
				</label>
				<div class="mt-6 is-flex is-justify-content-space-around">
				<button id="yes-button" class="button is-dark is-outlined">Да</button>
				<button id="no-button" class="button is-dark is-outlined">Нет</button>
				</div>
			</div>
		</div>
		<button class="modal-close is-large" aria-label="close"></button>
	</div>
	`
    let html = $(text);
    $('body').append(html);
    html.on("click", "#yes-button",
        function(event) {
            $(event.target).addClass('is-loading');
            func(parameters);
            html.hide(200, () => html.remove());
        });
    html.on("click", "#no-button", () => {
        html.hide(200, () => html.remove());
    });
}

function sendForm(event){
    const $fields_id = $(event.target).parents(".form").find(".send-field");
    const $fields_list = $(event.target).parents(".form").find(".send-field-list")
    $fields_id.removeClass('is-danger');
    $fields_list.removeClass('is-danger');
    var data_item = {};
    for(field of $fields_id){
        id = $(field).attr("data-id");
        switch(field.type){
            case "checkbox" : data_item[id] = field.checked; break;
            default: data_item[id] = field.value;break;
        }
    }
    data_item['list'] = [];
    for(field of $fields_list){
        switch(field.type){
            case "checkbox" : value = field.checked; break;
            case "radio" : value = field.checked; break;
            default: value = field.value;break;
        }
        data_item['list'].push({id:$(field).attr("data-id"), value:value});
    }
    const url = event.data.url[0] == "/" ? event.data.url : `${location.href}/${event.data.url}`; 
    $.post(url, { data: data_item }).then(
        (a) => {
            showAlert(a.result.text,a.status, 1000);
            if(a.result.error_fields){
                $fieldss = $.merge($fields_id, $fields_list);
                for(er of a.result.error_fields){
                    $fieldss.filter(`[data-id='${er}']`).addClass('is-danger');
                }
            }
            if(a.status == "success"){
                if(a.result['redirect']){
                    setTimeout(()=>{document.location.pathname = a.result['redirect']},1000);
                }else{
                    setTimeout(()=>{location.reload()},1000);

                }
            }
        }
    );
}

function showAlert(text, type, time) {

    const modal = $(`
	<div class="modal">
		<div class="modal-background"></div>
		<div class="modal-content">
			<div class="box">
				<label class="label ${type == "error" ? "has-text-danger" : ""} ">
					${text}
				</label>
			</div>
		</div>
	</div>
	`);

    $('body').append(modal);
    setTimeout(() => modal.remove(), time);
}

function addPipelineClick(){
    $.get("/pipelines/get-new-modal").then(res=>openModal(res));
}
$(document).on("click", ".button-add-pipeline-save",{url:"/pipelines/add"}, sendForm);
$(document).on("click", ".modal-close", event => $(event.target).parent().remove());
$(document).on("click", ".button-add-pipeline", addPipelineClick);