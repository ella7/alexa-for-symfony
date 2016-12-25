<?php

namespace Alexa\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use JsonSerializable;
use DateTime;
use DateTimeZone;

/**
 * Request represents an HTTP response to be provided to Amazon Alexa.
 * 
 * @author Ryan Packer
 */
class Response extends JsonResponse implements JsonSerializable
{

  const ALEXA_RESPONSE_VERSION = '1.0';

  /**
   * The version specifier for the response
   *
   * @var string
   */
  private $alexa_response_version = self::ALEXA_RESPONSE_VERSION;
  
  /**
   * A key-value pair of session attributes.
   *
   * @var array
   */
  private $sessionAttributes = [];
  
  /**
   * @var OutputSpeech
   */
  private $outputSpeech = null;

  /**
   * @var Card
   */
  private $card = null;
  
  /**
   * @var Reprompt
   */
  private $reprompt = null;
  
  /**
   * A boolean value with true meaning that the session should end, or false if the session should
   * remain active.
   *
   * @var bool
   */
  private $shouldEndSession = true;
  
  /**
   * An array of directives specifying device-level actions to take using a particular interface.
   * Unsupported and unused at this time.
   *
   * @var array
   */
  private $directives = [];
  
  /**
   * Contructor for Alexa\Response - sets the content type and optionally sets the OutputSpeech,
   * Card, and Reprompt before returning self for chaining purposes. 
   * 
   * @param OutputSpeech  $outputSpeech
   * @param Card          $card
   * @param Reprompt      $reprompt
   */
  public function __construct(OutputSpeech $outputSpeech = null, Card $card = null, Reprompt $reprompt = null)
  {
    parent::__construct();
    $this->headers->set('Content-Type', 'application/json');
    $this->outputSpeech = $outputSpeech;
    $this->card 				= $card;
    $this->reprompt			= $reprompt;
    
    $this->setData($this->jsonSerialize());
    
    return $this;
  }
  
  /**
   * Factory for most basic Alexa\Response where OutputSpeech text and Card text are the same.
   * 
   * @param string    $text
   */
  public static function BasicResponse($text = '')
  {
    return new Response(new OutputSpeech($text), new Card($text));
  }  
  
  /**
   * This function is called by json_encode and returns an array representing the parts of this 
   * object that are intended to exist in the JSON representation. 
   *  
   * @return array 
   */
  public function jsonSerialize()
  {
    $alexa_response = [];
    $properties = $this->propertyKeyToResponseKeyMap();
    $this->addPropertiesToAlexaResponse($properties, $alexa_response);
    
    return $alexa_response;
  }  

  /**
   * Adds the $property_key value of $this to the $alexa_response array using the $response_key. 
   *
   * @param string $property_key
   * @param string $response_key   
   * @param &array $alexa_response 
   */
  private function addPropertyToAlexaResponse($property_key, $response_key, &$alexa_response)
  {
    // validate $property_key      
    if(!array_key_exists($property_key, get_object_vars($this))){
      throw new Exception('The first argument $property_key must be a property of an Alexa\Response\Response object. Passed: '.$property_key);
    }
    
    if($this->{$property_key}){
      array_set($alexa_response, $response_key, $this->{$property_key});
    }

  }
  
  /**
   * Takes an array $properties of $property_key=>$response_key and an reference to an array 
   * $alexa_response, and adds the $property_key value of $this to the $alexa_response using the
   * $response_key. 
   *
   * @param array $properties
   * @param &array $alexa_response 
   */
  private function addPropertiesToAlexaResponse($properties, &$alexa_response)
  {
    foreach($properties as $property_key=>$response_key){
      $this->addPropertyToAlexaResponse($property_key, $response_key, $alexa_response);
    }
  }
  
  /**
   * Provides an array with keys equal to the property names of this object, and values equal 
   * to the corresponding names in the desired JSON response for Alexa (in "dot" notation).  
   *  
   * @return array 
   */
  private function propertyKeyToResponseKeyMap()
  {
    return [
      'alexa_response_version'  => 'version',
      'sessionAttributes'       => 'sessionAttributes',
      'outputSpeech'            => 'response.outputSpeech',
      'card'                    => 'response.card',
      'reprompt'                => 'response.reprompt',
      'shouldEndSession'        => 'shouldEndSession',
      'directives'              => 'directives'
    ];
  }
  
  /**
   * Provides a string that attempts to represent in plain english how much time has elapsed since
   * the passed in DateTime. This is an early version with specific and limited functionality. 
   *
   * @param DateTime        $date_time
   * @param DateTimeZone    $timezone
   *
   * @return string 
   */
  public static function getRelativeTimeString($date_time, $timezone)
  {
    $date_time->setTimezone($timezone);
    $now = new DateTime(null, $timezone);
    
    $time_string = $date_time->format('h:i A');
      
    $interval = $date_time->setTime(0, 0, 0)->diff($now->setTime(0, 0, 0));
    $days = $interval->d;
        
    if($days == 0){
      $date_string = 'today';
    }
    if($interval->invert){
        if($days == 1){
          $date_string = 'tomorrow';
        }
        if($days > 1){
          $date_string = 'in '.$days.' days';
        } 
    } else {
        if($days == 1){
          $date_string = 'yesterday';
        }
        if($days > 1){
          $date_string = $days.' days ago';
        }
    }        
    return $date_string.' at '.$time_string;
  }
}