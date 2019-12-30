<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

return function (App $app) {
    $app->get('/hello/{name}', function (RequestInterface $request, ResponseInterface $response, $args) {

        $name = $args['name'];
        $response->getBody()->write("Hello, {$name}");

        return $response;

    });

    $app->get('/user/{name}', function (RequestInterface $request, ResponseInterface $response, $args) {

        $name = $args['name'];
        $response->getBody()->write("Hello, $name");
        return $response;

    });
};
