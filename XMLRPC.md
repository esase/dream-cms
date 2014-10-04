# USAGE

1. All queries must be sent to this address: http://your_site.com/xmlrpc.
2. If you want make requests from your account you must specify your pablic api key. For example - http://your_site.com/xmlrpc?api_key=xxx.
3. Public api key is optional parameter if you make requests without your api key you will be determined as a guest.
4. Some methods requires signature. See below how to build the authenticate signature.

## Build the authenticate signature.

    // list all your parameters in array
    $methodParams = array(
        'timeZone' => 'Asia/Bishkek'
    );

    // order them
    asort($methodParams);

    // generate your signature
    $signature = md5(implode(':', array_merge($methodParams, array('__YOUR__SECRET_API_KEY__'))));

    // now we can send the request
    $client->call('user.setUserTimeZone', array($methodParams['timeZone'], $signature));