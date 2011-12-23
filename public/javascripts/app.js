var app = 
{	
	init:function()
	{		
		$('#scalable').css({height:$(window).height()});
		
		$(window).bind('resize',function(){
			$('#scalable').css({height:$(window).height()});
		});
	}	
}