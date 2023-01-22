(function($) {
	$( document ).ready( function() {

		$('.toplevel_page_wlds-support').click(function (e) {
			e.preventDefault();
			modal_show();
		});

		$('.plugins-php .row-actions .wlds-help').click(function (e) {
			e.preventDefault();
			modal_show();
		});
	});

	function modal_show() {

		// Prevent Double Modals
		if (typeof window.wlds_support_modal !== 'undefined') {
			return;
		}

		$('#wlds-support-admin-notice-success').remove();

		window.wlds_support_modal = new tingle.modal({
			footer: true,
			cssClass: ['wlds-support-modal-c'],
			closeMethods: ['overlay', 'button', 'escape'],
			closeLabel: wlds_support.text_button_support_cancel,
			onClose: function () {
				window.wlds_support_modal.destroy();
				delete(window.wlds_support_modal);
			}
		});

		// Set Content
		window.wlds_support_modal.setContent(document.querySelector('#support-modal').innerHTML);

		// Set Footer Buttons
		window.wlds_support_modal.addFooterBtn(wlds_support.text_button_support_send, 'tingle-btn tingle-btn--primary tingle-btn--pull-right', function () {
			send_support_form();
		});

		window.wlds_support_modal.addFooterBtn(wlds_support.text_button_support_cancel, 'tingle-btn tingle-btn--default tingle-btn--pull-right', function () {
			window.wlds_support_modal.close();
		});

		window.wlds_support_modal.open();
	}

	function send_support_form() {
		if ($('.wlds-support-modal-c').find('#'+wlds_support.field_id_support_message).prop('disabled')) {
			return;
		}

		// Get Message Content
		var message = $('.wlds-support-modal-c').find('#'+wlds_support.field_id_support_message).val();

		// Disable Field and Buttons
		$('.wlds-support-modal-c').find('#'+wlds_support.field_id_support_message).attr('disabled','disabled');
		$('.wlds-support-modal-c').find('.tingle-modal-box__footer button').attr('disabled','disabled');

		var request = $.ajax({
			type: 'POST',
			url: wlds_support.ajaxurl,
			dataType: 'json',
			data: {
				action: wlds_support.action,
				message: message,
				security: wlds_support.security
			},
			timeout: wlds_support.timeout
		});

		request.always(function (dataOrjqXHR, textStatus, jqXHRorErrorThrown) {
			// Disable Field and Buttons
			$('.wlds-support-modal-c').find('#'+wlds_support.field_id_support_message).removeAttr('disabled');
			$('.wlds-support-modal-c').find('.tingle-modal-box__footer button').removeAttr('disabled');
		});

		request.done(function (response, textStatus, jqXHR) {
			if (true == response.success) {
				window.wlds_support_modal.close();
				console.log('WOOT');
				console.log(response.data.message);
				show_admin_notice_success(response.data.message)
			} else {
				if (typeof response.data.message === 'undefined') {
					response.data.message = "ERROR";
				}
				modal_error(response.data.message)
			}
		});

		request.fail(function (jqXHR, textStatus) {
			console.log('ERROR');
			console.log(textStatus);
			console.log(jqXHR);
			modal_error('ERROR')
		});

	}

	function modal_error(message) {
		$('.wlds-support-modal-c').find('.error_message').show().text(message);
	}

	function show_admin_notice_success(message) {
		$('<div class="notice notice-success is-dismissible" id="wlds-support-admin-notice-success"><p>'+message+'</p></div>').insertAfter('#wlds-support-notice-placeholder');
	}

})( jQuery );
