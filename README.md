## NGP Donations

### Configuration

Go to `Settings -> General` and fill out the "NGP API Key", "Donation Support Phone Line", and "Addt'l Information for Donation Footer" fields.

"NGP API Key" is how this plugin authenticates with your NGP VAN service.

"Thanks for Contributing URL" is where the contributor is sent after a successful contribution.

"Donation Support Phone Line" is shown with the error message when the contribution gets rejected by the NGP VAN server.

"Addt'l Information for Donation Footer" might be used for listing things like donation mailing address, donation limits, taxable status of donations, etc.

### Usage

Place this short tag on the appropriate page or article:

	[ngp_show_form]

### Suggested jQuery

We use the following on our donation pages to make sure that the user understands that the radio buttons and the input field are for the same thing. If the user doesn't support javascript and the custom field holds a value, it always overrides whatever's selected in the radio buttons.

	$('.ngp_custom_dollar_amt').keyup(function() {
		if($(this).val()!='') { $('.Amount').attr('checked', false); }
	});
	$('.Amount').mouseup(function() {
		$('.ngp_custom_dollar_amt').val('');
	});


### Alert!

You should be running your site under an SSL certificate if you utilize this plugin.