function submit(event) {
    let button = $("#button-login");
    let login = $("#login").val();
    let pass = $("#password").val();
    button.addClass('is-loading');
    $.post("/login", { login: login, pass: pass }).then(
        function(data) {
            data = JSON.parse(data);
            console.log(data);
            button.removeClass('is-loading');
            if (data.status == "find") {
                document.location.pathname = "/leads"
            }
            if (data.status == "no_find") {
                showAlert("Логин или пароль неверен", "error", 2000);
            }
        }
    )
}

$(document).on('click', '#button-login', submit);