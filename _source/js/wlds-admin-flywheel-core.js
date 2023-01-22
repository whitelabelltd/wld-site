window.onload = function () {
	jQuery( ".wrap ul.core-updates li" ).replaceWith('<p>'+wlds_f.field_text+'</p>');
	jQuery( ".wrap h3:first" ).text(wlds_f.field_text_core);
};
