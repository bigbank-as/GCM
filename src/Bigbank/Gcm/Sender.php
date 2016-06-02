<?php
namespace Bigbank\Gcm;

use GuzzleHttp\Client;

/**
 * Messages sender to GCM servers
 */
class Sender
{

    /**
     * GCM endpoint
     *
     * @var string
     */
    private $gcmUrl = 'https://android.googleapis.com/gcm/send';

    /**
     * Path to CA file (due to cURL 7.10 changes; you can get it from here: http://curl.haxx.se/docs/caextract.html)
     *
     * @var string
     */
    private $caInfoPath = false;

    /**
     * An API key that gives the application server authorized access to Google services.
     *
     * @var string
     */
    private $serverApiKey = false;

    /**
     * @param string $serverApiKey
     * @param string $gcmUrl
     * @param string $caInfoPath
     */
    public function __construct($serverApiKey, $gcmUrl = null, $caInfoPath = null)
    {

        $this->serverApiKey = $serverApiKey;
        if ($gcmUrl) {
            $this->gcmUrl = $gcmUrl;
        }
        if ($caInfoPath) {
            $this->caInfoPath = $caInfoPath;
        }
    }

    /**
     * Send message to GCM without explicitly created message
     *
     * @throws Exception
     * @return Response
     */
    public function sendMessage()
    {

        $message = new Message();
        call_user_func_array([$message, 'bulkSet'], func_get_args());

        return $this->send($message);
    }

    /**
     * Send message to GCM
     *
     * @param Message $message
     *
     * @throws Exception
     * @return Response
     */
    public function send(Message $message)
    {

        if (!$this->serverApiKey) {
            throw new Exception("Server API Key not set", Exception::ILLEGAL_API_KEY);
        }

        //GCM response: Number of messages on bulk (1001) exceeds maximum allowed (1000)
        if (count($message->getRecipients()) > 1000) {
            throw new Exception("Malformed request: Registration Ids exceed the GCM imposed limit of 1000",
                Exception::MALFORMED_REQUEST);
        }

        $rawData = $this->formMessageData($message);
        $this->validatePayloadSize($rawData, 'data', 4096);
        $this->validatePayloadSize($rawData, 'notification', 2048);
        $data = json_encode($rawData);

        $headers = [
            'Authorization' => 'key=' . $this->serverApiKey,
            'Content-Type'  => 'application/json'
        ];

        $options = [
            'headers' => $headers,
            'body'    => $data
        ];

        if ($this->caInfoPath !== false) {
            $options['cert'] = $this->caInfoPath;
        }

        $client = new Client();

        $resultBody = $client->post($this->gcmUrl, $options);
        $resultHttpCode = $resultBody->getStatusCode();

        switch ($resultHttpCode) {
            case "200":
                //All fine. Continue response processing.
                break;

            case "400":
                throw new Exception('Malformed request. ' . $resultBody->getBody(), Exception::MALFORMED_REQUEST);
                break;

            case "401":
                throw new Exception('Authentication Error. ' . $resultBody->getBody(), Exception::AUTHENTICATION_ERROR);
                break;

            default:
                throw new Exception("Unknown error. " . $resultBody->getBody(), Exception::UNKNOWN_ERROR);
                break;
        }

        return new Response($message, $resultBody->getBody());
    }

    /**
     * Form raw message data for sending to GCM
     *
     * @param Message $message
     *
     * @return array
     */
    private function formMessageData(Message $message)
    {

        $data = [];

        if (!is_array($message->getRecipients())) {
            $data['to'] = $message->getRecipients();
        } else {
            $data['registration_ids'] = $message->getRecipients();
        }

        $dataFields = [
            'collapse_key'            => 'getCollapseKey',
            'data'                    => 'getData',
            'notification'            => 'getNotification',
            'delay_while_idle'        => 'getDelayWhileIdle',
            'time_to_live'            => 'getTtl',
            'restricted_package_name' => 'getRestrictedPackageName',
            'dry_run'                 => 'getDryRun',
            'content_available'       => 'getContentAvailable',
            'priority'                => 'getPriority'
        ];

        foreach ($dataFields as $fieldName => $getter) {
            if ($message->$getter() != null) {
                $data[$fieldName] = $message->$getter();
            }
        }

        return $data;
    }

    /**
     * Validate size of json representation of passed payload
     *
     * @param array  $rawData
     * @param string $fieldName
     * @param int    $maxSize
     *
     * @throws Exception
     * @return void
     */
    private function validatePayloadSize(array $rawData, $fieldName, $maxSize)
    {

        if (!isset($rawData[$fieldName])) {
            return;
        }
        if (strlen(json_encode($rawData[$fieldName])) > $maxSize) {
            throw new Exception(
                ucfirst($fieldName) . " payload is to big (max {$maxSize} bytes)",
                Exception::MALFORMED_REQUEST
            );
        }
    }

}
