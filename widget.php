<?php
/*
Plugin Name: Multi Twitter Stream by wsyms
Plugin URI: http://wordpress.org/extend/plugins/multi-twitter-stream-by-wsyms/
Description: A widget for multiple twitter accounts listed by the latest tweet
Author: William Syms
Version: 1.3.4
Author URI: http://williamsyms.com
*/
 
function human_time($datefrom, $dateto = -1)
{
	// Defaults and assume if 0 is passed in that its an error rather than the epoch
	if ( $datefrom <= 0 )
		return "A long time ago";
		
	if ( $dateto == -1) { 
		$dateto = time(); 
	}
	
	// Calculate the difference in seconds betweeen the two timestamps
	$difference = $dateto - $datefrom;
	
	// If difference is less than 60 seconds use 'seconds'
	if ( $difference < 60 )
	{ 
		$interval = "s"; 
	}
	// If difference is between 60 seconds and 60 minutes use 'minutes'
	else if ( $difference >= 60 AND $difference < (60*60) )
	{
		$interval = "n"; 
	}
	// If difference is between 1 hour and 24 hours use 'hours'
	else if ( $difference >= (60*60) AND $difference < (60*60*24) )
	{
		$interval = "h"; 
	}
	// If difference is between 1 day and 7 days use 'days'
	else if ( $difference >= (60*60*24) AND $difference < (60*60*24*7) )
	{
		$interval = "d"; 
	}
	// If difference is between 1 week and 30 days use 'weeks'
	else if ( $difference >= (60*60*24*7) AND $difference < (60*60*24*30) )
	{
		$interval = "ww";
	}
	// If difference is between 30 days and 365 days use 'months'
	else if ( $difference >= (60*60*24*30) AND $difference < (60*60*24*365) )
	{
		$interval = "m"; 
	}
	// If difference is greater than or equal to 365 days use 'years'
	else if ( $difference >= (60*60*24*365) )
	{
		$interval = "y"; 
	}
	
	// Based on the interval, determine the number of units between the two dates
	// If the $datediff returned is 1, be sure to return the singular
	// of the unit, e.g. 'day' rather 'days'
	switch ($interval)
	{
		case "m" :
			$months_difference = floor($difference / 60 / 60 / 24 / 29);
			
			while(
				mktime(date("H", $datefrom), date("i", $datefrom),
				date("s", $datefrom), date("n", $datefrom)+($months_difference),
				date("j", $dateto), date("Y", $datefrom)) < $dateto)
			{
				$months_difference++;
			}
			$datediff = $months_difference;
	
			// We need this in here because it is possible to have an 'm' interval and a months
			// difference of 12 because we are using 29 days in a month
			if ( $datediff == 12 )
			{ 
				$datediff--; 
			}
	
			$res = ($datediff==1) ? "$datediff month ago" : "$datediff months ago";
			
			break;
	
		case "y" :
			$datediff = floor($difference / 60 / 60 / 24 / 365);
			$res = ($datediff==1) ? "$datediff year ago" : "$datediff years ago";

			break;
	
		case "d" :
			$datediff = floor($difference / 60 / 60 / 24);
			$res = ($datediff==1) ? "$datediff day ago" : "$datediff days ago";
			
			break;
	
		case "ww" :
			$datediff = floor($difference / 60 / 60 / 24 / 7);
			$res = ($datediff==1) ? "$datediff week ago" : "$datediff weeks ago";

			break;
	
		case "h" :
			$datediff = floor($difference / 60 / 60);
			$res = ($datediff==1) ? "$datediff hour ago" : "$datediff hours ago";

			break;
	
		case "n" :
			$datediff = floor($difference / 60);
			$res = ($datediff==1) ? "$datediff minute ago" : "$datediff minutes ago";
	
			break;
	
		case "s":
			$datediff = $difference;
			$res = ($datediff==1) ? "$datediff second ago" : "$datediff seconds ago";
			
			break;
	}

	return $res;
}

