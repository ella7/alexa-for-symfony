<?php

namespace Alexa\Response;

use JsonSerializable;

/**
 * Reprompt contains a single OutputSpeed property. This object can only be included 
 * when sending a response to a LaunchRequest or IntentRequest.
 * 
 * @author Ryan Packer
 */
class Reprompt implements JsonSerializable
{

	/**
   * The OutputSpeech object used for the Reprompt
   *
   * @var OutputSpeech
   */
  private $outputSpeech;
  
  /**
   * Reprompt constructor.
   *
   * @param string $value
   * @param string $type
   */
	public function __construct($value = '', $type = OutputSpeech::TYPE_PLAINTEXT)
  {
  	$this->outputSpeech = new OutputSpeech($value, $type);
  }
  
  /**
   * This function is called by json_encode and returns an array representing the parts
   * of this object that are intended to exist in the JSON representation. 
   *  
   * @return array 
   */
  public function jsonSerialize()
  {
    $array = [];
    $array['outputSpeech'] = $this->outputSpeech;
    return $array;
  }  

}
