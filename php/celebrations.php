<?php
namespace SIM\EVENTS;
use SIM;

/**
 * Get all the users arriving today
 *
 * @return	array	The WP_Users arriving today
*/
function getArrivingUsers(){
	$date   = new \DateTime();
	return get_users(array(
		'meta_key'     => 'arrival_date',
		'meta_value'   => $date->format('Y-m-d'),
		'meta_compare' => '=',
	));
}

/**
 * Get all the anniversary events
 *
 * @return	array	all messages
*/
function getAnniversaries(){
	$messages = [];

	$events = new DisplayEvents();
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID, 'celebrationdate', true);

		if(is_array($startYear)){
			SIM\cleanUpNestedArray($startYear);

			$startYear	= array_values($startYear)[0];
		}
		
		if(!empty($startYear) && $startYear != date('Y-m-d')){
			$title		= $event->post_title;
			$age		= SIM\getAge($startYear);
			$privacy	= (array)get_user_meta($event->post_author, 'privacy_preference', true);

			if(substr($title, 0, 8) == 'Birthday' && in_array('hide_age', $privacy)){
				$age	= '';
			}
			if(substr($title, 0, 3) != 'SIM'){
				$title	= lcfirst($title);
			}

			//there happen to be more celeberations on the same day for one person
			if(isset($messages[$event->post_author])){
				$name	= get_userdata($event->post_author)->display_name;
				// remove the name of the previous text
				if(str_contains($title, $name)){
					$messages[$event->post_author]	= trim(str_replace($name, '', $messages[$event->post_author]));
				}
				$messages[$event->post_author]	.= ' and the ';
			}else{
				$messages[$event->post_author]	= '';
			}
			$messages[$event->post_author] .= trim("$age $title");
		}
	}

	return $messages;
}

add_action('delete_user', function($userId){
	$events = new CreateEvents();

	//Remove birthday events
	$birthdayPostId = get_user_meta($userId,'birthday_event_id',true);
	if(is_numeric($birthdayPostId)){
		$events->removeDbRows($birthdayPostId);
	}

	$anniversaryId	= get_user_meta($userId, SITENAME.' anniversary_event_id',true);
	if(is_numeric($anniversaryId)){
		$events->removeDbRows($anniversaryId);
	}
});

/**
 *
 * Adds html to the frontpage prayer message
 *
 * @return   string|false     Birthday and arrining usrs html or false if there are no events
 *
**/
add_filter('sim_prayer_message', function($html){
	
	$html	.= anniversaryMessages();

	$html	.= arrivingUsersMessage();
	
	return $html;
});

/**
 * Get the couple string of a certain user
 *
 * @param	int|object				$user		the user or user id
 * @param	int|object|string		$partner	the user object of the users partner. default empty
 *
 * @return	string					The couple string
 */
function getCoupleString($user, $partner=''){
	if(is_numeric($user)){
		$user	= get_userdata($user);
	}

	$family		= get_user_meta($user->ID, 'family', true);
	$lastName	= $user->last_name;

	if(!empty($family['name'])){
		$lastName	= $family['name'];
	}

	if(empty($partner)){
		$partner		= SIM\hasPartner($user->ID, true);

		if(!$partner){
			return "$user->first_name $lastName";
		}
	}
	
	return "$user->first_name & $partner->first_name $lastName";
}

function replaceCoupleString($string, $replaceString, $user, $partner=''){
	if(is_numeric($user)){
		$user	= get_userdata($user);
	}

	if(empty($partner)){
		$partner		= SIM\hasPartner($user->ID, true);

		if(!$partner){
			return $string;
		}
	}

	//Search for first names and last names
	$pattern	= "/((\b$user->first_name\b)|(\b$partner->first_name\b)).*((\b$partner->first_name\b)|(\b$user->first_name\b)).*((\b$partner->last_name\b)|(\b$user->last_name\b))/i";

	return preg_replace($pattern, $replaceString, $string);
}

/**
 *
 * Get the html birthday message
 *
 * @return   string|false     Anniversary html
 *
 */
