function getParameterByName(name) {
    var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

jQuery(document).ready(function()
{
	jQuery('#_myriad_linked_publication').change(refreshRates);	
	jQuery('#updateMyriadPricing').click(updateMyriadPricing);	
});

var refreshRates = function()
{
	var data = {
		'action': 'refresh_rates',
		'publicationID': jQuery('#_myriad_linked_publication').val()
	};

	jQuery('#_myriad_linked_rate').empty();
	jQuery.post(ajaxurl, data, function(response) {
		jQuery.each(response,function(key, value) 
		{
			jQuery('#_myriad_linked_rate').append('<option value=' + key + '>' + value + '</option>');
		});
	});
	}
	
	
var setCurrentRate = function()
{
	var data = {
		'action': 'current_rate_id',
		'postID':getParameterByName("post")
	};
	
	jQuery.post(ajaxurl, data, function(response) {
			if (response)
			{
				jQuery('#_myriad_linked_rate').val(response);
			}
	});
}


var updateMyriadPricing = function()
{
	var data = {
		'action': 'get_rate_price',
		'rateID':jQuery('#_myriad_linked_rate').val(),
		'titleID':jQuery('#_myriad_linked_publication').val()
	};
	
	jQuery.post(ajaxurl, data, function(response) {
			if (response)
			{
				jQuery('#_regular_price').val(response);
			}
	});
	
}



