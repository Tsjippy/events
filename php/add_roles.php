<?php
namespace SIM\EVENTS;
use SIM;

use const SIM\forms\MODULE_VERSION;

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$roleSet = get_role( 'contributor' )->capabilities;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'personnelinfo' )){
		add_role(
			'personnelinfo',
			'Personnel Info',
			$roleSet
		);
	}

	return $options;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    if($role == 'personnelinfo'){
        return 'HR role';
    }
    return $description;
}, 10, 2);