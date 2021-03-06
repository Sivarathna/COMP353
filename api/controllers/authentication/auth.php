<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Container;

class AuthController
{
    private static $table_name = "user_credentials";

    protected $container;

    // constructor receives container instance
    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function login(Request $request, Response $response, array $args) {
        $results = array();
        $connection = $this->container->get("db");
        $request_body = $request->getParsedBody();
        $stmt = $connection->prepare("SELECT * FROM ".AuthController::$table_name." WHERE username=:username AND password=:password LIMIT 1");
        $stmt->bindValue(':username', $request_body['username'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $request_body['password'], PDO::PARAM_STR);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            $user = array(
                "username" => $username,
                "type" => $user_type
            );
            array_push($results, $user);
        }
        if (count($results) < 1) {
            $response = $response->withStatus(403);
            $response = $response->withHeader("Content-Type", "application/json");
            $results["error"] = "Invalid credentials";
            $response->getBody()->write(json_encode($results));
            return $response;
        }

        // Add client data if a client is logging in
        if ($results[0]['type'] == 'client') {
            $stmt = $connection->prepare("SELECT company_name FROM clients WHERE company_email=:email LIMIT 1");
            $stmt->bindValue(':email', $results[0]['username'], PDO::PARAM_STR);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $results[0]['company'] = $company_name;
            }
        }

        $response = $response->withStatus(200);
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results[0]));
        return $response;
    }

}
