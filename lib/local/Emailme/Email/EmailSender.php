<?php

namespace Emailme\Email;

use Emailme\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;

/*
* EmailSender
*/
class EmailSender
{

    public function __construct($mandrill, $job_adder, $email_twig, $email_defaults) {
        $this->mandrill = $mandrill;
        $this->job_adder = $job_adder;
        $this->email_twig = $email_twig;
        $this->email_defaults = $email_defaults;
    }

    ////////////////////////////////////////////////////////////////////////

    public function composeEmailParametersFromTemplate($twig_path, $twig_vars) {
        // render the template
        if (substr($twig_path, -9) !== '.txt.twig') { $twig_path .= '.txt.twig'; }
        $twig_source = $this->email_twig->render($twig_path, $twig_vars);

        // extract message options
        list($message_options, $text) = $this->extractMessageDataFromText($twig_source);
        $message_options['text'] = $text;

        $message_parameters = array_replace_recursive($this->email_defaults, $message_options);
        return $message_parameters;
    }

    public function sendEmailInBackgroundWithParameters($params) {
        return $this->job_adder->addJob('job.email', $this->requireValidParameters($params));
    }

    public function sendEmailInBackground($to_name, $to_email, $subject, $text, $reply_to=null, $other_data=[]) {
        return $this->sendEmailInBackgroundWithParameters($this->buildParameters($to_name, $to_email, $subject, $text, $reply_to, $other_data));
    }

    public function sendEmail($to_name, $to_email, $subject, $text, $reply_to=null, $other_data=[]) {
        return $this->sendEmailWithParams($this->buildParameters($to_name, $to_email, $subject, $text, $reply_to, $other_data));
    }

    public function buildParameters($to_name, $to_email, $subject, $text, $reply_to=null, $other_data=[]) {
        return [
            'to_name'    => $to_name,
            'to_email'   => $to_email,
            'subject'    => $subject,
            'text'       => $text,
            'reply_to'   => $reply_to,
            'other_data' => $other_data,
        ];
    }


    public function sendEmailWithParams($params) {
        $mandrill_message = $params;

        // reply_to and other_data
        if (isset($params['reply_to'])) { $reply_to = $params['reply_to']; } else { $reply_to = null; }
        if (isset($params['other_data'])) {$other_data = $params['other_data']; } else { $other_data = []; }

        // build to
        $to_name = isset($params['to_name']) ? $params['to_name'] : null;
        $to_email = $params['to_email'];
        $to = ['email' => $to_email, 'type' => 'to'];
        if (strlen($to_name)) { $to['name'] = $to_name; }
        $mandrill_message['to'] = [$to];

        // other parameters
        if ($other_data) { $mandrill_message = array_replace_recursive($mandrill_message, $other_data); }

        // headers
        $mandrill_message['headers'] = (isset($mandrill_message['headers']) ? $mandrill_message['headers'] : []);
        if ($reply_to) {
            $mandrill_message['headers']['Reply-To'] = $reply_to;
        }

        unset($mandrill_message['to_name']);
        unset($mandrill_message['to_email']);
        unset($mandrill_message['reply_to']);
        unset($mandrill_message['other_data']);

        // Debug::trace("sending:",$mandrill_message,__FILE__,__LINE__,$this);
        return $this->sendEmailWithMandrillParameters($mandrill_message);
    }

    public function requireValidParameters($params) {
        // if (!isset($params['to_name'])) { throw new Exception("no to_name param found", 1); }
        if (!isset($params['to_email'])) { throw new Exception("no to_email param found", 1); }
        if (!isset($params['subject'])) { throw new Exception("no subject param found", 1); }
        if (!isset($params['text'])) { throw new Exception("no text param found", 1); }
        return $params;
    }

    ////////////////////////////////////////////////////////////////////////

    protected function sendEmailWithMandrillParameters($mandrill_message) {
        try {
            $response = $this->mandrill->call('messages/send', array('message' => $mandrill_message));
        } catch (Exception $e) {
            EventLog::logError('email.send.error', ['message' => $mandrill_message, 'error' => $e]);
            throw $e;
            
        }
#        Debug::trace("\$response=\n".json_encode($response, 192),__FILE__,__LINE__,$this);
        return $response;
    }

    public function extractMessageDataFromText($text) {
        $opts = [];

        if (($sep_pos = strpos($text, "--------")) !== false) {
            $opts_src = substr($text, 0, $sep_pos);
            $opts = parse_ini_string($opts_src);
            if (($newline_pos = strpos($text, "\n", $sep_pos)) !== false) {
                $text = substr($text, $newline_pos + 1);
            }
        }
        return [$opts, $text];
    }


}

