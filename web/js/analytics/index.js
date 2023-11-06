function searchClick(event){
	const date_from = $(".input-date-from").val();
	const date_to = $(".input-date-to").val();
	const user = $(".select-user").val();
	const data = {date_from, date_to, user};
	$(event.currentTarget).addClass("is-loading");
	$("#analytics-inner").load("/analytics/get-inner", data,
		()=>{
			$(event.currentTarget).removeClass("is-loading");
		}
	)
}

function modeChange(event){
	const mode = event.currentTarget.value;
	const date_to = new Date();
	console.log(mode);
	switch(mode){
		case "today":
			date_from = date_to;	
		break;
		case "week":
			day = date_to.getDay();
			date_from = new Date(date_to.getFullYear(),date_to.getMonth(), date_to.getDate()-( day == 0 ? 7 : day)+2);
		break;
		case "full-week":
			date_from = new Date(date_to.getFullYear(),date_to.getMonth(), date_to.getDate()-6);
		break;
		case "month":
			date_from = new Date(date_to.getFullYear(),date_to.getMonth(), 2);	
		break;
		case "full-month":
			date_from = new Date(date_to.getFullYear(),date_to.getMonth()-1,date_to.getDate()+1);		
		break;
		case "year":
			date_from = new Date(date_to.getFullYear(),0,2);
		break;
		case "full-year":
			date_from = new Date(date_to.getFullYear()-1,date_to.getMonth(),date_to.getDate()+1);	
		break;
		case "all-time":
			date_from = new Date(0);	
		break;
		case "set-time":
			date_from = new Date(date_to.getFullYear(),date_to.getMonth(), 2);	
		break;
	}

	$(".input-date-to").val(date_to.toISOString().substring(0,10));
	$(".input-date-from").val(date_from.toISOString().substring(0,10));
	if(mode == "set-time"){
		$(".input-date-from, .input-date-to").prop('disabled', false);
	}else{
		$(".input-date-from, .input-date-to").prop('disabled', true);
	}
}


$(document).on("change", ".select-mode", modeChange);
$(document).on("click", ".button-search", searchClick);