function validateNumber(event){
	var regex = /^([0-9]+(\.[0-9]+)?)?$/;
	if(!regex.test(event.target.value)){
		$(event.target).addClass("is-danger");
	}else{
		$(event.target).removeClass("is-danger");
	}
}  

function validateEmail(event){
	var regex = /^([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)?$/;
	if(!regex.test(event.target.value)){
		$(event.target).addClass("is-danger");
	}else{
		$(event.target).removeClass("is-danger");
	}
}  

function validatePhone(event){
	var regex = /^(\+[0-9]{11,12}?)?$/;
	if(!regex.test(event.target.value)){
		$(event.target).addClass("is-danger");
	}else{
		$(event.target).removeClass("is-danger");
	}
}
function focusPhone(event){
	if(event.target.value.length == 0 ){
		event.target.value = "+";
		$(event.target).addClass("is-danger");
	}
}
function focusoutPhone(event){
	if(event.target.value.length == 1 && event.target.value == "+"){
		event.target.value = "";
		$(event.target).removeClass("is-danger");
	}
}

$(document).on("input",".input-number, .input-money", validateNumber);
$(document).on("input",".input-email", validateEmail);
$(document).on("input",".input-phone", validatePhone);
$(document).on("focus",".input-phone", focusPhone);
$(document).on("focusout",".input-phone", focusoutPhone);