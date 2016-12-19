<?php

namespace Alexa\Response;

use Exception;

/**
 * Used for setting both the outputSpeech and the reprompt properties within Amazon Alexa responses
 * 
 * @author Ryan Packer
 */
class OutputSpeech
{

  const TYPE_PLAINTEXT  = 'PlainText';
  const TYPE_SSML       = 'SSML';

  /**
   * A string containing the type of output speech to render
   *
   * @var string
   */
  private $type = self::TYPE_PLAINTEXT;

  /**
   * A string containing the content (text or ssml) to render to the user.
   *
   * @var string
   */
  private $value;

  /**
   * OutputSpeech constructor.
   *
   * @param string $value
   * @param string $type
   */
  public function __construct($value = '', $type = self::TYPE_PLAINTEXT)
  {
    $this->setValue($value);
    $this->setType($type);
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
    if($type !== self::TYPE_PLAINTEXT && $type !== self::TYPE_SSML) {
      throw new Exception('Invalid OutputSpeech type');
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
   * @param string
   *
   * @return $this
   */
  public function setValue($value)
  {
    $this->value = $value;
    return $this;
  }

  /**
   * @return string
   */
  public function getValue()
  {
    return $this->value;
  }
  
  /**
   * Return the parameter key used to store the $value based on the $type
   * 
   * @return string
   */
  private function valueKeyForType()
  {
    $keys = [
      self::TYPE_PLAINTEXT  => 'text',
      self::TYPE_SSML       => 'ssml'
    ];
    return $keys[$this->type];
  }
  
}