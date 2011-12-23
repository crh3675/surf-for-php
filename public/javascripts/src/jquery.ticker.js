// 
//  jquery.ticker.js
//  
//  Created by Craig Hoover on 2009-12-17.
//  Copyright 2009 GDIT. All rights reserved.
// 
// A better ticker that doesn't crap up your document
// with extra classes and unnecessary markup or truncate your tags
//
// Example:
// $('#headlines ul').ticker({interval:3500,direction:'ltr',pause:3500,cursor:'▒'});
//

$.fn.extend({
	ticker:function(ops)
	{
		$(this).each(function(){
			
			var s = {interval:3500,direction:'ltr',pause:1500,cursor:'_',cursorWidth:12,cursorColor:'#000000',start:null,baseZ:100,_node:this,_index:0,_entry:null};
			var $node = $(this), $kids = $node.children();
			var w = parseInt($node.width()), h = parseInt($node.height());
		
			// extend/overwrite our settings with user specified
			$.extend(s, ops || {});	s._width  = w; s._height = h;
			$.extend(this,{_ticker:s});

			// normalize our container to hide and position content	
			$node.css({position:'relative',zIndex:s.baseZ,overflow:'hidden'});
			$kids.hide().each(function(i){ $(this).css({position:'absolute',height:h,width:0,top:this.offsetTop,left:0,zIndex:s.baseZ + $kids.length - i})});

			switch(s.direction)
			{
				case 'rtl': $kids.css({left:w}); break;
				default: 	$kids.css({left:0}); break;
			}
		
			$.extend(this._ticker,{_entries: $kids.length, _index: s.start || s._index});

			$node.__runticker.call(this);
		});		
	},
	__runticker:function()
	{		
		var ops = this._ticker;
		var node = this;
		var $node = $(this);		
		var count = $node.children().length;

		ops._index = ops.start ? ops.start : ops._index;		
		ops._index = ops._index < 0 ? count-1 : ops._index;
		ops._index = ops._index >= count ? 0 : ops._index;		

		var $entry = $($node.children()[ops._index]);
		var $kids = $entry.children();
		var $cursor = $('<span>').addClass('cursor').css({position:'absolute',zIndex:ops.baseZ + ops._entries, top:0,height:ops._height,width:ops.cursorWidth,color:ops.cursorColor}).append(ops.cursor);
		$entry.append($cursor);
		
		switch(ops.direction)
		{
			case 'rtl': $cursor.css({textAlign:'left',left:0}); break;
			default: 	$cursor.css({textAlign:'right',right:0}); break;
		}

		// we will need out text width later
		var _width = $entry.textWidth();	

		// we will need our current item for later as well
		this._ticker._entry = $entry.get(0);

		// run ticker animation - we use a width to show text as appending text
		// causes clickability issues in some browsers
		$entry.animate({width:ops._width,left:0},ops.interval,function(){
			
			// the stride flag tells us our context is longer than the display area
			var stride 	= _width > ops._width ? true : false;
			
			// use text width which is longer than box width if stride is set
			var width 	= stride ? _width : ops._width; 
			
			// we will reposition our box using the offset difference from the box width
			var offset 	= width - ops._width; 
			
			// calculate our new left position, 0 if no stride
			var left 	= stride ? (ops.direction != 'ltr' ? 0 : (-1) * offset) : 0; 
			
			// calculate new speed, 0 if no stride (no animation)
			var speed 	= stride ? Math.abs(Math.floor(ops.interval * offset / ops._width)) : 0; 

			// run the stride animation - does nothing if stride is not set since speed = 0
			$entry.animate({width:width, left:left}, speed,function(){
				$entry.children('.cursor').remove();				
				$entry.animate({top:0},ops.pause,function(){
					ops._index = ops._index + 1;
					$.extend(node._ticker, ops);
					$(this).hide().css({width:0,left:ops.direction == 'rtl' ? ops._width : 0});
					$node.__runticker.call(node);
				});		
			});			
		});		
	},
	tickernext:function()
	{
		$(this).each(function(){
			$(this).__tickernav.call(this,'next');
		});
	},
	tickerprev:function()
	{
		$(this).each(function(){
			$(this).__tickernav.call(this,'prev');
		});	
	},
	__tickernav:function(dir)
	{
		var ops = this._ticker;
		$(ops._entry).stop().hide().css({width:0,left:ops.direction == 'rtl' ? ops._width : 0});
		this._ticker.start = (dir == 'prev' ? this._ticker._index-1 : this._ticker._index+1);
		$(this).__runticker.call(this);		
	}
});

// my little helper function to get text width
$.fn.extend({
	textWidth:function()
	{
		var chars = $(this).text().replace(/^\s+|\s+$/g,'');
		var $span = $('<span>').css({visibility:'hidden',position:'absolute',whiteSpace:'nowrap'}).text(chars);
		$('body').append($span);
		var width = parseInt($span.width());	
		$span.remove();	
		return width;	
	}
});