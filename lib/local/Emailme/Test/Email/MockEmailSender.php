<?php

namespace Emailme\Test\Email;

use EmailMe\Debug\Debug;
use Emailme\Email\EmailSender;
use Exception;

/*
* MockEmailSender
*/
class MockEmailSender extends EmailSender
{

    static $SENT_MESSAGES = [];

    ////////////////////////////////////////////////////////////////////////

    public function getSentMessagesCollection() {
        return self::$SENT_MESSAGES;
    }
    public function clearSentMessagesCollection() {
        $out = $this->getSentMessagesCollection();
        self::$SENT_MESSAGES = [];
        return $out;
    }

    ////////////////////////////////////////////////////////////////////////

    protected function sendEmailWithMandrillParameters($mandrill_message) {
        /* keep */ Debug::trace("\$mandrill_message=\n".str_replace('\\n',"\n",json_encode($mandrill_message, 192)),__FILE__,__LINE__,$this);
        // just save the message to memory
        self::$SENT_MESSAGES[] = $mandrill_message;
        return [];
    }

}

