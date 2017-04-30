<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\Com\Rest;

use Jin2\Com\Curl;

/**
 * @source: Arun Kumar Sekar (Rest.inc.php)
 */
class RestService
{

  public $_allow = array();
  public $_content_type = "application/json";
  public $_request = array();
  public $_requestJSon = array();

  private $_method = "";
  private $_code = 200;
  private $securedRest = false;
  private $securedKeys = array();
  private $requestPublicKey;
  private $requestSecure;
  private $requestService;

  public function __construct()
  {
    $this->inputs();
  }

  public function setSecured($keys)
  {
    $this->securedRest = true;
    $this->securedKeys = $keys;
  }

  public function get_referer()
  {
    return $_SERVER['HTTP_REFERER'];
  }

  public function response($data, $status)
  {
    $this->_code = ($status) ? $status : 200;
    $this->set_headers();
    echo $data;
    exit;
  }

  private function get_status_message()
  {
    return Curl::getHttpCodeVerbose($this->_code);
  }

  public function get_request_method()
  {
    return $_SERVER['REQUEST_METHOD'];
  }

  public function processApi()
  {
    if ($this->securedRest) {
      if (!isset($this->requestSecure) || !isset($this->requestPublicKey)) {
        $this->response('Utilisateur non authentifié', 401);
      }
      if (!$this->verifyKeys($this->requestPublicKey, $this->requestSecure)) {
        $this->response('Utilisateur non authentifié', 401);
      }
    }
    $func = strtolower(trim(str_replace("/", "", $_REQUEST['request'])));
    if ((int) method_exists($this, $func) > 0) {
      $this->$func();
    } else {
      $this->response('', 404);
    }
  }

  private function verifyKeys($publicKey, $secureString)
  {
    if(!isset($this->securedKeys[$publicKey])){
      return false;
    }
    $localHmac = hash_hmac('sha256', $this->getToEncodeString($publicKey), $this->securedKeys[$publicKey]);
    return $this->compareHMAC($localHmac, $secureString);
  }

  private function getToEncodeString($publicKey)
  {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
      $protocol .= 's';
    }
    $toEncode = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL'];
    $toEncode .= $this->get_request_method();
    $toEncode .= $publicKey;
    $toEncode .= json_encode(static::stringifyArrayValues($this->_requestJSon), JSON_HEX_QUOT | JSON_HEX_TAG);
    return $toEncode;
  }

  private function compareHMAC($a, $b)
  {
    if (!is_string($a) || !is_string($b)) {
      return false;
    }
    $len = strlen($a);
    if ($len !== strlen($b)) {
      return false;
    }
    $status = 0;
    for ($i = 0; $i < $len; $i++) {
      $status |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $status === 0;
  }

  private function inputs()
  {
    if (isset($_GET['publickey']) && isset($_GET['secure'])) {
      $this->requestPublicKey = $_GET['publickey'];
      $this->requestSecure = $_GET['secure'];
      unset($_GET['publickey']);
      unset($_GET['secure']);
    }
    if (isset($_GET['request'])) {
      $this->requestService = $_GET['request'];
      unset($_GET['request']);
    }
    switch ($this->get_request_method()) {
      case Curl::CURL_REQUEST_TYPE_POST:
        $this->_request = $this->cleanInputs($_POST);
        $this->_requestJSon = $_POST;
        break;
      case Curl::CURL_REQUEST_TYPE_GET:
        $this->_request = $this->cleanInputs($_GET);
        $this->_requestJSon = $_GET;
        break;
      case Curl::CURL_REQUEST_TYPE_DELETE:
        $this->_request = $this->cleanInputs($_GET);
        $this->_requestJSon = $_GET;
        break;
      case Curl::CURL_REQUEST_TYPE_PUT:
        parse_str(file_get_contents("php://input"), $this->_request);
        $this->_request = $this->cleanInputs($_GET);
        $this->_requestJSon = $_GET;
        break;
      default:
        $this->response('', 406);
    }
  }

  private function cleanInputs($data)
  {
    $cleaned = array();
    foreach($data as $key => $value){
      $cleaned[$key] = json_decode($value, true);
    }
    return $cleaned;
  }

  private function set_headers()
  {
    header("HTTP/1.1 " . $this->_code . " " . $this->get_status_message());
    header("Content-Type:" . $this->_content_type);
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