#CleverAge\PHPTrac


Interface PHP 5.3+ to query [TRAC](http://trac.edgewall.org/) via its `RPC` api. `JSON` version is actually 
used.

## Compatibility

Tested with Trac 0.12 and API version 1.1.2-r12546.


##Exemples

    $tracOptions = array(
        'url' => 'http://www.mytrac.org',
    );
    
    $trac = new \CleverAge\PHPTrac\TracApi($tracOptions);

    $ticket = $trac->getTicketById(101);
    echo $ticket->id. ' : '.$ticket->status;
    
    $tickets = $trac->getTicketByStatus($status='closed', $limit=100);
    foreach ($tickets as $ticket) {
        echo $ticket->id. ' : '.$ticket->status;
    }

## Options

* `url` (**required**): The trac main url
* `ticket.class`: The class object to use when getting tickets. Default is `CleverAge\PHPTrac\Ticket`.
* `auth`: Supports **none** and **Basic http**. Use `CleverAge\PHPTrac\TracApi::AUTH_*` constants, default is `AUTH_NONE`.
    * if `auth` is `AUTH_BASIC`, then you must provide `user.login` and `user.password`.