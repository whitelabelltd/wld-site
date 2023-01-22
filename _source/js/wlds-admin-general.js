window.onload = function () {
	jQuery( "input#siteurl" ).attr('readonly', true);
	jQuery( "input#siteurl" ).parent().append( "<p class='description'>"+wlds_f.field_text+"</p>" );
	jQuery( "input#home" ).attr('readonly', true);
	jQuery( "input#home" ).parent().children("p").text(wlds_f.field_text);
};
