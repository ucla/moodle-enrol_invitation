// Javascript

$(document).ready( function () {
    $('#dropdown').removeClass('DownArrow ');
    $('#dropdown').addClass('RightArrow ');
    $('#dropdown').click(function (e) {
        e.preventDefault();
        $('.dropdownlist').toggle('fast');
        $('#dropdown').toggleClass('RightArrow ');
        $('#dropdown').toggleClass('DownArrow ');
    });
});
