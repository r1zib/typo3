<?php
// session_start() has to go right at the top, before any output!
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>REST/OAuth Example</title>
    </head>
    <body>
        <tt>
            <?php

            require_once ('soapclient/SforcePartnerClient.php');
            require_once ('soapclient/SforceEnterpriseClient.php');

            define("USERNAME", "user@example.com");
            define("PASSWORD", "password");
            define("SECURITY_TOKEN", "sdfhkjwrhgfwrgergp");

            try {
                echo "<table border=\"1\"><tr><td>";
                echo "First with the enterprise client<br/><br/>\n";

                $mySforceConnection = new SforceEnterpriseClient();
                $mySforceConnection->createConnection("soapclient/enterprise.wsdl.xml");

                // Simple example of session management - first call will do
                // login, refresh will use session ID and location cached in
                // PHP session
                if (isset($_SESSION['enterpriseSessionId'])) {
                    $location = $_SESSION['enterpriseLocation'];
                    $sessionId = $_SESSION['enterpriseSessionId'];

                    $mySforceConnection->setEndpoint($location);
                    $mySforceConnection->setSessionHeader($sessionId);

                    echo "Used session ID for enterprise<br/><br/>\n";
                } else {
                    $mySforceConnection->login(USERNAME, PASSWORD.SECURITY_TOKEN);

                    $_SESSION['enterpriseLocation'] = $mySforceConnection->getLocation();
                    $_SESSION['enterpriseSessionId'] = $mySforceConnection->getSessionId();

                    echo "Logged in with enterprise<br/><br/>\n";
                }

                $query = "SELECT Id, FirstName, LastName, Phone from Contact";
                $response = $mySforceConnection->query($query);

                echo "Results of query '$query'<br/><br/>\n";
                foreach ($response->records as $record) {
                    echo $record->Id.": ".$record->FirstName." "
                   .$record->LastName." ".$record->Phone."<br/>\n";
                }

                echo "<br/>Now, create some records<br/><br/>\n";

                $records = array();

                $records[0] = new stdclass();
                $records[0]->FirstName = 'John';
                $records[0]->LastName = 'Smith';
                $records[0]->Phone = '(510) 555-5555';
                $records[0]->BirthDate = '1957-01-25';

                $records[1] = new stdclass();
                $records[1]->FirstName = 'Mary';
                $records[1]->LastName = 'Jones';
                $records[1]->Phone = '(510) 486-9969';
                $records[1]->BirthDate = '1977-01-25';

                $response = $mySforceConnection->create($records, 'Contact');

                $ids = array();
                foreach ($response as $i => $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $records[$i]->FirstName." ".$records[$i]->LastName
                                ." ".$records[$i]->Phone." created with id "
                                .$result->id."<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                    array_push($ids, $result->id);
                }

                echo "<br/>Retrieve the newly created records:<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->FirstName." "
                   .$record->LastName." ".$record->Phone."<br/>\n";
                }

                echo "<br/>Next, update the new records<br/><br/>\n";

                $records[0] = new stdclass();
                $records[0]->Id = $ids[0];
                $records[0]->Phone = '(415) 555-5555';

                $records[1] = new stdclass();
                $records[1]->Id = $ids[1];
                $records[1]->Phone = '(415) 486-9969';

                $response = $mySforceConnection->update($records, 'Contact');
                foreach ($response as $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $result->id." updated<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }

                echo "<br/>Retrieve the updated records to check the update:<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->FirstName." "
                   .$record->LastName." ".$record->Phone."<br/>\n";
                }

                echo "<br/>Let's remove those phone numbers<br/><br/>\n";

                $records[0] = new stdclass();
                $records[0]->Id = $ids[0];
                $records[0]->fieldsToNull = 'Phone';

                $records[1] = new stdclass();
                $records[1]->Id = $ids[1];
                $records[1]->fieldsToNull = 'Phone';

                $response = $mySforceConnection->update($records, 'Contact');
                foreach ($response as $result) {
                    echo ($result->success == 1)
                           &nbsp;? $result->id." updated<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }

                echo "<br/>Retrieve the updated records again to check the update:";
                echo "<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->FirstName." "
                   .$record->LastName." ".$record->Phone."<br/>\n";
                }
                echo "<br/>Finally, delete the records:<br/><br/>\n";
                $response = $mySforceConnection->delete($ids);
                foreach ($response as $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $result->id." deleted<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }
                echo "</td><td>";
                echo "Now let's use the partner client<br/><br/>\n";

                $mySforceConnection = new SforcePartnerClient();
                $mySforceConnection->createConnection("soapclient/partner.wsdl.xml");

                if (isset($_SESSION['partnerSessionId'])) {
                    $location = $_SESSION['partnerLocation'];
                    $sessionId = $_SESSION['partnerSessionId'];

                    $mySforceConnection->setEndpoint($location);
                    $mySforceConnection->setSessionHeader($sessionId);

                    echo "Used session ID for partner<br/><br/>\n";
                } else {
                    $mySforceConnection->login(USERNAME, PASSWORD.SECURITY_TOKEN);

                    $_SESSION['partnerLocation'] = $mySforceConnection->getLocation();
                    $_SESSION['partnerSessionId'] = $mySforceConnection->getSessionId();

                    echo "Logged in with partner<br/><br/>\n";
                }

                $query = "SELECT Id, FirstName, LastName, Phone from Contact";
                $response = $mySforceConnection->query($query);

                echo "Results of query '$query'<br/><br/>\n";
                foreach ($response->records as $record) {
                    // Id is on the $record, but other fields are accessed via
                    // the fields object
                    echo $record->Id.": ".$record->fields->FirstName." "
                            .$record->fields->LastName." "
                            .$record->fields->Phone."<br/>\n";
                }

                echo "<br/>Now, create some records<br/><br/>\n";

                $records = array();

                $records[0] = new SObject();
                $records[0]->fields = array(
                    'FirstName' => 'John',
                    'LastName' => 'Smith',
                    'Phone' => '(510) 555-5555',
                    'BirthDate' => '1957-01-25'
                );
                $records[0]->type = 'Contact';

                $records[1] = new SObject();
                $records[1]->fields = array(
                    'FirstName' => 'Mary',
                    'LastName' => 'Jones',
                    'Phone' => '(510) 486-9969',
                    'BirthDate' => '1977-01-25'
                );
                $records[1]->type = 'Contact';

                $response = $mySforceConnection->create($records);

                $ids = array();
                foreach ($response as $i => $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $records[$i]->fields["FirstName"]." "
                                .$records[$i]->fields["LastName"]." "
                                .$records[$i]->fields["Phone"]
                                ." created with id ".$result->id."<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                    array_push($ids, $result->id);
                }

                echo "<br/>Retrieve the newly created records:<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->fields->FirstName." "
                            .$record->fields->LastName." "
                            .$record->fields->Phone."<br/>\n";
                }

                echo "<br/>Next, update the new records<br/><br/>\n";

                $records[0] = new SObject();
                $records[0]->Id = $ids[0];
                $records[0]->fields = array(
                    'Phone' => '(415) 555-5555',
                );
                $records[0]->type = 'Contact';

                $records[1] = new SObject();
                $records[1]->Id = $ids[0];
                $records[1]->fields = array(
                    'Phone' => '(415) 486-9969',
                );
                $records[1]->type = 'Contact';

                $response = $mySforceConnection->update($records);
                foreach ($response as $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $result->id." updated<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }

                echo "<br/>Retrieve the updated records to check the update:<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->fields->FirstName." "
                   .$record->fields->LastName." ".$record->fields->Phone."<br/>\n";
                }

                echo "<br/>Let's remove those phone numbers<br/><br/>\n";

                $records[0] = new SObject();
                $records[0]->Id = $ids[0];
                $records[0]->fieldsToNull = 'Phone';
                $records[0]->type = 'Contact';

                $records[1] = new SObject();
                $records[1]->Id = $ids[1];
                $records[1]->fieldsToNull = 'Phone';
                $records[1]->type = 'Contact';

                $response = $mySforceConnection->update($records);
                foreach ($response as $result) {
                    echo ($result->success == 1)
                           &nbsp;? $result->id." updated<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }

                echo "<br/>Retrieve the updated records again to check the update:";
                echo "<br/><br/>\n";
                $response = $mySforceConnection->retrieve('Id, FirstName, LastName, Phone',
                                'Contact', $ids);
                foreach ($response as $record) {
                    echo $record->Id.": ".$record->fields->FirstName." "
                   .$record->fields->LastName." ".$record->fields->Phone."<br/>\n";
                }

                echo "<br/>Finally, delete the records:<br/><br/>\n";
                $response = $mySforceConnection->delete($ids);
                foreach ($response as $result) {
                    echo ($result->success == 1) 
                           &nbsp;? $result->id." deleted<br/>\n"
                           &nbsp;: "Error: ".$result->errors->message."<br/>\n";
                }

                echo "</td></tr></table>";
            } catch (Exception $e) {
                echo "Exception ".$e->faultstring."<br/><br/>\n";
                echo "Last Request:<br/><br/>\n";
                echo $mySforceConnection->getLastRequestHeaders();
                echo "<br/><br/>\n";
                echo $mySforceConnection->getLastRequest();
                echo "<br/><br/>\n";
                echo "Last Response:<br/><br/>\n";
                echo $mySforceConnection->getLastResponseHeaders();
                echo "<br/><br/>\n";
                echo $mySforceConnection->getLastResponse();
            }
           &nbsp;?>
        </tt>
    </body>
</html>

