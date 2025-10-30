<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_after_bot_payer', __NAMESPACE__.'\afterBotPrayer');
function afterBotPrayer($args){
	$family	= new SIM\FAMILY\Family();

    // calendar events
    $events		= new DisplayEvents();

	// add normal events
    $events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));
    foreach($events->events as $event){
        $startYear	= get_post_meta($event->ID, 'celebrationdate', true);

        //only add events which are not a celebration and start today after curent time
        if(empty($startYear) && $event->startdate == date('Y-m-d') && $event->starttime > date('H:i', current_time('U'))){
            $args['message']    .= "\n\n".$event->post_title.' starts today at '.$event->starttime;
            if(!empty($event->location)){
                $args['message']    .= "\nIt takes place at $event->location";
            }
            $args['urls'][]	    = get_permalink($event->ID);
        }
    }

    // add aniversaries
	$anniversaryMessages = getAnniversaries();

	//If there are anniversaries
	if(!empty($anniversaryMessages)){
		$args['message'] .= "\n\nToday is the ";

		$messageString	= '';

		//Loop over the anniversary_messages
		foreach($anniversaryMessages as $userId=>$msg){
			$msg	        = html_entity_decode($msg);

			$userdata		= get_userdata($userId);

			// user does not exist
			if(!$userdata){
				continue;
			}

            // Add to the message
			if(!empty($messageString)){
				$messageString .= " and the ";
			}

			$coupleString	= getCoupleString($userdata);

			$msg	        = replaceCoupleString($msg, "of $coupleString", $userdata);

			$msg	        = str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

			$messageString .= $msg;

            // User page url
			$url			= SIM\maybeGetUserPageUrl($userId);
			if($url){
				$args['urls'][]	= str_replace('https://', '', $url);
			}

            // add appropriate picture
			if(str_contains($msg, '&')){
				$family	= new SIM\FAMILY\Family();
				$picture	= $family->getFamilyMeta($userId, 'picture');

				if(is_numeric($picture)){
                    $args['pictures'][] = get_attached_file($picture);
				}
			}else{
                $profilePicture	= get_user_meta($userId, 'profile_picture', true);
                if(is_array($profilePicture) && isset($profilePicture[0])){
                    $args['pictures'][] = get_attached_file($profilePicture[0]);
                }elseif(is_numeric($profilePicture)){
					$args['pictures'][] = get_attached_file($profilePicture);
				}
            }
		}
		$args['message'] .= $messageString.'.';
	}

	$arrivalUsers = getArrivingUsers();
	
	//If there are arrivals
	if(!empty($arrivalUsers)){
		if(count($arrivalUsers) == 1){
			$args['message'] 	.= "\n\n".$arrivalUsers[0]->display_name." arrives today.";
			$args['urls'][]		= str_replace('https://', '', SIM\maybeGetUserPageUrl($arrivalUsers[0]->ID))."\n";
		}else{
			$args['message'] .= "\n\nToday the following people will arrive: ";

			//Loop over the arrival_users to find any families
			$skip	= [];
			foreach($arrivalUsers as $user){
				if(in_array($user->ID, $skip)){
					continue;
				}

				$name		= $family->getFamilyName($user, false, $partnerId);

				if($partnerId){
					$skip[]		= $partnerId;

                    $picture	= $family->getFamilyMeta($userId, 'picture');

                    if($picture){
                        $args['pictures'][] = get_attached_file($picture);
                    }
				}else{
                    $profilePicture	= get_user_meta($user->ID, 'profile_picture', true);
                    if(isset($profilePicture[0])){
                        $args['pictures'][] = get_attached_file($profilePicture[0]);
                    }
                }

				$args['message'] 	.= "$name\n";
				$args['urls'][] 	= str_replace('https://', '', SIM\maybeGetUserPageUrl($user->ID));
			}
		}
	}

	return $args;
}