function format_tweet($tweet, $options) 
{
	if ( $options['reply'] )
	    $tweet = preg_replace('/(^|\s)@(\w+)/', '\1@<a href="http://www.twitter.com/\2">\2</a>', $tweet);
	
	if ( $options['hash'] )
	    $tweet = preg_replace('/(^|\s)#(\w+)/', '\1#<a href="http://search.twitter.com/search?q=%23\2">\2</a>', $tweet);
	
	if( $options['links'] )
		$tweet = preg_replace('#(^|[\n ])(([\w]+?://[\w\#$%&~.\-;:=,?@\[\]+]*)(/[\w\#$%&~/.\-;:=,?@\[\]+]*)?)#is', '\1<a href="\2">\2</a>', $tweet);
	
	return $tweet;
}

function multi_twitter($widget) 
{
	// Create our HTML output var to return
	$output = ''; 
	
	$accounts = explode(" ", $widget['users']);
	
	if ( ! $widget['user_limit'] )
	{
		$widget['user_limit'] = 5; 
	}
	
	$feed_limit = $widget['user_limit'];
		
	// var
	$a = array();
	$a2 = array();
	$i = 0;
	
	$target = "_blank"; // Possible options: _blank, _parent, _self, _top
	$url = "https://api.twitter.com/1/statuses/user_timeline.xml?screen_name=";
	
	// For each tweets
	for($j = 0; $j < count($accounts); $j++)
	{
		// Build URL
		$u[$j] = $url . $accounts[$j] . "&count=10";
		
		// Load XML Feed from URL
		$xml[$j] = simplexml_load_file($u[$j]);

		foreach($xml[$j]->children() as $c1)
		{
			foreach($c1->children() as $c2)
			{
				foreach($c2->children() as $c3)
				{
					if ($c3->getName() == "profile_image_url")
					{
						$pfurl = $c3;
						$a[$i][0] = $pfurl;
					}
					
					if ($c3->getName() == "name")
					{
						$n = $c3;
						$a[$i][5] = $n;
					}
					
					if ($c3->getName() == "screen_name")
					{
						$sn = $c3;
						$a[$i][1] = $sn;
					}
				}
				
				if ($c2->getName() == "created_at")
				{
					$date = strtotime($c2);
					$a[$i][2] = $date;
					$a2[$i] = $date;
				}
				if ($c2->getName() == "id")
				{
					$id = $c2;
					$a[$i][3] = $id;
				}
				if ($c2->getName() == "text")
				{
					//$tweet = parseTweet($c2);
					$tweet = $c2;
					$a[$i][4] = $tweet;
				}
			}
			$i++;
		}
	}
	
	// Order by latest tweet
	rsort($a2);
	
	$output .= '<ul class="twitter">';
	
	// Show the latest
	for ($i = 0; $i < $feed_limit; $i++)
	{
		for ($j = 0; $j < count($a); $j++)
		{
			if ($a2[$i] == $a[$j][2])
			{
				$output .= '<li class="tweet clearfix">';
				$output .= '<a class="tweet-a" href="http://www.twitter.com/' . $a[$j][1] . '/status/' . $a[$j][3] . '" target="' . $target . '"><img class="avatar left" src="' . $a[$j][0] . '">';
				$output .= '<div class="tweet-content">';
				$output .= '<p class="tweet-h1">' . $a[$j][5] . '</a>';
				if ( $widget['date'] === true )
				{
					$output .=  '<small class="sml"> - ' . human_time($a[$j][2]) . '</small>';
					
				}
				$output .= '</p>';
				$output .= '<p class="tweet-p">' . format_tweet($a[$j][4], $widget) . '</p>';
				$output .= '</div>';
				$output .= '</li>';
			}
		}
	}
	
	$output .= '</ul>';
	
	if ( $widget['credits'] === true )
	{
		$output .= 
			'<hr />'.
			'<p class="footer-p">Built by <a href="http://www.williamsyms.com/" target="_blank">@wsyms</a></p>';
	}
	
	if ( $widget['stylez'] == 'Dark' )
	{
	$output .= 
		'<style type="text/css">'.
		'.avatar { width: 48px; height: 48px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; }'.
		'.twitter { list-style: none; margin: 0; padding: 0; }'.
		'.clearfix::after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }'.
		'.tweet-h1 { padding-top: 7px; font-size: 12px; line-height: 0px; color: #454545; font-family: Arial,sans-serif; font-weight: bold; margin-bottom: 10px; }'.
		'.tweet-h1 a { color: #454545; }'.
		'.tweet-h1 a:hover { color: #333; text-decoration:none; }'.
		'.sml { color: #8A8A8A; }'.
		'.tweet-p { font-size: 11px; line-height: 14px; color: #333; font-family: Arial,sans-serif; word-wrap: break-word; }'.
		'.tweet-p a { color: ' . $widget['color'] . '; }'.
		'.tweet-p a:hover { color: ' . $widget['color'] . '; }'.
		'.tweet-a { text-decoration: none; }'.
		'.tweet-content { margin-left: 58px; word-wrap: break-word; display: block; }'.
		'.tweet { height: 60px; }'.
		'.left { float: left; }'.
		'.right { float: right; }'.
		'.footer-p { font-size: 11px; line-height: 14px; color: #333; font-family: Arial,sans-serif; }'.
		'.footer-p a { color: ' . $widget['color'] . '; }'.
		'.footer-p a:hover { color: ' . $widget['color'] . '; }'.
		'</style>';
	} else if ( $widget['stylez'] == 'Light' ) {
	$output .= 
		'<style type="text/css">'.
		'.avatar { width: 48px; height: 48px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; }'.
		'.twitter { list-style: none; margin: 0; padding: 0; }'.
		'.clearfix::after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }'.
		'.tweet-h1 { padding-top: 7px; font-size: 12px; line-height: 0px; color: #fff; font-family: Arial,sans-serif; font-weight: bold; margin-bottom: 10px; }'.
		'.tweet-h1 a { color: #fff; }'.
		'.tweet-h1 a:hover { color: #fff; text-decoration:none; }'.
		'.tweet-p { font-size: 11px; line-height: 14px; color: #fff; font-family: Arial,sans-serif; word-wrap: break-word; }'.
		'.tweet-p a { color: ' . $widget['color'] . '; }'.
		'.tweet-p a:hover { color: ' . $widget['color'] . ';}'.
		'.tweet-a { text-decoration: none; }'.
		'.tweet-content { margin-left: 58px; word-wrap: break-word; display: block; }'.
		'.tweet { height: 60px; }'.
		'.left { float: left; }'.
		'.right { float: right; }'.
		'.footer-p { font-size: 11px; line-height: 14px; color: #fff; font-family: Arial,sans-serif; }'.
		'.footer-p a { color: ' . $widget['color'] . '; }'.
		'.footer-p a:hover { color: ' . $widget['color'] . '; }'.
		'</style>';
	}
	echo $output;
}

