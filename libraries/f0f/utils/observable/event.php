<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  utils
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('F0F_INCLUDED') or die;

/**
 * Defines an observable event.
 *
 * This class is based on JEvent as found in Joomla! 3.2.0
 */
abstract class F0FUtilsObservableEvent extends F0FUtilsObject
{
    /**
     * Event object to observe.
     *
     * @var    object
     */
    protected $_subject = null;

    /**
     * Constructor
     *
     * @param   object  &$subject  The object to observe.
     */
    public function __construct(&$subject)
    {
        // Register the observer ($this) so we can be notified
        $subject->attach($this);

        // Set the subject to observe
        $this->_subject = &$subject;
    }

    /**
     * Method to trigger events.
     * The method first generates the even from the argument array. Then it unsets the argument
     * since the argument has no bearing on the event handler.
     * If the method exists it is called and returns its return value. If it does not exist it
     * returns null.
     *
     * @param   array  &$args  Arguments
     *
     * @return  mixed  Routine return value
     */
    public function update(&$args)
    {
        // First let's get the event from the argument array.  Next we will unset the
        // event argument as it has no bearing on the method to handle the event.
        $event = $args['event'];
        unset($args['event']);

        /*
         * If the method to handle an event exists, call it and return its return
         * value.  If it does not exist, return null.
         */
        if (method_exists($this, $event))
        {
            return call_user_func_array(array($this, $event), $args);
        }
        else
        {
            return null;
        }
    }
}
