<?php
/**
 * Created by PhpStorm.
 * User: amadeus.seilert
 * Date: 17/04/2016
 * Time: 15:41
 */

require_once 'vendor\autoload.php';

use Aws\DynamoDb\Exception\DynamoDbException;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;

$app = new Micro();
$client = null;

try {
    $client = \Aws\DynamoDb\DynamoDbClient::factory(array(
        'region' => 'sa-east-1',
        'version' => 'latest',
        'credentials' => array(
            'key' => 'AKIAIQO3IGZF3TUAFR6Q',
            'secret' => '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'
        )
    ));
} catch (DynamoDbException $e){
    echo $e->getMessage();
    exit();
}
// Retrieves all ride locations
$app->get('/api/locations', function () use ($client) {

    $result = null;
    $response = new Response();

    try {
        $result = $client->scan([
            'TableName' => 'RideLocations'
        ]);
        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($result->get ( 'Items' )));
        return $response;

    } catch (DynamoDbException $e) {
        $response->setStatusCode(400, "Bad Request");
        $response->setContent($e->getMessage());
        return $response;
    }
});

// Searches rides
$app->get('/api/search/ride/{ride}', function ($ride) use ($client) {

    parse_str($ride, $data);
    $response = new Response();
    echo $ride;
    $origin = null;
    $destination = null;
    $date = null;
    $time = null;
//    try {
//        $origin = $data['origin'];
//        $destination = $data['destination'];
//        $date = $data['date'];
//        $time = $data['time'];
//        $result = $client->query([
//            'TableName' => 'Rides',
//            'ConsistentRead' => true,
//            'ProjectionExpression' => ''
//            'Origin' => $origin,
//            'Destination' => $destination,
//            'Date' => $date,
//            'Time' => $time
//        ]);
//
//        $response->setStatusCode(200, "OK");
//        $response->setContent(json_encode($result->get ( 'Items' )));
//        return $response;
//    } catch (DynamoDbException $e){
//        $response->setStatusCode(400, "Bad Request");
//        $response->setContent($e->getMessage());
//        return $response;
//    }
});

// Adds a new ride
$app->post('/api/add/ride', function () use ($client, $app){

    $data = $app->request->getJsonRawBody();
    $response = new Response();

    try {
        $result = $client->putItem([
            'TableName' => 'Rides',
            'Item' => [
                'RidesId' => ['S' => uniqid()], // Primary Key
                'Date' => ['S' => $data->date],
                'Time' => ['S' => $data->time],
                'Driver' => ['S' => $data->driver],
                'Slots' => ['N' => $data->slots],
                'Origin' => ['S' => $data->origin],
                'Destination' => ['S' => $data->destination]
            ]
        ]);

        $response->setStatusCode(200, "OK");
        $response->setContent("");
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent($e->getMessage());
        return $response;
    }

});


$app->handle();