function anniversaryMessages(){
	$currentUser			= wp_get_current_user();
	$anniversaryMessages 	= getAnniversaries();

	if(empty($anniversaryMessages)){
		return '';
	}

	$messageString	= '';

	//Loop over the anniversary_messages
	foreach($anniversaryMessages as $userId=>$message){
		if(!empty($messageString)){
			$messageString .= " and the ";
		}

		$message	= str_replace('&amp;', '&', $message);

		$partner	= SIM\hasPartner($userId, true);

		$userdata	= get_userdata($userId);
		if(!$userdata){
			continue;
		}

		$addImage	= true;

		if($userId  == $currentUser->ID){
			$coupleLink	= "of you and your spouse my dear $currentUser->first_name!<br>";
			$link		= str_replace($currentUser->display_name, "of you my dear $currentUser->first_name!<br>", $message);

			$addImage	= false;
		}else{
			//Get the url of the user page
			$url		= SIM\maybeGetUserPageUrl($userId);

			$coupleString	= getCoupleString($userdata, $partner);
			$coupleLink		= "of <a href=\"$url\">$coupleString</a>";
			$link			= "of <a href=\"$url\">{$userdata->display_name}</a>";
		}

		if($partner){
			$newMessage	= replaceCoupleString($message, $coupleLink, $userdata, $partner);

			// Add family picture if needed
			if($newMessage != $message && $addImage){
				$message	= $newMessage;
				$family		= get_user_meta($userId, 'family', true);

				if(is_array($family['picture']) && is_numeric($family['picture'][0])){
					$url		= wp_get_attachment_url($family['picture'][0]);
					$picture	= wp_get_attachment_image($family['picture'][0], 'avatar', false, 'style=border-radius: 50%;');
					$message	.= "<a href='$url'>$picture</a>";
				}

				$addImage	= false;
			}
		}
		$message	= str_replace($userdata->display_name, $link, $message);

		if($addImage){
			$profilePicture	= get_user_meta($userId, 'profile_picture', true);
			if(isset($profilePicture[0])){
				$pictureUrl		= wp_get_attachment_url($profilePicture[0]);
				$pictureHtml	= wp_get_attachment_image($profilePicture[0], 'avatar', false, 'style=border-radius: 50%;');
				$message		.= "<a href='$pictureUrl'>$pictureHtml</a>";
			}
		}

		$messageString	.= $message;
	}

	if(empty($messageString)){
		return '';
	}

	$html = '<div name="anniversaries" style="font-size: 18px;">';
		$html .= '<h3>Celebrations</h3>';
		$html .= '<p>';
			$html .= "Today is the $messageString";
		$html .= '.</p>';
	$html .= '</div>';

	return $html;
}

/**
 *
 * Get the arriving users if any
 *
 * @return   string     Arrining users html
 *
*/
function arrivingUsersMessage(){
	$arrivingUsers	= getArrivingUsers();
	$html			= '';

	//If there are arrivals
	if(!empty($arrivingUsers)){
		$html 	.= '<div name="arrivals" style="font-size: 18px;">';
			$html 	.= '<h3>Arrivals</h3>';

			$html .= '<p>';
		
				if(count($arrivingUsers)==1){
					//Get the url of the user page
					$url	 = SIM\maybeGetUserPageUrl($arrivingUsers[0]->ID);
					$html	.= "<a href='$url'>{$arrivingUsers[0]->display_name}</a> arrives today!";
				}else{
					$html 	.= 'The following people arrive today:<br><br>';

					$skip	= [];
					//Loop over the arrivals
					foreach($arrivingUsers as $user){
						if(in_array($user->ID, $skip)){
							continue;
						}

						$name		= SIM\getFamilyName($user, false, $partnerId);

						if($partnerId){
							$skip[]		= $partnerId;
						}
						$url 	 = SIM\maybeGetUserPageUrl($user->ID);
						$html 	.= "<a href='$url'>$name</a><br>";
					}
				}
			$html .= '.</p>';
		$html .= '</div>';
	}

	return $html;
}
