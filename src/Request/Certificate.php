<?php

namespace Alexa\Request;

class Certificate 
{
  
  const SIGNATURE_VALID_PROTOCOL  = 'https';
	const SIGNATURE_VALID_HOSTNAME  = 's3.amazonaws.com';
	const SIGNATURE_VALID_PATH      = '/echo.api/';
	const SIGNATURE_VALID_PORT      = 443;

  const ECHO_SERVICE_DOMAIN = 'echo-api.amazon.com';
	const ENCRYPT_METHOD = "sha1WithRSAEncryption";
  
  /**
   * Parsed certificate 
   *
   * @var string
   */
  protected $parsed_certificate;

  /**
   * The public key for the certificate
   *
   * @var string
   */  
  protected $certificate_key;
  
  /**
   * Retrieve the certificate from a url, parse and store the parsed certificate
   *
   * @param string $certificateChainUri
   *
   * @return Alexa\Request\Certificate
   */
  public static function initFromRemoteCertificate($certificateChainUri)
  {
      $certificate = new Certificate();

      if(self::isValidCertificateChainUri($certificateChainUri)){
        $certificate_contents = self::getRemoteCertificate($certificateChainUri);
        $certificate->certificate_key = openssl_pkey_get_public($certificate_contents);
        $certificate->parsed_certificate = openssl_x509_parse($certificate_contents);
        return $certificate;
      } else {
        return null;
      }
  }
  
 /**
  * Retrieve the certificate from a url
  *
  * @param string $certificateChainUri
  *
  * @return string certificate contents
  */
  public static function getRemoteCertificate($certificateChainUri)
  {
   
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $certificateChainUri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$certificate = curl_exec($ch);
		curl_close($ch);
		
		return $certificate;
  }
  
  /**
	 * Validate the provided URL for the certificate
	 *
	 * @param string $certificateChainUri
	 *
	 * @return bool
	 */
	public static function isValidCertificateChainUri($certificateChainUri) 
	{

		$url = parse_url($certificateChainUri);

		// TODO: throw proper exceptions
		return (
		  ($url['scheme'] === static::SIGNATURE_VALID_PROTOCOL)
		  && ($url['host'] === static::SIGNATURE_VALID_HOSTNAME)
		  && (strpos($url['path'], static::SIGNATURE_VALID_PATH) === 0)
		);
	}
	
	/**
	 * Validate the certificate
	 *
	 * @return bool
	 */
	public function isValid() 
  {
    return (
      $this->hasValidDate()
      && $this->hasProperSubjectAltName()
    );
	}
  
  /**
   * Returns whether the configured service domain is present and valid
   *
   * @return bool
   */
  protected function hasProperSubjectAltName()
  {
      $subjectAltName = $this->parsed_certificate['extensions']['subjectAltName'];
      return strpos($subjectAltName, self::ECHO_SERVICE_DOMAIN) !== false;
  }
  
  /**
   * Returns whether the date is valid
   *
   * @return bool
   */
  protected function hasValidDate()
  {
      $valid_from = $this->parsed_certificate['validFrom_time_t'];
      $valid_to   = $this->parsed_certificate['validTo_time_t'];
      $time       = time();

      return ($valid_from <= $time && $time <= $valid_to);
  }
  
  /**
   * Verify the signature header matches the expected value for the signed content
   *
   * @params $signature, $raw_request_body 
   *
   * @throws Exception
   *
   * @return bool
   */
  public function verifyRequestSignature($signature, $raw_request_body) {

    $valid = openssl_verify(
      $raw_request_body, 
      base64_decode($signature),
      $this->certificate_key,
      self::ENCRYPT_METHOD
    );
    
    if (!$valid) {
      throw new Exception('Request signature could not be verified');
    } else {
      return $valid;
    }
  }

}
