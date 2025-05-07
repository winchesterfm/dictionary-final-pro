
$(document).ready(function () {
    if (localStorage.getItem("theme") === "dark") {
        $("body").addClass("dark-mode");
        $("#themeToggle").prop("checked", true);
    }

    $("#themeToggle").on("change", function () {
        $("body").toggleClass("dark-mode");
        localStorage.setItem("theme", $("body").hasClass("dark-mode") ? "dark" : "light");
    });
});
