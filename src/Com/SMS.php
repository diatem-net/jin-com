<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\Com\SMS;

use Jin2\Com\WebService\WSClient;

/**
 * Envoi de SMS via un webservice Diatem
 */
class SMS
{

  /**
   * Méthode permettant l'envoi du SMS et sa sauvegarde dans la base de donnée
   *
   * @param  string  $numero       Numéro de téléphone
   * @param  string  $message      Contenu du SMS
   * @param  string  $codeSociete  Société émettrice
   * @param  string  $serveurSMS   Serveur d'envoi de SMS
   * @throws \Exception
   * @return boolean               TRUE si le SMS a bien été envoyé
   */
  public static function send($numero, $message, $codeSociete, $serveurSMS)
  {
    //On appelle le webservice qui s'occupe d'envoyer le SMS
    $client = new WSClient($serveurSMS);
    $client->setWSDLCacheEnabled(false);
    $client->service('sms', array('numero' => $numero, 'message' => $message, 'code_societe' => $codeSociete));
    return true;
  }

}
