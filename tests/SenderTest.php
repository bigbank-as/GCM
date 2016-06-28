<?php
namespace Bigbank\Gcm\Test;

use Bigbank\Gcm\Exception;
use Bigbank\Gcm\Message;
use Bigbank\Gcm\Sender;

/**
 * @coversDefaultClass \Bigbank\Gcm\Sender
 */
class SenderTest extends TestCase
{

    /**
     * @covers ::__construct
     * @covers ::send
     * @covers ::formMessageData
     * @covers ::validatePayloadSize
     * @expectedException Exception
     * @expectedExceptionCode 1
     */
    public function testApiKeyCheck()
    {
        $sender = new Sender(null);
        $message = new Message();
        $sender->send($message);
    }

    /**
     * @covers ::__construct
     * @covers ::send
     * @covers ::formMessageData
     * @covers ::validatePayloadSize
     * @expectedException Exception
     * @expectedExceptionCode 3
     */
    public function testPayloadDataSizeCheck()
    {
        $sender = new Sender("MY API KEY ))");
        $data = [];
        for ($i = 0; $i < 4096; $i++) {
            $data['key'.$i] = $i;
        }
        $message = new Message(array(), $data);
        $sender->send($message);
    }

    /**
     * @covers ::__construct
     * @covers ::send
     * @covers ::formMessageData
     * @covers ::validatePayloadSize
     * @expectedException Exception
     * @expectedExceptionCode 3
     */
    public function testPayloadNotificationSizeCheck()
    {
        $sender = new Sender("MY API KEY ))");
        $notification = ['key' => str_repeat('x', 2048)];
        $message = (new Message())
            ->setNotification($notification);
        $sender->send($message);
    }

}
