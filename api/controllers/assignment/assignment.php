<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Container;

class AssignmentController
{
    // Identify relevant db table names
    private static $assigned_table_name = "assigned_contracts";
    private static $desired_table_name = "desired_contracts";
    private static $contracts_table_name = "contracts";
    protected $container; // To receive container instance

    // constructor receives container instance
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /*
     * GET
     * loadAssignables()
     * loads a list of contracts that fits employee's preferences
     * @args : {eid} employee_id
     */
    public function loadAssignables(Request $request, Response $response, array $args)
    {
        // check prefs
        $results = array();
        $prefs = array();
        $connection = $this->container->get("db");
        $eid_param = $args['eid'];
        $queryTxt = "SELECT desired_category, desired_type FROM ".AssignmentController::$desired_table_name." WHERE employee_id =:eid";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            extract($row);
            array_push($prefs, $desired_category);
            array_push($prefs, $desired_type);
        }
        if (count($prefs) != 2)
        {
            //if no result was found
            $response = $response->withStatus(404);
            $response = $response->withHeader("Content-Type", "application/json");
            $results["error"] = "incomplete preferences";
            $response->getBody()->write(json_encode($results));
            return $response;
        }

        // load matching contracts
        $queryTxt2 = "SELECT * FROM ".AssignmentController::$contracts_table_name." WHERE contract_category =:cat AND type_of_service =:type ";
        $stmt2 = $connection->prepare($queryTxt2);
        $stmt2->bindValue(':cat', $prefs[0], PDO::PARAM_STR);
        $stmt2->bindValue(':type', $prefs[1], PDO::PARAM_STR);
        $stmt2->execute();
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC))
        {
            array_push($results, $row);
        }

        // Push results
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results));
        return $response;
    }

    /*
     * POST
     * assignContract()
     * assigns a contract to the employee
     * @args : {eid} employee_id
     * @body : {cid} contract_id
     * returns list of contracts assigned to employee
     */
    public function assignContract(Request $request, Response $response, array $args)
    {
        $connection = $this->container->get("db");
        $eid_param = $args['eid'];
        $request_body = $request->getParsedBody();
        $queryTxt = "INSERT INTO ".AssignmentController::$assigned_table_name."(employee_id, contract_id) VALUES(:eid, :cid)";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $request_body['cid'], PDO::PARAM_INT);
        $stmt->execute();

        $results = array();

        $queryTxt2 = "SELECT * FROM ".AssignmentController::$assigned_table_name." WHERE employee_id =:eid ";
        $stmt2 = $connection->prepare($queryTxt2);
        $stmt2->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt2->execute();
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC))
        {
            array_push($results, $row);
        }

        // Push results
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results));
        return $response;
    }


    /*
     * POST
     * updateHours()
     * @args : {eid} employee_id, {cid} contract_id
     * @body : {numHours} number of hours worked
     */
    public function updateHours(Request $request, Response $response, array $args)
    {
        $connection = $this->container->get("db");
        $eid_param = $args['eid'];
        $cid_param = $args['cid'];
        $request_body = $request->getParsedBody();
        $queryTxt = "UPDATE ".AssignmentController::$assigned_table_name." SET hours_worked = hours_worked + :numHours WHERE employee_id =:eid AND contract_id =:cid ";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':numHours', $request_body['numHours'], PDO::PARAM_INT);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $cid_param, PDO::PARAM_INT);
        $stmt->execute();
        
        $queryTxt = "UPDATE ".AssignmentController::$assigned_table_name." SET hours_worked = :numHours WHERE employee_id =:eid AND contract_id =:cid AND hours_worked is NULL";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':numHours', $request_body['hours_worked'], PDO::PARAM_INT);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $cid_param, PDO::PARAM_INT);
        $stmt->execute();

        $results = array();
        $queryTxt2 = "SELECT * FROM ".AssignmentController::$assigned_table_name." WHERE employee_id = :eid AND contract_id = :cid";
        $stmt2 = $connection->prepare($queryTxt2);
        $stmt2->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt2->bindValue(':cid', $cid_param, PDO::PARAM_INT);
        $stmt2->execute();
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC))
        {
            array_push($results, $row);
        }

        // Push results
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results[0]));
        return $response;
    }

    /*
     * GET
     * loadAssignedContracts()
     *
     */
    public function loadAssignedContracts(Request $request, Response $response, array $args)
    {
        $results = array();

        $connection = $this->container->get("db");
        $eid_param = $args['eid'];
        $queryTxt = "SELECT * FROM ".AssignmentController::$assigned_table_name." WHERE employee_id = :eid ";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->execute();

        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            array_push($results, $row);
        }

        // Push results
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results));
        return $response;
    }
    /*
     * DELETE
     * removeEmployee()
     *
     */
    public function removeEmployee(Request $request, Response $response, array $args)
    {
        $connection = $this->container->get("db");
        $eid_param = $args['eid'];
        $cid_param = $args['cid'];
        $queryTxt = "DELETE FROM ".AssignmentController::$assigned_table_name." WHERE employee_id =:eid AND contract_id =:cid";
        $stmt = $connection->prepare($queryTxt);
        $stmt->bindValue(':eid', $eid_param, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $cid_param, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            $results['message'] = "Removal of employee ".$eid_param." was successful.";
        } else {
            $response = $response->withStatus(500);
            $results['error'] = "Removal of employee ".$eid_param." was unsuccessful.";
        }

        // Push results
        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($results));
        return $response;
    }

}
