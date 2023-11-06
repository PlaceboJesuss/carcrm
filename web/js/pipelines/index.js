function addLead(){
    const pipeline_id = $("body").attr("data-id");
	$.get("/leads/get-new-item", {pipeline_id}).then((res)=>{
		openModal(res);
	})
}


$(document).on("click","#add-lead", addLead);
$(document).on("click",".button-add-lead-save",{url:"/leads/add-new"}, sendForm)