<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'personnelinfo'){
        return 'HR role';
    }
    return $description;
}