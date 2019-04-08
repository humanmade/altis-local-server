<?php

namespace HM\Platform\Local_Server;

use PHPMailer;

class Log_PHPMailer extends PHPMailer {
	function preSend() {
		$this->Encoding = '8bit';
		return parent::preSend();
	}

	/**
	 * Override postSend() so mail isn't actually sent.
	 */
	function postSend() {
		$message = [
			'to'      => $this->to,
			'cc'      => $this->cc,
			'bcc'     => $this->bcc,
			'header'  => $this->MIMEHeader . $this->mailHeader,
			'subject' => $this->Subject,
			'body'    => $this->MIMEBody,
		];
		error_log( 'PHPMailer Message Sent:' );
		error_log( var_export( $message, true ) );
		return true;
	}

}
