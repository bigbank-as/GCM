<?php
namespace Bigbank\Gcm;

/**
 * Gcm response parser
 */
class Response
{

    /**
     * Unique ID (number) identifying the multicast message.
     *
     * @var integer
     */
    private $multicastId = null;

    /**
     * Unique id identifying the single message.
     *
     * Only have value if single or topic message is sent to google
     *
     * @var int
     */
    private $messageId = null;

    /**
     * Number of messages that were processed without an error.
     *
     * @var integer
     */
    private $success = null;

    /**
     * Number of messages that could not be processed.
     *
     * @var integer
     */
    private $failure = null;

    /**
     * Number of results that contain a canonical registration ID.
     *
     * @var integer
     */
    private $canonicalIds = null;

    /**
     * Holds single message error
     *
     * @var string
     */
    private $error = null;

    /**
     * Array of objects representing the status of the messages processed.
     * The objects are listed in the same order as the request
     * (i.e., for each registration ID in the request, its result is listed in the same index in the response)
     * and they can have these fields:
     *      message_id: String representing the message when it was successfully processed.
     *      registration_id: If set, means that GCM processed the message but it has another canonical
     *                       registration ID for that device, so sender should replace the IDs on future requests
     *                       (otherwise they might be rejected). This field is never set if there is an error in the request.
     *      error: String describing an error that occurred while processing the message for that recipient.
     *             The possible values are the same as documented in the above table, plus "Unavailable"
     *             (meaning GCM servers were busy and could not process the message for that particular recipient,
     *             so it could be retried).
     *
     * @var array
     */
    private $results = [];

    /**
     * @param Message $message
     * @param string  $responseBody json string of google cloud message server response
     *
     * @throws Exception
     */
    public function __construct(Message $message, $responseBody)
    {

        $data = \json_decode($responseBody, true);
        if ($data === null) {
            throw new Exception("Malformed response body. " . $responseBody, Exception::MALFORMED_RESPONSE);
        }

        if (!$data['error']) {
            $this->messageId    = (isset($data['message_id'])) ? $data['message_id'] : null;
            $this->multicastId  = $data['multicast_id'];
            $this->failure      = $data['failure'];
            $this->success      = (!$this->multicastId) ? 1 : $data['success'];
            $this->canonicalIds = $data['canonical_ids'];
            $this->results      = [];
            $this->parseResults($message, $data);
        } else {
            $this->error = $data['error'];
            $this->messageId    = (isset($data['message_id'])) ? $data['message_id'] : null;
            $this->failure = (!isset($data['failure'])) ? 1 : $data['failure'];
        }
    }

    /**
     * @return int
     */
    public function getMulticastId()
    {

        return $this->multicastId;
    }

    /**
     * @return int|null
     */
    public function getMessageId()
    {

        return $this->messageId;
    }

    /**
     * @return int
     */
    public function getSuccessCount()
    {

        return $this->success;
    }

    /**
     * @return int
     */
    public function getFailureCount()
    {

        return $this->failure;
    }

    /**
     * @return int
     */
    public function getNewRegistrationIdsCount()
    {

        return $this->canonicalIds;
    }

    /**
     * @return array
     */
    public function getResults()
    {

        return $this->results;
    }

    /**
     * @return string
     */
    public function getError()
    {

        return $this->error;
    }

    /**
     * Return an array of expired registration ids linked to new id
     * All old registration ids must be updated to new ones in DB
     *
     * @return array oldRegistrationId => newRegistrationId
     */
    public function getNewRegistrationIds()
    {

        if ($this->getNewRegistrationIdsCount() == 0) {
            return [];
        }
        $filteredResults = array_filter($this->results,
            function ($result) {

                return isset($result['registration_id']);
            });

        $data = array_map(function ($result) {

            return $result['registration_id'];
        }, $filteredResults);

        return $data;
    }

    /**
     * Returns an array containing invalid registration ids
     * They must be removed from DB because the application was uninstalled from the device.
     *
     * @return array
     */
    public function getInvalidRegistrationIds()
    {

        if ($this->getFailureCount() == 0) {
            return [];
        }
        $filteredResults = array_filter($this->results,
            function ($result) {

                return (
                    isset($result['error'])
                    &&
                    (
                        ($result['error'] == "NotRegistered")
                        ||
                        ($result['error'] == "InvalidRegistration")
                    )
                );
            });

        return array_keys($filteredResults);
    }

    /**
     * Returns an array of registration ids for which you must resend a message,
     * cause devices are not available now.
     *
     * @return array
     */
    public function getUnavailableRegistrationIds()
    {

        if ($this->getFailureCount() == 0) {
            return [];
        }
        $filteredResults = array_filter($this->results,
            function ($result) {

                return (
                    isset($result['error'])
                    &&
                    ($result['error'] == "Unavailable")
                );
            });

        return array_keys($filteredResults);
    }

    /**
     * Parse result array with correct data
     *
     * @param Message $message
     * @param array   $response
     */
    private function parseResults(Message $message, array $response)
    {

        if (is_array($message->getRecipients())) {
            foreach ($message->getRecipients() as $key => $registrationId) {
                $this->results[$registrationId] = $response['results'][$key];
            }
        } else {
            $this->results[$message->getRecipients()] = $response['results'];
        }
    }
}
