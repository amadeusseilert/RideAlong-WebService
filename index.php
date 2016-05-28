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

    $data = explode(".",$ride);
    $origin = $data[0];
    $destination = $data[1];
    $response = new Response();

    try {
        $result = $client->scan([
            'TableName' => 'Rides',
            'Origin' => $origin,
            'Destination' => $destination
        ]);

        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($result->get ( 'Items' )));
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent($e->getMessage());
        return $response;
    }
});

// Searches rides
$app->get('/api/search/ride', function () use ($client) {
    $response = new Response();
    try {
        $result = $client->scan([
            'TableName' => 'Rides',
        ]);

        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($result->get ( 'Items' )));
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent($e->getMessage());
        return $response;
    }
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
                'Driver' => ['N' => $data->driver],
                'Slots' => ['N' => $data->slots],
                'Origin' => ['S' => $data->origin],
                'Destination' => ['S' => $data->dest]
            ]
        ]);
        echo $result;
    } catch (DynamoDbException $e){
        echo $e->getMessage();
    }

});


$app->handle();