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
function addUsers(ContainerInterface $container)
{
    inform('adding users ....');
    $sql = "INSERT INTO users (`name`, `email`, `phone`) VALUES (:name, :email, :phone)";
    $stmt = $container->get('connection')->prepare($sql);
    $settings = $container->get('settings');

    for ($x = 1; $x <= $settings['rows']; $x++) {
        $name = 'User' . $x;
        $email = 'user' . $x . '@gmail.com';
        $phone = '01150064746';
        $stmt->execute([':name' => $name, ':email' => $email, ':phone' => $phone]);
    }
}


/**
 * @param ContainerInterface|null $container
 * @param ResponseInterface $response
 * @return array
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function addProducts(ContainerInterface $container)
{
    inform('adding products ....');
    $sql = "INSERT INTO products (`name`) VALUES (:name)";
    $stmt = $container->get('connection')->prepare($sql);
    $settings = $container->get('settings');

    for ($x = 1; $x <= $settings['rows']; $x++) {
        $name = "Product " . strval($x);
        $stmt->execute([':name' => $name]);
    }

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
function addTransactions(ContainerInterface $container): void
{
    inform('adding transactions ....');
    $sql = "INSERT INTO transactions (`user_id`,`product_id`,`amount`) VALUES (:user_id,:product_id,:amount)";
    $stmt = $container->get('connection')->prepare($sql);
    $settings = $container->get('settings');
    for ($x = 1; $x <= $settings['rows']; $x++) {
        $user_id = $x;
        $product_id = $x;
        $amount = 200;
        $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id,
            ':amount' => $amount,
        ]);
    }
}

/**
 * @param ContainerInterface|null $container
 * @return void
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function createTables(?ContainerInterface $container): void
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
            createTables($container);
            addUsers($container);
            addProducts($container);
            addTransactions($container);

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
