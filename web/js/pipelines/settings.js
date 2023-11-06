jQuery.fn.swap = function(b) {
    b = jQuery(b)[0];
    var a = this[0],
        a2 = a.cloneNode(true),
        b2 = b.cloneNode(true),
        stack = this;

    a.parentNode.replaceChild(b2, a);
    b.parentNode.replaceChild(a2, b);

    stack[0] = a2;
    return this.pushStack( stack );
};

function moveStatus(event){
	let block1 = $(event.target).parents(".status");
	let name1 = block1.find(".edit-name");
	if(event.data.up)
		block2 = block1.prev();
	else
		block2 = block1.next();
	let name2 = block2.find(".edit-name");
	name1.swap(name2);
}

function deleteStatus(event){
	const block = $(event.target).parents(".status");
	const id = block.find(".edit-name").attr("data-id");
	if(id != ""){
		openModalYesNo(()=>{
            $.post("/pipelines/delete-status", {id}).then(()=>{
            block.remove();
            actualMoveButton();
            });
        },0,"Все данные о сделках в этом статусе будут утеряны. Вы уверены?");
	}else{
        block.remove();
        actualMoveButton();
    }
}

function actualMoveButton(){
    statuses = $("#statuses-form .status");
    statuses.find(".is-hidden").removeClass("is-hidden");
    statuses.first().find(".to-up").addClass("is-hidden");
    statuses.last().find(".to-down").addClass("is-hidden");
}
setTimeout(actualMoveButton,0);

function addStatus(){
    $.get("/pipelines/get-new-status").then(
        res=>{
            $("#add-status-block").before($(res));
            actualMoveButton();
        }
    );
}

function deletePipelineClick(event){
    openModalYesNo(function(){
    const id = $(event.currentTarget).attr("data-id");
    $.post("/pipelines/delete", {id}).then(
        (res)=>
            {	
                showAlert(res.result.text, res.status, 1000);
                setTimeout(()=>{location.pathname="/leads"}, 1000);
            }
    );
        },{},"Все данные из этой воронки будут удалены. Вы уверены?");
}

$(document).on("click", ".button-delete-pipeline", deletePipelineClick);
$(document).on('click', "#confirm",{url:"../save", redirect:`${document.location.href.substring(0,document.location.href.length-9)}`},sendForm);
$(document).on('click', "#add-button", addStatus);
$(document).on('click', ".to-up", {up:true}, moveStatus);
$(document).on('click', ".to-down", {up:false}, moveStatus);
$(document).on('click', ".to-delete", deleteStatus);
$(document).on('click', "#add_status", addStatus);