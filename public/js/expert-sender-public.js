(function( $ ) {
	'use strict';

	const ECDP_DATA_TAG = 'data-ecdp-field';
	const NEWSLETTER_FORM_SUBMIT_KEY = 'newsletter-submit';
	const EMAIL_INPUT_KEY = 'email';

	$(function() {
		prepareNewsletterConsentForm();
	});
	

	function prepareNewsletterConsentForm() {
		$(`[${ECDP_DATA_TAG}='${NEWSLETTER_FORM_SUBMIT_KEY}']`).each(function () {
			$(this).on('click', submitConsents);
		})
		
	}

	function submitConsents() {
		let email = null;
		$(`[${ECDP_DATA_TAG}='${EMAIL_INPUT_KEY}']`).each(function () {
			if ($(this).val()) {
				email = $(this).val();
			}
		});

		if (null !== email) {
			let data = {
				'action': 'expert_sender_update_newsletter_consents',
				email
			};

			$.post(settings.ajaxurl, data);
		}
		
		return true;
	}

})( jQuery );
