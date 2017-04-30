<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\Com\WebService;

use PHP2WSDL\PHPClass2WSDL;

/**
 * Outil de création de WebService à partir d'une classe
 */
class WsServer
{

  /**
   * Chemin du fichier de classe php
   *
   * @var string
   */
  private $classFile = null;

  /**
   * Url du fichier service php
   *
   * @var string
   */
  private $endPoint = null;

  /**
   * Nom de la classe utilisée comme WebService
   *
   * @var string
   */
  private $className = null;

  /**
   * Constructeur
   *
   * @param  string  $classFile  Fichier PHP à utiliser comme WebService
   * @param  string  $className  Nom de la classe
   * @param  string  $endPoint   Uurl d'accès du WebService
   * @return void
   */
  function __construct($classFile, $className, $endPoint)
  {
    $this->classFile = $classFile;
    $this->className = $className;
    $this->endPoint  = $endPoint;
  }

  /**
   * Construction du service
   *
   * @return void
   */
  public function buildService()
  {
    require_once($this->classFile);
    $wsdl = $this->generateWsdl();

    if (isset($_GET['wsdl'])) {
      print $wsdl;
    } else {
      $file = 'data://text/plain;base64,'. base64_encode($wsdl);
      $server = new \SoapServer($file);
      $server->setClass($this->className);
      $functions = get_class_methods($this->className);
      foreach ($functions as $function) {
        $server->addFunction($function);
      }
      $server->handle();
    }
  }

  /**
   * Génération du WSDL à la volée
   *
   * @return mixed  Définition WSDL
   */
  private function generateWsdl()
  {
    error_reporting(0);
    set_error_handler(null);

    $wsdlGenerator = new PHPClass2WSDL($this->className, $this->endPoint);
    $wsdlGenerator->generateWSDL(false);

    return $wsdlGenerator->dump();
  }

}
