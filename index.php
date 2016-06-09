<?php
/**
 * Created by PhpStorm.
 * User: amadeus.seilert
 * Date: 17/04/2016
 * Time: 15:41
 */

require_once 'vendor\autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;

$app = new Micro();
$client = null;

function convert_time($time) {

    if ($time < 100) {
        if ($time < 10)  {
            $time = "0{$time}";
        }
        return "00:{$time}";
    } else {
        $hour = intval($time/100);
        $minutes = $time % 100;
        if ($hour < 10) {
            $hour = "0{$hour}";
        }
        if ($minutes < 10) {
            $minutes = "0{$minutes}";
        }
        return "{$hour}:{$minutes}";
    }
}

try {
    $client = DynamoDbClient::factory(array(
        'region' => 'sa-east-1',
        'version' => 'latest'
    ));
} catch (DynamoDbException $e){
    echo $e->getMessage();
    exit();
}
// Retrieves all ride locations
$app->get('/api/locations', function () use ($client) {

    $result = null;
    $response = new Response();
    $response->setContentType('application/json');
    try {
        $result = $client->scan([
            'TableName' => 'RideAlong_RideLocations'
        ]);

        $result = $result->get ( 'Items' );

        $data = array();

        foreach ($result as $item){
            $temp = array(
                'id' => $item['RideAlong_RideLocationsID']['S'],
                'name' => $item['RideAlong_RideLocationsName']['S'],
            );
            array_push($data, $temp);
        }

        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($data));
        return $response;

    } catch (DynamoDbException $e) {
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }
});

// Searches rides
$app->get('/api/search/ride/{origin}/{destination}/{date}/{time}', function ($origin, $destination, $date, $time) use ($client) {

    $response = new Response();
    $response->setContentType('application/json');
    $date = str_replace("-", "/", $date);
    $time = intval(str_replace(":", "", $time));

    try {

        $result = $client->scan([
            'TableName' => 'RideAlong_Rides',
            'ConsistentRead' => true,
            'Limit' => 20,
            'FilterExpression' =>
                'RideAlong_RideContext = :context_val AND
                RideAlong_RideDate = :date_val AND
                RideAlong_RideTime >= :time_start_val AND
                RideAlong_RideTime <= :time_end_val AND
                RideAlong_RideDestination = :destination_val AND
                RideAlong_RideOrigin = :origin_val AND
                RideAlong_RideSlots > :val',
            'ExpressionAttributeValues' =>  [
                ':context_val' => ['S' => 'UNIFESP'],
                ':origin_val' => ['S' => $origin],
                ':destination_val' => ['S' => $destination],
                ':date_val' => ['S' => $date],
                ':time_start_val' => ['N' => ($time - 100)],
                ':time_end_val' => ['N' => ($time + 100)],
                ':val' => ['N' => 0]
                                            ]
        ]);

        $result = $result->get('Items');

        foreach ($result as $item => $value){
            $result[$item]['RideAlong_RideTime']['N'] = convert_time($value['RideAlong_RideTime']['N']);
        }

        $data = array();

        foreach ($result as $item){
            $temp = array(
                'context' => $item['RideAlong_RideContext']['S'],
                'id' => $item['RideAlong_RideID']['S'],
                'driver' => $item['RideAlong_RideDriver']['S'],
                'date' => $item['RideAlong_RideDate']['S'],
                'time' => $item['RideAlong_RideTime']['N'],
                'destination' => $item['RideAlong_RideDestination']['S'],
                'origin' => $item['RideAlong_RideOrigin']['S'],
                'slots' => $item['RideAlong_RideSlots']['N']
            );
            array_push($data, $temp);
        }

        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($data));
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }
});

