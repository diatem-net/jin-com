<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\Com;

/**
 * Classe permettant de générer des appels CURL
 */
class Curl
{

  /**
   * @var string  Dernière erreur rencontrée
   */
  protected static $lastErrorText = '';

  /**
   * @var int  Dernier code d'erreur rencontré
   */
  protected static $lastErrorCode = 0;
  protected static $lastCurlInfo;

  const CURL_REQUEST_TYPE_GET = 'GET';
  const CURL_REQUEST_TYPE_POST = 'POST';
  const CURL_REQUEST_TYPE_PUT = 'PUT';
  const CURL_REQUEST_TYPE_DELETE = 'DELETE';
  const CURL_REQUEST_TYPE_PATCH = 'PATCH';

  /**
   * Appelle une Url
	 *
   * @param  string        $url                Url à appeler
   * @param  array|string  $args               (optional) Arguments à transmettre (NULL par défaut)
   * @param  string        $requestType        (optional) Type de requête. (POST, GET, DELETE, PATCH ou PUT) (POST par défaut)
   * @param  boolean       $throwError         (optional) Génère les erreurs directement (True par défaut)
   * @param  string        $contentType        (optional) Header 'Content-type'. Par défaut : multipart/form-data
   * @param  array         $headers            (optional) Headers additionnels. (Sous la forme d'un tableau clé/valeur)
   * @param  string        $outputTraceFile    (optional) Si renseigné : effectue une trace des flux réseaux dans le fichier ainsi déterminé. (Chemin absolu du fichier)
   * @param  boolean       $followLocation     (optional) Autorise CURL a suivre les redirections (False par défaut)
   * @return boolean
   * @throws \Exception
   */
  public static function call($url, $args = null, $requestType = self::CURL_REQUEST_TYPE_POST, $throwError = true, $httpAuthUser = null, $httpAuthPassword = null, $contentType = null, $headers = array(), $outputTraceFile = null, $followLocation = false)
  {

    if(!$contentType){
      $contentType = 'multipart/form-data';
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if ($followLocation) {
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    }

    // Set headers
    $h = array();
    if ($contentType) {
      $h[] = 'Content-type: ' . $contentType;
    }
    if (!empty($headers)) {
      foreach ($headers AS $k => $v) {
        $h[] = $k . ': ' . $v;
      }
    }
    if (!empty($h)) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
    }

    // Mode debug
    if (!empty($outputTraceFile)) {
      curl_setopt($curl, CURLOPT_VERBOSE, true);
      $verbose = fopen($outputTraceFile, 'w');
      curl_setopt($curl, CURLOPT_STDERR, $verbose);
    }

    // Authentification HTTP
    if (!is_null($httpAuthUser) && !is_null($httpAuthPassword)) {
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_USERPWD, $httpAuthUser . ':' . $httpAuthPassword);
    }
    
    // Delete
    if ($requestType == self::CURL_REQUEST_TYPE_DELETE) {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, self::CURL_REQUEST_TYPE_DELETE);
      if (strpos($url, '?') === false && !empty($args)) {
        $url .= '?' . http_build_query($args);
      } else if (!empty($args)) {
        $url .= '&' . http_build_query($args);
      }
      curl_setopt($curl, CURLOPT_URL, $url);
    }

    // Get
    if ($requestType == self::CURL_REQUEST_TYPE_GET) {
      if (strpos($url, '?') === false && !empty($args)) {
        $url .= '?' . http_build_query($args);
      } else if (!empty($args)) {
        $url .= '&' . http_build_query($args);
      }
      curl_setopt($curl, CURLOPT_URL, $url);
    }
    
    // Patch
    if ($requestType == self::CURL_REQUEST_TYPE_PATCH) {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, self::CURL_REQUEST_TYPE_PATCH);
      if (!empty($args)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($args));
      }
    }
    
    // Post
    if ($requestType == self::CURL_REQUEST_TYPE_POST) {

      foreach($args AS $k => $v){
        if(is_array($v)){
          $args[$k] = json_encode($v);
        }
      }
      
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $args);

    }
    
    // Put
    if ($requestType == self::CURL_REQUEST_TYPE_PUT) {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, self::CURL_REQUEST_TYPE_PUT);
      if (!empty($args)) {
         curl_setopt($curl, CURLOPT_POST, true);
         curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($args));
      }
    }
    
    $return = curl_exec($curl);

    self::$lastCurlInfo = curl_getinfo($curl);

    if (!$return) {
      $errornum = curl_errno($curl);
      $errortxt = 'Impossible d\'appeler l\'url : ' . $url . ' : (ERREUR CURL ' . $errornum . ') ' . curl_error($curl);
      if ($throwError) {
        throw new \Exception($errortxt);
      }
      self::$lastErrorText = $errortxt;
      self::$lastErrorCode = $errornum;
      return false;
    }

    curl_close($curl);
    return $return;
  }

  /**
   * Retourne le dernier code d'erreur rencontré
   *
   * @return int
   */
  public static function getLastErrorCode()
  {
    return self::$lastErrorCode;
  }

  /**
   * Retourne la dernière erreur rencontrée (verbose)
   *
   * @return string
   */
  public static function getLastErrorVerbose()
  {
    return self::$lastErrorText;
  }

  /**
   * Retourne le code HTTP de retour du dernier appel
   *
   * @return string
   */
  public static function getLastHttpCode()
  {
    if (isset(self::$lastCurlInfo['http_code'])) {
      return self::$lastCurlInfo['http_code'];
    } else {
      return false;
    }
  }

  /**
   * Retourne le détail textuel du dernier code HTTP retourné
   *
   * @return string
   */
  public static function getLastHttpCodeVerbose()
  {
    if (isset(self::$lastCurlInfo['http_code'])) {
      return self::getHttpCodeVerbose(self::$lastCurlInfo['http_code']);
    } else {
      return false;
    }
  }

  /**
   * Retourne le détail textuel d'un code HTTP
   *
   * @param  integer $httpCode Code HTTP
   * @return string
   */
  public static function getHttpCodeVerbose($httpCode)
  {
    $status = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      306 => '(Unused)',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported');
    return ($status[$httpCode])
      ? $status[$httpCode]
      : $status[500];
  }

}
