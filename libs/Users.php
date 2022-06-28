<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Arrays;

class Users
{
    static function getCurrentUserId(){
        return get_current_user_id();
    }

    static function getUserByEmail($email){
        return get_user_by( 'email', $email);
    }

    static function getUserIdByEmail($email){
        $u = get_user_by( 'email', $email);

        if (!empty($u)){
            return $u->ID;
        }
    }

    static function userExistsByEmail($email){
        return !empty( get_user_by( 'email', $email) );
    }
    
    /*
        https://wordpress.stackexchange.com/a/111788/99153
    */
    static function roleExists( $role ) {
        if( ! empty( $role ) ) {
            return $GLOBALS['wp_roles']->is_role( $role );
        }

        return false;
    }

    /**
     * hasRole 
     *
     * function to check if a user has a specific role
     * 
     * @param  string  $role    role to check against 
     * @param  int  $user_id    user id
     * @return boolean
     * 
     * https://wordpress.stackexchange.com/a/111788/99153
     */
    static function hasRole($role, $user_id){
        if ( is_numeric( $user_id ) )
            $user = get_user_by( 'id', $user_id );
        else
            $user = wp_get_current_user();

        if ( empty( $user ) )
            return false;

        return in_array( $role, (array) $user->roles );
    }
}