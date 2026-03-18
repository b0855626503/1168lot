$("form").submit(function (event) {
    let token = $('meta[name="csrf-token"]').attr("content");
    $('<input />').attr('type', 'hidden')
        .attr('name', '_token')
        .attr('value', token)
        .appendTo('form');
    return true;
});