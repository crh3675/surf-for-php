<?

function getNewsStories($topic = '')
{
	$story = $stories[] = new stdClass();
	$story->photo = '/images/photos/091216-feature-03-hp.jpg';
	$story->title = 'Bahraini reforms focus on women, economy';
	$story->intro = 'Bahrainis on Wednesday celebrated their National Day and looked back on ten years of reforms under the reign of King Hamad bin Isa Al Khalifa.';

	$story = $stories[] = new stdClass();
	$story->photo = '/images/photos/091216-feature-02-hp.jpg';
	$story->title = 'Saudi women seek election to commerce board despite hardliners\' opposition';
	$story->intro = 'While conservatives say it will lead to "moral degeneration", Saudi businesswomen say elections will help them eliminate business obstacles for women investors.';
	
	$story = $stories[] = new stdClass();
	$story->photo = '/images/photos/091216-feature-03-hp.jpg';
	$story->title = 'Bahraini reforms focus on women, economy';
	$story->intro = 'Bahrainis on Wednesday celebrated their National Day and looked back on ten years of reforms under the reign of King Hamad bin Isa Al Khalifa.';

	return $stories;
}

?>