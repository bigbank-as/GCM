<?php
namespace Bigbank\Gcm\Test;

use Bigbank\Gcm\Message;
use Bigbank\Gcm\Response;

/**
 * @coversDefaultClass \Bigbank\Gcm\Response
 */
class ResponseTest extends TestCase
{

    /**
     * @var Response
     */
    private $response;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $message = new Message([1, 2, 3, 4, 5, 6]);
        $responseBody = '{ "multicast_id": 216,
            "success": 3,
            "failure": 3,
            "canonical_ids": 1,
            "results": [
              { "message_id": "1:0408" },
              { "error": "Unavailable" },
              { "error": "InvalidRegistration" },
              { "message_id": "1:1516" },
              { "message_id": "1:2342", "registration_id": "32" },
              { "error": "NotRegistered"}
            ],
            "error": null
          }';

        $this->response = new Response($message, $responseBody);
    }

    /**
     * @covers ::getNewRegistrationIds
     */
    public function testGetNewRegistrationIds()
    {
        $this->assertEquals([5 => 32], $this->response->getNewRegistrationIds());
    }

    /**
     * @covers ::getInvalidRegistrationIds
     * @covers ::getFailureCount
     */
    public function testGetInvalidRegistrationIds()
    {
        $this->assertEquals([3, 6], $this->response->getInvalidRegistrationIds());
    }

    /**
     * @covers ::getUnavailableRegistrationIds
     * @covers ::getFailureCount
     */
    public function testGetUnavailableRegistrationIds()
    {
        $this->assertEquals([2], $this->response->getUnavailableRegistrationIds());
    }
}
