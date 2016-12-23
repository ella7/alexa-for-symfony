<?php

namespace Alexa\Request;

use DateTime;
use DateTimeZone;
use DateInterval;
use Exception;

/**
 * This class contains convenience functions to help get the right DateTime objects from slots
 * in an Alexa\Request. 
 * 
 * @author Ryan Packer
 */
class DateTimeDurationSlotHandler
{

  /**
   * Array of slots for the intent
   *
   * @var array
   */
  protected $slots;

  /**
   * The timezone to be applied to the provided Alexa time and date information
   *
   * @var DateTimeZone
   */
  protected $timezone;
  
  
  /**
   * Contructor for Alexa\Request\DateTimeDurationSlotHandler 
   * 
   * @param array             $slots
   * @param DateTimeZone      $timezone
   */
  public function __construct($slots, DateTimeZone $timezone)
  {
    $this->slots      = $slots;
    $this->timezone   = $timezone;
  }
  
  /**
   * Get the value for a slot 
   *
   * @return mixed
   */
  public function getSlot($slot_key)
  {
    return array_get($this->slots, $slot_key.'.value'); 
  }

  /**
   * Get a PHP DateTime object from the slot 
   *
   * @param string    $slot_key
   *
   * @return mixed
   */
  public function getDateTimeFromSlot($slot_key)
  {
    $slot_value = $this->getSlot($slot_key);

    if($slot_value){
      return new DateTime($slot_value, $this->timezone);
    }
    return null;
  }
  
  /**
   * Get a PHP DateInterval object from the slot 
   *
   * @param string    $slot_key
   *
   * @return mixed
   */
  public function getDateIntervalFromSlot($slot_key)
  {
    $slot_value = $this->getSlot($slot_key);

    if($slot_value){
      return new DateInterval($slot_value);
    }
    return null;
  }
  
  /**
   * Get a PHP DateTime object from AMAZON.DURATION slot where the duration represents how long 
   * in the past or future from now the returned PHP DateTime object should represent. If duration
   * slot is not set, the function returns the starting DateTime which defaults to "now".
   *
   * @param string    $duration_slot_key
   * @param bool      $before
   * @param DateTime  $starting_date_time 
   *
   * @return DateTime
   */
  public function getDateTimeFromDurationSlot($duration_slot_key, $before = true, $starting_date_time = null)
  {
    if($starting_date_time){
      $date_time = $starting_date_time;
    } else {
      $date_time = new DateTime(null, $this->timezone);
    }

    if($duration = $this->getDateIntervalFromSlot($duration_slot_key)){
      if($before){
        $date_time->sub($duration);
      } else {
        $date_time->add($duration);
      }
    };
    return $date_time;
  }

  /**
   * Intelligentyly get a PHP DateTime object after looking for a date, time, and duration slot
   * of types AMAZON.DATE, AMAZON.TIME, and AMAZON.DURATION respectively. The DateTime defaults 
   * to now and is then adjusted to the given duration/interval before or after the time specified 
   * by the date and time slots.
   *
   * @param array   $slot_keys  must provide $slot_keys for 'date', 'time', and 'duration'
   * @param bool    $before
   *
   * @return DateTime
   */
  public function getDateTimeFromSlots($slot_keys = ['date'=>'date', 'time'=>'time', 'duration'=>'duration'], $before = true)
  {
    $date_time = new DateTime(null, $this->timezone);

    if(array_key_exists('date', $slot_keys) && $date = $this->getDateTimeFromSlot($slot_keys['date'])){
      $date_time->setDate($date->format('Y'), $date->format('m'), $date->format('d'));
    };
    
    if(array_key_exists('time', $slot_keys) && $time = $this->getDateTimeFromSlot($slot_keys['time'])){
      $date_time->setTime($time->format('H'), $time->format('i'), $time->format('s'));
    };
    
    if(array_key_exists('duration', $slot_keys)){
      $date_time = $this->getDateTimeFromDurationSlot($slot_keys['duration'], $before, $date_time);
    }  
    
    return $date_time;

  }

}