function widget_multi_twitter_wsyms($args) 
{
	extract($args);

	$options = get_option("widget_multi_twitter_wsyms");
	
	if ( ! is_array($options)) 
	{	 
		$options = array(
			'title' 		=> 'Multi Twitter',
			'users' 		=> 'wsyms twitter',
			'user_limit' 	=> 5,
			'stylez' 		=> 'Dark',
			'color' 		=> '#0088CC',
			'links' 		=> true,
			'reply' 		=> true,
			'hash'			=> true,
			'credits'		=> true,
			'date'			=> true
		);  
	}  

	echo $before_widget;
	echo $before_title;
    echo $options['title'];
	echo $after_title;

	multi_twitter($options);
	
	echo $after_widget;
}

function multi_twitter_control() 
{
	$options = get_option("widget_multi_twitter_wsyms");
	
	if ( ! is_array($options)) 
	{ 
		$options = array(
			'title' 		=> 'Multi Twitter',
			'users' 		=> 'wsyms twitter',
			'user_limit' 	=> 5,
			'stylez' 		=> 'Dark',
			'color' 		=> '#0088CC',
			'links' 		=> true,
			'reply'			=> true,
			'hash'			=> true,
			'credits'		=> true,
			'date'			=> true
		); 
	}  

	if ( $_POST['multi_twitter-Submit'] ) 
	{
		$options['title'] = htmlspecialchars($_POST['multi_twitter-Title']);
		$options['users'] = htmlspecialchars($_POST['multi_twitter-Users']);
		$options['user_limit'] = $_POST['multi_twitter-UserLimit'];
		$options['stylez']	= $_POST['multi_twitter-Stylez'];
		$options['color'] = $_POST['multi_twitter-Color'];
		$options['hash']	= ($_POST['multi_twitter-Hash']) ? true : false;
		$options['reply']	= ($_POST['multi_twitter-Reply']) ? true : false;
		$options['links']	= ($_POST['multi_twitter-Links']) ? true : false;
		$options['credits']	= ($_POST['multi_twitter-Credits']) ? true : false;
		$options['date']	= ($_POST['multi_twitter-Date']) ? true : false;
		
		update_option("widget_multi_twitter_wsyms", $options);
	}
?>
	<p>
		<label for="multi_twitter-Title">Widget Title: </label><br />
		<input type="text" class="widefat" id="multi_twitter-Title" name="multi_twitter-Title" value="<?php echo $options['title']; ?>" />
	</p>
	<p>	
		<label for="multi_twitter-Users">Users: </label><br />
		<input type="text" class="widefat" id="multi_twitter-Users" name="multi_twitter-Users" value="<?php echo $options['users']; ?>" /><br />
		<small><em>enter accounts separated with a space</em></small>
	</p>
	<p>
		<label for="multi_twitter-UserLimit">Limit user feed to: </label>
		<select id="multi_twitter-UserLimit" name="multi_twitter-UserLimit">
			<option value="<?php echo $options['user_limit']; ?>"><?php echo $options['user_limit']; ?></option>
			<option value="1">1</option>
			<option value="2">2</option>
			<option value="3">3</option>
			<option value="4">4</option>
			<option value="5">5</option>
			<option value="6">6</option>
			<option value="7">7</option>
			<option value="8">8</option>
			<option value="9">9</option>
			<option value="10">10</option>
		</select>
	</p>
	<p>
		<label for="multi_twitter-Stylez">Style: </label>
		<select id="multi_twitter-Stylez" name="multi_twitter-Stylez">
			<option value="<?php echo $options['stylez']; ?>"><?php echo $options['stylez']; ?></option>
			<option value="Light">Light</option>
			<option value="Dark">Dark</option>
		</select>
	</p>
	<p>
		<label for="multi_twitter-Color">Link color: </label><br />
		<input type="text" class="widefat" id="multi_twitter-Color" name="multi_twitter-Color" value="<?php echo $options['color']; ?>" /><br />
		<small><em>enter hex color code ex: #000000</em></small>
	</p>
	<p>
		<label for="multi_twitter-Links">Automatically convert links?</label>
		<input type="checkbox" name="multi_twitter-Links" id="multi_twitter-Links" <?php if ($options['links']) echo 'checked="checked"'; ?> />
	</p>
	<p>
		<label for="multi_twitter-Reply">Automatically convert @replies?</label>
		<input type="checkbox" name="multi_twitter-Reply" id="multi_twitter-Reply" <?php if ($options['reply']) echo 'checked="checked"'; ?> />
	</p>
	<p>
		<label for="multi_twitter-Hash">Automatically convert #hashtags?</label>
		<input type="checkbox" name="multi_twitter-Hash" id="multi_twitter-Hash" <?php if ($options['hash']) echo 'checked="checked"'; ?> />
	</p>
	<p>
		<label for="multi_twitter-Credits">Show Credits?</label>
		<input type="checkbox" name="multi_twitter-Credits" id="multi_twitter-Credits" <?php if ($options['credits']) echo 'checked="checked"'; ?> />
	</p>
	<p>
		<label for="multi_twitter-Date">Show Date?</label>
		<input type="checkbox" name="multi_twitter-Date" id="multi_twitter-Date" <?php if ($options['date']) echo 'checked="checked"'; ?> />
	</p>
	<p><input type="hidden" id="multi_twitter-Submit" name="multi_twitter-Submit" value="1" /></p>
<?php
}

function multi_twitter_init() 
{
	register_sidebar_widget('Multi Twitter', 'widget_multi_twitter_wsyms');
	register_widget_control('Multi Twitter', 'multi_twitter_control', 250, 250);	
}

add_action("plugins_loaded", "multi_twitter_init");
?>