$app->get('/api/search/ride/{origin}/{destination}/{date}', function ($origin, $destination, $date) use ($client) {

    $response = new Response();
    $response->setContentType('application/json');
    $date = str_replace("-", "/", $date);

    try {

        $result = $client->scan([
            'TableName' => 'RideAlong_Rides',
            'ConsistentRead' => true,
            'Limit' => 20,
            'FilterExpression' =>
                'RideAlong_RideContext = :context_val AND
                RideAlong_RideDate = :date_val AND
                RideAlong_RideDestination = :destination_val AND
                RideAlong_RideOrigin = :origin_val AND
                RideAlong_RideSlots > :val',

            'ExpressionAttributeValues' =>  [
                ':context_val' => ['S' => 'UNIFESP'],
                ':origin_val' => ['S' => $origin],
                ':destination_val' => ['S' => $destination],
                ':date_val' => ['S' => $date],
                ':val' => ['N' => 0]
            ]
        ]);

        $result = $result->get('Items');

        foreach ($result as $item => $value){
            $result[$item]['RideAlong_RideTime']['N'] = convert_time($value['RideAlong_RideTime']['N']);
        }

        $data = array();

        foreach ($result as $item){
            $temp = array(
                'context' => $item['RideAlong_RideContext']['S'],
                'id' => $item['RideAlong_RideID']['S'],
                'driver' => $item['RideAlong_RideDriver']['S'],
                'date' => $item['RideAlong_RideDate']['S'],
                'time' => $item['RideAlong_RideTime']['N'],
                'destination' => $item['RideAlong_RideDestination']['S'],
                'origin' => $item['RideAlong_RideOrigin']['S'],
                'slots' => $item['RideAlong_RideSlots']['N']
            );
            array_push($data, $temp);
        }

        $response->setStatusCode(200, "OK");
        $response->setContent(json_encode($data));
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }
});

// Adds a new ride
$app->post('/api/add/ride', function () use ($client, $app){

    $data = $app->request->getJsonRawBody();

    $response = new Response();
    $response->setContentType('application/json');
    $data->time = intval(str_replace(":", "", $data->time));

    try {
        $new_id = uniqid();
        $result = $client->putItem([
            'TableName' => 'RideAlong_Rides',
            'Item' => [
                'RideAlong_RideContext' => ['S' => 'UNIFESP'], // Primary Context Key
                'RideAlong_RideID' => ['S' => $new_id],
                'RideAlong_RideTime' => ['N' => $data->time],
                'RideAlong_RideDate' => ['S' => $data->date],
                'RideAlong_RideDriver' => ['S' => $data->driver],
                'RideAlong_RideSlots' => ['N' => $data->slots],
                'RideAlong_RideOrigin' => ['S' => $data->origin],
                'RideAlong_RideDestination' => ['S' => $data->destination]
            ]
        ]);

        $content = array('id' => $new_id);

        $response->setStatusCode(201, "Created");
        $response->setContent(json_encode($content));
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }

});

$app->delete('/api/delete/ride/{context}/{id}', function ($context, $id) use ($client){

    $response = new Response();
    $response->setContentType('application/json');
    try {
        $result = $client->deleteItem([
            'TableName' => 'RideAlong_Rides',
            'Key' => [
                'RideAlong_RideContext' => ['S' => $context],
                'RideAlong_RideID' => ['S' => $id]
            ]
        ]);

        $response->setStatusCode(200, "OK");
        $response->setContent("");
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }
});

$app->delete('/api/reserve/ride/{context}/{id}', function ($context, $id) use ($client){

    $response = new Response();
    $response->setContentType('application/json');
    try {
        $result = $client->updateItem([
            'TableName' => 'RideAlong_Rides',
            'Key' => [
                'RideAlong_RideContext' => ['S' => $context],
                'RideAlong_RideID' => ['S' => $id]
            ],
            'ConditionExpression' => 'RideAlong_RideSlots > :cond',
            'UpdateExpression' => 'set RideAlong_RideSlots = RideAlong_RideSlots - :val',
            'ExpressionAttributeValues' => [
                ':val' => ['N' => 1],
                ':cond' => ['N' => 0]
            ]
        ]);

        $response->setStatusCode(200, "OK");
        $response->setContent("");
        return $response;
    } catch (DynamoDbException $e){
        $response->setStatusCode(400, "Bad Request");
        $response->setContent('');
        return $response;
    }
});

$app->handle();