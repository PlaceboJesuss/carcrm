$(function(){
	$.get("/cars/get-autocompleates").then((res)=>{
		const data = res.result.autocompleates;
		for(type in data){
			const tt = type;
			$(document).on('keydown.autocomplete', `[data-id="${tt}"]`, function() { $(this).autocomplete({source: data[tt] }); });
		}
	});
 });
$(document).on("click",".button-car-save", {url:"/cars/save"},sendForm );