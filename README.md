# Google Cloud Messaging (GCM)

A PHP library to send messages to devices registered through Google Cloud Messaging

See GCM documentation: http://developer.android.com/guide/google/gcm/index.html

Example usage
-----------------------
``` php

use Bigbank\Gcm\Sender;
use Bigbank\Gcm\Message;

$sender = new Sender("YOUR GOOGLE API KEY", $GcmEndpoint);

$message = new Message(
        ["device_registration_id1", "device_registration_id2"],
        ["data1" => "123", "data2" => "string"]
);

$message
    ->notification(["title" => "foo", "body" => "bar"])
    ->setCollapseKey("collapse_key")
    ->setDelayWhileIdle(true)
    ->setTtl(123)
    ->setRestrictedPackageName("com.example")
    ->setDryRun(true)
;

try {
    $response = $sender->send($message);
} catch (\Exception $exception) {
    throw new \Exception($exception->getMessage());
}

```

Note about cURL SSL verify peer option
-----------------------
Library has turned off CURLOPT_SSL_VERIFYPEER by default, but you can enable it by passing third parameter into constructor of Sender class.

You need to [download](http://curl.haxx.se/docs/caextract.html) root certificates and add them somewhere into your project directory. Then construct Sender object like this:

``` php

use Bigbank\Gcm\Sender;

$sender = new Sender("YOUR GOOGLE API KEY", $GcmEndpoint, "/path/to/cacert.crt");

```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Credits

- [Bigbank's developers][link-bb-developers]
- [All Contributors][link-contributors]

## License

The Apache 2 License. Please see [License File](LICENSE.md) for more information.