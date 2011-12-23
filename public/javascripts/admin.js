$(function(){
	
	if($('div.scaffolded_message').length)
	{
		if($('div.scaffolded_message').hasClass('with_error')) return;
		
		setTimeout(function(){
			$('div.scaffolded_message').slideUp('fast');
		},2000);
	}	
	
});