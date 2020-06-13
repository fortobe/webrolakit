<?
/*
EXAMPLE USE
$o_mail = new \wrk\classes\mailer("test@test.test", "tester");
$o_mail->SendMail("test@test.test","TEST MSG","TEST!");
*/

namespace wrk\classes;

include("../dependencies/phpmailer/class.phpmailer.php");

class mailer {
	
	private $oPhpMailer;
	
	function __construct($sFrom, $sFromName) {
		$this->oPhpMailer = new \PHPMailer();
		$this->oPhpMailer->IsHTML(true);
		$this->oPhpMailer->From = $sFrom;
		$this->oPhpMailer->FromName = $sFromName;
	}
	
	function AddAttachment($sPath, $sName) {
		$this->oPhpMailer->AddAttachment($sPath, $sName);
	}

	function SendMail($sTo, $Subject, $sBody, $aFiles = array()) {
		$this->oPhpMailer->ClearAddresses();
		$this->oPhpMailer->AddAddress($sTo);
		$this->oPhpMailer->Subject  = $Subject;
		$this->oPhpMailer->Body     = $sBody;
		if(count($aFiles) > 0 && !empty($aFiles)) {
			$b_att = true;
			foreach ($aFiles as $fileItem) {
				$b_att = $b_att && $this->oPhpMailer->AddAttachment($fileItem);
			}
			//if(!$b_att) return $this->oPhpMailer->ErrorInfo;
			if(!$b_att) return false;
		}
		if ($this->oPhpMailer->Send())
			return true;
		else
			return false;		
	}
}



?>
