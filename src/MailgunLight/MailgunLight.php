<?php

namespace MailgunLight;

use Exception;

class MailgunLight
{
    const MAILGUN_API_MESSAGES  = 'https://api.mailgun.net/v3/%s/messages';
    const MAILGUN_API_SUBSCRIBE = 'https://api.mailgun.net/v3/lists/%s/members';

    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var string
     */
    protected $apiDomain = '';

    /**
     * @var string
     */
    protected $from = '';

    /**
     * @var string
     */
    protected $replyTo = '';

    /**
     * @var array
     */
    protected $recipients = array();

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var string
     */
    protected $html = '';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var string
     */
    protected $error = '';

    /**
     * MailGun constructor.
     * @param string $apiKey
     * @param string $apiDomain
     */
    public function __construct(string $apiKey, string $apiDomain)
    {
        $this->apiKey       = $apiKey;
        $this->apiDomain    = $apiDomain;
    }

    /**
     * Set who the message is from
     *
     * @param string $email
     * @param string $name
     * @return self
     * @throws Exception
     */
    public function setFrom(string $email, string $name):self
    {
        $this->from = $name . ' <' . $email . '>';
        return $this;
    }

    /**
     * Set who to reply to
     *
     * @param string $email
     * @param string $name
     * @return self
     * @throws Exception
     */
    public function setReplyTo(string $email, string $name):self
    {
        $this->replyTo = $name . ' <' . $email . '>';
        return $this;
    }

    /**
     * Add recipient. This method support multiple recipients
     *
     * @param string $email
     * @param string $name
     * @return self
     */
    public function addRecipient(string $email, string $name):self
    {
        $name = (empty($name)) ? $email : $name;
        $this->recipients[] = $name . ' <' . $email . '>';
        return $this;
    }

    /**
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject):self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param string $text
     * @return self
     */
    public function setText(string $text):self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param string $html
     * @return self
     */
    public function setHTML(string $html):self
    {
        $this->html = $html;
        return $this;
    }

    public function getError():string
    {
        return $this->error;
    }

    /**
     * Register email to a mailing list
     *
     * @param string $list
     * @param string $address
     * @param string $name
     * @param string $description
     * @param array $vars
     * @return string
     */
    public function subscribe(string $list, string $address, string $name = '', string $description = '', array $vars = []):string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, sprintf(self::MAILGUN_API_SUBSCRIBE, $list));
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            array(
                'subscribed' => 'True',
                'address' => $address,
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $result = curl_exec($ch);

        if($result === false) {
            $this->error = curl_error($ch);

            $return = false;
        }
        else {
            switch ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:
                    $return = true;
                    break;
                default:
                    $this->error = 'Unexpected HTTP code: ' . $status . '(' . $result . ')';
                    $return = false;
                    break;
            }
        }

        return $return;
    }

    /**
     * Send your message
     * If sent successfully return true else returns false
     *
     * @return bool
     */
    public function send()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, sprintf(self::MAILGUN_API_MESSAGES, $this->apiDomain));

        if(!empty($this->replyTo)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'h:reply-to: ' . $this->replyTo,
            ));
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS,
            array(
                'from' => $this->from,
                'to' => implode(',', $this->recipients),
                'subject' => $this->subject,
                'text' => $this->text,
                'html' => $this->html
            )
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $result = curl_exec($ch);

        if($result === false) {
            $this->error = curl_error($ch);

            $return = false;
        }
        else {
            switch ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:
                    $return = true;
                    break;
                default:
                    $this->error = 'Unexpected HTTP code: ' . $status . '(' . $result . ')';
                    $return = false;
                    break;
            }
        }

        return $return;
    }
}