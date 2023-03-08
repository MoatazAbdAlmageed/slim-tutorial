<?php

declare(strict_types=1);

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

/**
 * @param ContainerInterface|null $container
 * @param ResponseInterface $response
 * @return array
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function addUsers(?ContainerInterface $container, ResponseInterface $response): array
{
    inform('adding users ....');
    $sql = "INSERT INTO users (`name`, `email`, `phone`) VALUES (:name, :email, :phone)";
    $stmt = $container->get('connection')->prepare($sql);
    $settings = $container->get('settings');
    for ($x = 0; $x <= $settings['rows']; $x++) {
        $x++;
        $name = 'User' . $x;
        $email = 'user' . $x . '@gmail.com';
        $phone = '01150064746';
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $result = $stmt->execute();
    }

    return array($sql, $stmt, $x, $name, $result);
}

/**
 * @param ContainerInterface|null $container
 * @param ResponseInterface $response
 * @return array
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function addProducts(?ContainerInterface $container, ResponseInterface $response): array
{
    inform('adding products ....');
    $sql = "INSERT INTO products (`name`) VALUES (:name)";
    $stmt = $container->get('connection')->prepare($sql);
    $settings = $container->get('settings');

    for ($x = 0; $x <= $settings['rows']; $x++) {
        $x++;
        $name = "Product $x";
        $stmt->bindParam(':name', $name);
        $result = $stmt->execute();
    }

    return array($sql, $stmt, $x, $result);
}

function inform($message)
{
    echo "$message </br>";
}

/**
 * @param ContainerInterface|null $container
 * @param ResponseInterface $response
 * @return void
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function addTransactions(?ContainerInterface $container, ResponseInterface $response): void
{
    inform('adding transactions ....');
    $sql = "INSERT INTO transactions (`user_id`,`product_id`,`amount`) VALUES (:user_id,:product_id,:amount)";
    $stmt = $container->get('connection')->prepare($sql);


    $amount = 200;


    $stmt->bindParam(':amount', $amount);
    $settings = $container->get('settings');
    for ($x = 0; $x <= $settings['rows']; $x++) {
        $x++;
        $user_id = $x;
        $stmt->bindParam(':user_id', $user_id);
        $product_id = $x;
        $stmt->bindParam(':product_id', $product_id);
        $result = $stmt->execute();
    }

}

/**
 * @param ContainerInterface|null $container
 * @return void
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function createtables(?ContainerInterface $container): void
{
    inform('creating tables ....');
    $container->get('connection')->exec(' 
drop table if exists users;
drop table if exists transactions;
drop table if exists products;
drop table if exists location;
create table IF NOT EXISTS users (id int NOT NULL AUTO_INCREMENT, name varchar(255), email varchar(255), phone varchar(255), created_at datetime ,updated_at datetime , PRIMARY KEY (id));
create table IF NOT EXISTS products ( id int NOT NULL AUTO_INCREMENT, name varchar(255)  ,  created_at date ,updated_at datetime,PRIMARY KEY (id));
create table IF NOT EXISTS transactions ( id int NOT NULL AUTO_INCREMENT,user_id int ,product_id int, amount varchar(255)  ,  created_at datetime ,updated_at datetime ,PRIMARY KEY (id));
');
}

return function (App $app) {
    $container = $app->getContainer();
    $app->get('/install', function (RequestInterface $request, ResponseInterface $response, $args) use ($container) {

        try {
            createtables($container);
            addUsers($container, $response);
            addProducts($container, $response);
            addTransactions($container, $response);

            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);

        } catch (PDOException $exception) {
            return $exception->getMessage();
        }
    });
    $app->get('/transactions', function (RequestInterface $request, ResponseInterface $response, $args) use ($container) {

        try {
            $sql = "
                SELECT transactions.amount amount, users.name user ,products.name product
                FROM transactions
                INNER JOIN users ON transactions.user_id = users.id
                INNER JOIN products ON transactions.product_id = products.id;
";
            $stmt = $container->get('connection')->query($sql);
            $transactions = $stmt->fetchAll(PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($transactions));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    });

};
