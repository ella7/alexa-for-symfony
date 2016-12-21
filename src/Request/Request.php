<?php

namespace Alexa\Request;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest; 
use Symfony\Component\HttpFoundation\ParameterBag;
use Alexa\Request\Certificate;
use DateTime;
use Exception;

/**
 * Request represents an HTTP request from Amazon Alexa.
 * 
 * @author Ryan Packer
 */
class Request extends SymfonyRequest
{
  
  const TIMESTAMP_VALID_TOLERANCE_SECONDS = 30;
  
  /**
   * Parameter bag to hold the values in the request body.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $data;

  /**
   * Parameter bag to hold the session values.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $alexa_session;
  
  /**
   * Parameter bag to hold the values in the request body request.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $alexa_request;
  
  /**
   * Alexa request intent.
   *
   * @var String
   */
  protected $intent;
  
  /**
   * Array of slots for the intent
   *
   * @var array
   */
  protected $slots;
  
      
  
  /**
   * Creates a new request with values from the passed in SymfonyRequest.
   *
   * @return Request A new request
   */
  public static function createFromSymfonyRequest(SymfonyRequest $symfony_request)
  {
    $new_request = new Request();
    $new_request->query       = clone $symfony_request->query;
    $new_request->request     = clone $symfony_request->request;
    $new_request->attributes  = clone $symfony_request->attributes;
    $new_request->cookies     = clone $symfony_request->cookies;
    $new_request->files       = clone $symfony_request->files;
    $new_request->server      = clone $symfony_request->server;
    $new_request->headers     = clone $symfony_request->headers;
    $new_request->session     = clone $symfony_request->session;
    
    $new_request->setAlexaSpecificPropertiesFromRawRequest();
    
    return $new_request;
  }
  
  /**
   * Sets the Alexa specific properties of this object from the request data.
   *
   * @return null
   */
  private function setAlexaSpecificPropertiesFromRawRequest()
  {
    $decoded_request_body = json_decode($this->getContent(), true);
    if(is_array ( $decoded_request_body )){
      $this->data = new ParameterBag($decoded_request_body);
      
      if($this->data->has('session') && is_array($this->data->get('session'))){
        $this->alexa_session = new ParameterBag($this->data->get('session')); 
      }
    
      if($this->data->has('request') && is_array($this->data->get('request'))){
        $this->alexa_request = new ParameterBag($this->data->get('request'));

        if($this->alexa_request->has('intent')){
          $intent = $this->alexa_request->get('intent');
          if(array_key_exists('name', $intent)) $this->intent = $intent['name'];
          if(array_key_exists('slots', $intent)) $this->slots = $intent['slots'];
        }
      }
    } else {
      // TODO: Throw a proper exception 
    }
  }
  
  /**
   * Get slots
   *
   * @return array
   */
  public function getSlots()
  {
    return $this->slots;
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
   * Returns the alexa request type, i.e. IntentRequest
   *
   * @return string
   */
  public function getRequestType()
  {
    return $this->alexa_request->get('type');
  }
  
  /**
   * Returns the alexa request requestId
   *
   * @return string
   */
  public function getRequestId()
  {
    return $this->alexa_request->get('requestId');
  }
  
  /**
   * Returns the alexa request timestamp
   *
   * @return string
   */
  public function getTimestamp()
  {
    return $this->alexa_request->get('timestamp');
  }

  /**
   * Returns the alexa request intent
   *
   * @return string
   */
  public function getIntent()
  {
    return $this->intent;
  }
  
  /**
   * Validates the request - checks timestamp, applicationId, and signature
   *
   * @return bool
   */
  public function isValid($expected_application_id)
  {
    return (
      ($this->hasValidTimestamp())
      && ($this->hasValidApplicationId($expected_application_id)) 
      && ($this->hasValidSignature())
    );
  }
  
  /**
	 * Check if request is whithin the allowed time.
	 * 
	 * @return bool
	 */
	public function hasValidTimestamp() 
	{
		$now = new DateTime;
		$request_time = new DateTime($this->alexa_request->get('timestamp'));
		$differenceInSeconds = $now->getTimestamp() - $request_time->getTimestamp();

		if ($differenceInSeconds > self::TIMESTAMP_VALID_TOLERANCE_SECONDS) {
			throw new Exception('Request timestamp was too old. Possible replay attack.');
		} else {
		  return true;
		}
	}
  
  /**
   * Validates the applicationId
   *
   * @return bool
   */
  public function hasValidApplicationId($expected_application_id)
  {
    // TODO: either wrap in try catch or check array_keys_exist first
    $application = $this->alexa_session->get('application');
    if($application['applicationId'] !== $expected_application_id){
      throw new Exception('ApplicationId: '.$application['applicationId'].' does not match expected ApplicationId: '.$expected_application_id);
    } else {
      return true;
    }
  }
  
  /**
   * Validates the signature and signing certificate
   *
   * @return bool
   */
  public function hasValidSignature()
  {
    $certificateChainUri  = $this->headers->get('Signaturecertchainurl');
    $signature            = $this->headers->get('Signature');
    $certificate          = Certificate::initFromRemoteCertificate($certificateChainUri);
    $raw_request_body     = $this->getContent();
    
    if(!$certificate->isValid()){
      throw new Exception('The provided certificate is not valid');
    } else {
      return $certificate->verifyRequestSignature($signature, $raw_request_body);
    }
  }


}
