#CleverAge\Trac


Interface PHP 5.3+ to query [TRAC](http://trac.edgewall.org/) via its `RPC` api. `JSON` version is actually 
used.

## Installation

With composer : `php composer.phar require "cleverage/php-trac": "dev-master"`

It needs Buzz (default with Curl) or Guzzle to proceed HTTP queries.

## Compatibility

Tested with Trac 0.12 and API version 1.1.2-r12546.


##Exemples

    $tracOptions = array(
        'url' => 'http://www.mytrac.org',
    );
    
    $client = new \CleverAge\Trac\HttpClient\Guzzle\GuzzleHttpClient();
    // $client = new \CleverAge\Trac\HttpClient\Buzz\BuzzHttpClient();

    $trac = new \CleverAge\Trac\TracApi($tracOptions, $client);

    $ticket = $trac->getTicketById(101);
    echo $ticket->id. ' : '.$ticket->status;
    
    $tickets = $trac->getTicketByStatus($status='closed', $limit=100);
    foreach ($tickets as $ticket) {
        echo $ticket->id. ' : '.$ticket->status;
    }

## Options

* `url` (**required**): The trac main url
* `ticket.class`: The class object to use when getting tickets. Default is `CleverAge\Trac\Ticket`.
* `auth`: Supports **none** and **Basic http**. Use `CleverAge\Trac\TracApi::AUTH_*` constants, default is `AUTH_NONE`.
    * if `auth` is `AUTH_BASIC`, then you must provide `user.login` and `user.password`.

## Performances

If you use Guzzle HttpClient, some requests are parallelized, so it improves performances, using MultiCurl :

    $tracOptions = array(
        'url' => 'http://www.mytrac.org',
    );
    
    $client = new \CleverAge\Trac\HttpClient\Guzzle\GuzzleHttpClient();
    $client->setParallelLimit(10); // default is 5

    $trac = new \CleverAge\Trac\TracApi($tracOptions, $client);

    $tickets = $trac->getManyTicketsByIds(array(100, 101, 102, 103));
    foreach ($tickets as $ticket) {
        echo $ticket->id. ' : '.$ticket->status;
    }
