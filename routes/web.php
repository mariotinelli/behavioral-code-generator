<?php

/** @var \Laravel\Lumen\Routing\Router $router */



/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
        return $router->app->version();
});


$router->post('/events', 'EventController@post');
$router->get('/events', 'EventController@get');
$router->get('/events/{eventId}', 'EventController@get');
$router->put('/events/{eventId}', 'EventController@put');
$router->delete('/events/{eventId}', 'EventController@delete');
$router->post('/event_types', 'EventTypeController@post');
$router->get('/event_types', 'EventTypeController@get');
$router->get('/event_types/{eventTypeId}', 'EventTypeController@get');
$router->put('/event_types/{eventTypeId}', 'EventTypeController@put');
$router->delete('/event_types/{eventTypeId}', 'EventTypeController@delete');






