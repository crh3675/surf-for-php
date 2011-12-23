// 
//  jquery.glider.js
//  
//  Created by Craig Hoover on 2009-12-17.
//  Copyright 2009 GDIT. All rights reserved.
// 
// A better glider that doesn't crap up your document
// with extra classes and unnecessary markup
//
// Example:
// $('#newsglider').glider({speed:750,pause:5000,direction:'ltr');
//

$.fn.extend({
	glider:function(ops)
	{
		$(this).each(function(){

			// default settings for glider
			var s = {direction:'ltr',speed:250,pause:2000,start:0,baseZ:100,_index:0,_pause:false};
			var $node = $(this), $kids = $node.children();
			var w = parseInt($node.width()), h = parseInt($node.height());
			
			if($kids.length == 1) return;
		
			// extend/overwrite our settings with user specified
			$.extend(s, ops || {});	s._width  = w; s._height = h;
			$.extend(this, {_glider:s});
		
			// normalize our container to hide and position content	
			$node.css({position:'relative',zIndex:s.baseZ,overflow:'hidden'});
			$kids.hide().each(function(i){ $(this).css({position:'absolute',opacity:100,zIndex:s.baseZ + $kids.length - i})});	
		
			// toggle animation directions and reset positions of elements
			switch(s.direction)
			{
				case 'rtl' : $kids.css({left:w,top:0}); break;
				case 'ttb' : $kids.css({top:(-1) * h,left:0}); break;
				case 'btt' : $kids.css({top:h,left:0}); break;	
				case 'ltr' : default: $kids.css({left:(-1) * w,top:0}); break;
			}
	
			// let's start this party!
			$node.__runglider.call(this);	
		});		
		
	},
	gliderplay:function()
	{
		$(this).each(function(){
			var entry = this._glider._current;
			$(entry).animate(entry._revert,this._glider.speed, function(){$(this).hide().css({opacity:100})});
			this._glider._pause = false;
			$(this).__runglider.call(this);	
		});	
	},
	gliderpause:function()
	{
		$(this).each(function(){
			this._glider._pause = true;
		});		
	},
	glidernext:function()
	{
		$(this).each(function(){
			$(this).__glidernav.call(this,'next');
		});
	},
	gliderprev:function()
	{
		$(this).each(function(){
			$(this).__glidernav.call(this,'prev');
		});
	},
	__runglider:function(ops)
	{
		var ops = this._glider;
		var node = this;
		var $node = $(this);
		var count = $node.children().length;
			
		ops._index = ops._index || ops.start;
		ops._index = ops._index < 0 ? count-1 : ops._index;
		ops._index = ops._index >= count ? 0 : ops._index;	
		
		// get our child entry within our parent tag
		var $entry = $($node.children()[ops._index]);
		$entry.show();
		this._glider._current = $entry.get(0);
		
		// reusable function to restore state of entries once animation is done
		var restore = function() 
		{
			$(this).hide().css({opacity:100});
		};

		// run animation directions
		switch(ops.direction)
		{			
			case 'rtl': // Right to left
				var change = {left:0}, revert = {left:ops._width,opacity:0};
			break;

			case 'ttb': // Top to bottom
				var change = {top:0}, revert = {top:(-1) * ops._height,opacity:0};
			break;
			
			case 'btt': // Bottom to top
				var change = {top:0}, revert = {top:ops._height,opacity:0};
			break;
			
			case 'ltr': // Left to right
			default:
				var change = {left:0}, revert = {left: (-1) * ops._width,opacity:0};
			break;
		}

		node._glider._current._revert = revert; // store revert settings
		
		if($.browser.msie) $entry.get(0).style.removeAttribute('filter'); // remove filter so cleartype kicks in
		$entry.animate(change,ops.speed).animate({top:0},ops.pause,function(){			
			$.extend(node._glider, {_index:ops._index+1,start:null});
			if(!node._glider._pause)
			{
				$entry.animate(revert,ops.speed,restore); // return to original state
				$node.__runglider.call(node); // Start the party again!
			}
		});
	},
	__glidernav:function(dir)
	{
		var start = dir == 'prev' ? this._glider._index-1 : this._glider._index+1;
		var entry = this._glider._current;
		start = this._glider._pause ? start -1 : start;

		$(this).gliderpause();
		$.extend(this._glider, {start:start, _index:start});
		$(this).gliderplay();		
	}
});