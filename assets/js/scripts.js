$(document).ready(function () {
    $('.spoiler-body').hide(300);

    $(document).on('click', '.spoiler-head', function (e) {
        e.preventDefault();
        $(this).parents('.spoiler-wrap').toggleClass("active").find('.spoiler-body').slideToggle();
    });

    for (let i = 1; i < 9; i++) {
        if ($("#example-" + i + " > .spoiler-wrap").length !== 0 | $("#example-" + i + " > .error-answer").length !== 0) {
            $("#example-" + i).attr("aria-expanded", "true").addClass("in");
        };
    }

    $("#auth-form").submit(function (e) {
        if ($("#username").val() === "" | $("#password").val() === "") {
            e.preventDefault();
        }
    });
});