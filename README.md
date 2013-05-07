## NGP Donations

### Configuration

Go to `Settings -> General` and fill out the "NGP API Key", "Donation Support Phone Line", and "Addt'l Information for Donation Footer" fields.

"NGP API Key" is how this plugin authenticates with your NGP VAN service. You need to make sure the API Credentials string you get from NGP is for the "CWP" API.

"Thanks for Contributing URL" is where the contributor is sent after a successful contribution.

"Donation Support Phone Line" is shown with the error message when the contribution gets rejected by the NGP VAN server.

"Addt'l Information for Donation Footer" might be used for listing things like donation mailing address, donation limits, taxable status of donations, etc.

#### NGP Configuration

Ensure that you have the following suffixes configured in NGP for your constituents/donors.

* Dr.
* Hon.
* Mr.
* Mrs.
* Ms.
* Prof.
* Rep.
* Rev.

Ensure that you have the following volunteer type configured in NGP for your constituents.

* WEBSITE

### Donation Usage

Place this short tag on the appropriate page or article:

	[ngp_show_form]

You can set custom amounts for the donation amount in two ways:

1. Put the amounts in the embed tag: `[ngp_show_form amounts="50,250,1000"]`
2. Put the amounts in a GET querystring: `http://mycamapign.com/donation?amounts=50,250,1000`

If you want to have a default donation amounts, put it in the embed tag and then override it with a querystring amounts when you need to.

You can source an article in two ways:

1. Put source in the embed tag: `[ngp_show_form source="hard-hitting-ad"]`
2. Put the source in a GET querystring: `http://mycamapign.com/donation?source=hard-hitting-ad`

If you want to have a default donation source, put it in the embed tag and then override it with a querystring source when you need to.

### Donation Suggested jQuery

We use the following on our donation pages to make sure that the user understands that the radio buttons and the input field are for the same thing. If the user doesn't support javascript and the custom field holds a value, it always overrides whatever's selected in the radio buttons.

	$('.ngp_custom_dollar_amt').keyup(function() {
		if($(this).val()!='') { $('.Amount').attr('checked', false); }
	});
	$('.Amount').mouseup(function() {
		$('.ngp_custom_dollar_amt').val('');
	});


### Alert!

You should be running your site under an SSL certificate if you utilize this plugin.


### Signup Usage

Place this short tag on the appropriate page or article:

	[ngp_show_signup campaign_id="[Put Campaign ID here]" fields="Email|Zip" main_code="[Put Tag here]" thanks_url="/thanks"]

The `fields` property is a pipe-delimited set of the fields you want to display. A complete set of values is: `Name|Email|Phone|StreetAddress|Zip`.

The `main_code` has to be created ahead of time in NGP: [http://help.ngphost.com/content/creating-coded-landing-pages](http://help.ngphost.com/content/creating-coded-landing-pages)

Steps 1-5 are all you need to do in order to make sure your code is saved in myNGP.com.

The `thanks_url` property is optional and defaults to `/thank-you-for-signing-up`.

### Volunteer Usage

Place this short tag on the appropriate page or article:

	[ngp_show_volunteer thanks_url="/thanks-for-volunteering"]

The `thanks_url` property is optional and defaults to `/thank-you-for-volunteering`