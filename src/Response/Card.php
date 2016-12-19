<?php

namespace Alexa\Response;

use JsonSerializable;
use Exception;

/**
 * This object can only be included when sending a response to a LaunchRequest or IntentRequest.
 * 
 * @author Ryan Packer
 */
class Card implements JsonSerializable
{

  const TYPE_SIMPLE  				= 'Simple';
  const TYPE_STANDARD       = 'Standard';
  const TYPE_LINK_ACCOUNT   = 'LinkAccount';

  /**
   * A string containing the type card to render
   *
   * @var string
   */
  private $type = self::TYPE_SIMPLE;

  /**
   * A string containing the title of the card. (not applicable for cards of type LinkAccount)
   *
   * @var string
   */
  private $title;
  
  /**
   * A string containing the contents of a Simple card (not applicable for cards of type Standard or LinkAccount).
   *
   * @var string
   */
  private $content;
  
  /**
   * A string containing the text content for a Standard card (not applicable for cards of type Simple or LinkAccount)
   *
   * @var string
   */
  private $text;
  
  /**
   * An image array that specifies the URLs for the image to display on a Standard card. Only applicable for Standard cards.
   *
   * @var array
   */
  private $image;


  /**
   * Card constructor.
   *
   * @param string $value
   * @param string $type
   */
  public function __construct($title = null, $content = null, $text = null, $image = null, $type = self::TYPE_SIMPLE)
  {
		$this->title 			= $title;
		$this->content 		= $content;
		$this->text 			= $text;
		$this->image			= $image;
		$this->setType($type);
		// TODO: throw errors if values are passed for the wrong card type
  }
  
  /**
   * Set the small image URL 
   *
   * @param string $url 
   */
  public function setSmallImageUrl($url)
  {
  	$this->image['smallImageUrl'] = $url;
  }

  /**
   * Set the large image URL
   *
   * @param string $url 
   */
  public function setLargeImageUrl($url)
  {
  	$this->image['largeImageUrl'] = $url;
  }
  
  /**
   * @param string $type
   *
   * @throws \Exception
   *
   * @return $this
   */
  public function setType($type)
  {
    if($type !== self::TYPE_SIMPLE && $type !== self::TYPE_STANDARD && $type !== self::TYPE_LINK_ACCOUNT) {
      throw new Exception('Invalid Card type: '. $type);
    }

    $this->type = $type;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
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
    $properties = $this->objectKeyToOutputKeyMap();
    $this->addPropertiesToAssociativeArray($properties, $array);

    return $array;
  }  
  
  /**
   * Adds the value $this->$object_key to the array $array using the $output_key. 
   *
   * @param string $object_key
   * @param string $output_key   
   * @param &array $array 
   */
  private function addPropertyToAssociativeArray($object_key, $output_key, &$array)
  {
    // validate $object_key      
    if(!array_key_exists($object_key, get_object_vars($this))){
      throw new Exception('The first argument $object_key must be a property of an Alexa\Response\Response object. Passed: '.$object_key);
    }
    
    if($this->{$object_key}){
      array_set($array, $output_key, $this->{$object_key});
    }

  }
  
  /**
   * Takes an array $properties of $object_key=>$output_key and an reference to an array 
   * $array, and adds the $object_key value of $this to the $array using the
   * $output_key. 
   *
   * @param array $properties
   * @param &array $array 
   */
  private function addPropertiesToAssociativeArray($properties, &$array)
  {
    foreach($properties as $object_key=>$output_key){
      $this->addPropertyToAssociativeArray($object_key, $output_key, $array);
    }
  }
  
  /**
   * Provides an array with keys equal to the property names of this object, and values equal 
   * to the corresponding names in the desired JSON response for Alexa (in "dot" notation).  
   *  
   * @return array 
   */
  private function objectKeyToOutputKeyMap()
  {
    return [
      'type'  		=> 'type',
      'title'     => 'title',
      'content'   => 'content',
      'text'      => 'text',
      'image'     => 'image',
    ];
  }
  
  // TODO: move serialization related functions to abstract parent or helper class
  // TOOD: add getters and setters for object properties
  
}