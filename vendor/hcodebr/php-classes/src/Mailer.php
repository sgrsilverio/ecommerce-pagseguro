<?php
/**
 * Created by PhpStorm.
 * User: shalon
 * Date: 12/08/2018
 * Time: 15:57
 */

namespace Hcode;
use Rain\Tpl;



class Mailer
{
    const USERNAME = "";
    const PASSWORD = "";
    const NAME_FROM = "Hcode Store";
    private $mail;

  public function __construct($toAddress,$toName,$subject,$tplName,$data=array())
  {
      $config = array(
          "tpl_dir" => $_SERVER["DOCUMENT_ROOT"] . "/views/email/",
          "cache_dir" => $_SERVER["DOCUMENT_ROOT"] . "views-cache/",
          "debug" => false // set to false to improve the speed
      );

      Tpl::configure($config);
      $tpl = new Tpl;
      foreach ($data as $key => $value) {
          $tpl->assign($key,$value);
      }
      $html = $tpl->draw($tplName, true);

      $this->mail = new \PHPMailer;
      $this->mail->isSMTP();
      $this->mail->SMTPDebug = 0;
      $this->mail->Host = 'smtp.gmail.com';
      $this->mail->Port = 587;
      $this->mail->SMTPSecure = 'tls';
      $this->mail->SMTPAuth = true;
      $this->mail->Username = Mailer::USERNAME;
      $this->mail->Password = Mailer::PASSWORD;
      $this->mail->setFrom(Mailer::USERNAME,Mailer::NAME_FROM);
      //$this->mail->addReplyTo('replyto@example.com', 'First Last');
      $this->mail->addAddress($toAddress,$toName);
      $this->mail->Subject = $subject;
      $this->mail->msgHTML($html);
      $this->mail->AltBody = 'This is a plain-text message body';
      //$this->mail->addAttachment('images/phpmailer_mini.png');
  }
        Public function send(){
            return $this->mail->send();
        }

}