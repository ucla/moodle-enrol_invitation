<?php
define("ACCEPTABLE_PREFIX_LENGTH", 8);
define("ACCEPTABLE_PREFIX_REGEX", "_[a-zA-Z0-9]{1,8}_");
class PrefixUtil {
    
    function PrefixUtil()
    {
    
    }
    
    /**
     * Returns the prefix for user, group, class, resource ids given an account
     * (or LC Admin User name).
     * 
     * @param account $ 
     * @return the prefix for user, group, class, resource ids.
     * @throws PrefixException
     */
    function getPrefix($account)
    {
        if ($account == "") 
        {
            return "";
        } 
    
        if (strpos($account, '_') === false) 
        { // do not apply prefix rule
            return "";
        } 
        else 
        {
            if (!$this->isPrefixValid($account)) 
            {
                return null;
            } 
    
            return $account; // return account which contains the starting "_"
        } 
    } 
    
    /**
     * Checks that the prefix is valid agains the regexp ACCEPTABLE_PREFIX_REGEX
     * 
     * @param prefix $ The prefix to be checked
     * @return true If the prefix is valid
     */
    function isPrefixValid($prefix)
    {
        return ($prefix != null && ereg(ACCEPTABLE_PREFIX_REGEX, $prefix) > 0);
    } 
    
    /**
     * Removes the prefix from the value
     * 
     * @param value $ 
     * @return Trimed value, witout the prefix.
     * @throws PrefixException
     */
    function trimPrefix($value, $prefix)
    {
        if ($prefix == "") 
        {
            return $value;
        } 
    
        if (strpos($value, $prefix) === false) //the room_id doesnt contain prefix
        {
            return $value;
        } 
        else 
        {
            return substr($value, strlen($prefix));
        } 
    } 
}
?>
