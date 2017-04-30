<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\Com\Rest;

use Jin2\Com\Curl;

class RestCall
{

  private $secured = false;
  private $publicKey;
  private $privateKey;
  private $url;
  private $args = array();
  private $method;
  private $throwError = true;

  public function __construct($url, $args = NULL, $method = Curl::CURL_REQUEST_TYPE_POST)
  {
    $this->url = $url;
    foreach($args as $key => $value){
      $this->args[$key] = json_encode($value);
    }
    $this->method = $method;
  }

  public function setSecured($publicKey, $privateKey)
  {
    $this->secured = true;
    $this->publicKey = $publicKey;
    $this->privateKey = $privateKey;
  }

  public function setErrorThrowed($etat)
  {
    $this->throwError = true;
  }

  public function call()
  {
    $plus = '';
    if ($this->secured) {
      if (strpos($this->url, '?') !== false) {
        $plus = '&secure='.$this->getHMAC().'&publickey='.$this->publicKey;
      } else {
        $plus = '?secure='.$this->getHMAC().'&publickey='.$this->publicKey;
      }
    }
    $results = Curl::call($this->url.$plus, $this->args, $this->method, $this->throwError);
    return $results;
  }

  public function getLastErrorCode()
  {
    return Curl::getLastErrorCode();
  }

  public function getLastErrorVerbose()
  {
    return Curl::getLastErrorVerbose();
  }

  public function getLastHttpCode()
  {
    return Curl::getLastHttpCode();
  }

  public function getLastHttpCodeVerbose()
  {
    return Curl::getLastHttpCodeVerbose();
  }

  private function getHMAC()
  {
    $toEncode = $this->url;
    $toEncode .= $this->method;
    $toEncode .= $this->publicKey;
    if ($this->args) {
      $toEncode .= json_encode(static::stringifyArrayValues($this->args), JSON_HEX_QUOT | JSON_HEX_TAG);
    } else {
      $toEncode .= json_encode(array());
    }
    return hash_hmac('sha256', $toEncode, $this->privateKey);
  }

  private static function stringifyArrayValues($array)
  {
    if (is_array($array)) {
      foreach($array AS $key => $value) {
        $array[$key] = static::stringifyArrayValues($value);
      }
    } else {
      return (string) $array;
    }
    return $array;
  }

}
