# USAGE

1. All queries must be sent to this address: http://your_site.com/xmlrpc.
2. If you want make requests from your account you must specify your public api key. For example - http://your_site.com/xmlrpc?api_key=xxx.
3. Public api key is the optional parameter if you make requests without your api key you will be determined as a guest.
4. Some methods requires signature. See below how to build the authenticate signature.

## Build the authenticate signature example.

    use Zend\XmlRpc\Client as Client;

    // list all your parameters in array
    $methodParams = [
        'timeZone' => 'Asia/Bishkek'
    ];

    // order them
    asort($methodParams);

    // generate your signature
    $signature = md5(implode(':', array_merge($methodParams, ['__YOUR__SECRET_API_KEY__'])));

    // now we can send the request
    try {
        $client = new Client('http://my-site.com/xmlrpc?api_key=__YOUR_PUBLIC_API_KEY__');
        $client->skipSystemLookup(true);
        $client->call('user.setUserTimeZone', [
            $methodParams['timeZone'],
            $signature
        ]);
    }
    catch(\Exception $e) {
        echo $e->getMessage();
    }