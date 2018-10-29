<?php

require __DIR__.'/bootstrap.php';

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

class SubscriberContext implements Context
{
    /**
     * @var FeatureContext
     */
    private $featureContext;

    /**
     * @param BeforeScenarioScope $scope
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        $this->featureContext = $scope->getEnvironment()->getContext('FeatureContext');
    }

    /**
     * @param string $list
     * @param array $table
     *
     * @Given /I have "(.*)" list with the following subscribers:/
     * @throws Exception
     */
    public function iHaveSubscribersForList($list, PyStringNode $stringNode)
    {
        $rows = $stringNode->getStrings();
        $db = $this->featureContext->getMysqli();
        $list = $this->iHaveList($list);
        $table = 'phplist_listuser';
        foreach($rows as $row){
            $subscriber = $this->iHaveSubscriber($row);

            $select = sprintf(
                'SELECT userid,listid from %s where userid=%s and listid=%s',
                $table,
                $subscriber['id'],
                $list['id']
            );
            $results = $db->query($select)->fetch_assoc();
            if(!isset($results['userid'])){
                // insert here
                $insert = sprintf(
                    'INSERT into %s(userid,listid,entered,modified) VALUES(%s,%s,now(),now())',
                    $table,
                    $subscriber['id'],
                    $list['id']
                );
                $db->query($insert);
                if($db->affected_rows <= 0){
                    throw new \Exception($db->error);
                }
            }
        }
    }

    public function iHaveSubscriber($email)
    {
        $table = 'phplist_user_user';
        $insert = <<<EOC
insert into ${table} (email,entered,htmlemail,confirmed,uniqid)
values("%s",now(),1,1,"%s")
EOC;
        ;
        $select = <<<EOC
select * from ${table} where email = "%s"
EOC;
        $db = $this->featureContext->getMysqli();
        $results = $db
            ->query(sprintf($select,$email))
            ->fetch_assoc()
        ;
        if(isset($results['id'])){
            return $results;
        }

        // else we add subscriber
        $uniqid = bin2hex(random_bytes(16));
        $results = $db
            ->query(sprintf($insert,$email,$uniqid))
        ;

        $results = $db
            ->query(sprintf($select,$email))
            ->fetch_assoc()
        ;
        return $results;
    }

    public function iHaveList($name, $owner = null, $description=null)
    {
        if(is_null($description)){
            $description = 'Description for '.$name;
        }
        if(is_null($owner)){
            $owner = $this->featureContext->getCurrentUser();
        }
        $db = $this->featureContext->getMysqli();
        $selectQuery = sprintf(
            'SELECT * from %s where name="%s"',
            'phplist_list',
            $name
        );

        $results = $db->query($selectQuery)->fetch_array();
        if(isset($results['id'])){
            return $results;
        }

        $query = sprintf(
            'INSERT into %s(name,description,owner,active,entered) values("%s","%s",%s,%s,%s)',
            'phplist_list',
            $name,
            $description,
            $owner['id'],
            1,
            'now()'
        );

        $results = $db->query($query);

        if($db->affected_rows <= 0 ){
            throw new \Exception($db->error);
        }

        $results = $db->query($selectQuery)->fetch_assoc();
        return $results;

    }



    /**
     * @Given /I want to send campaign with title "(.*)"/
     */
    public function iWantToSendCampaignWithTitle($title)
    {
        $db = $this->featureContext->getMysqli();
        $table = 'phplist_message';
        $select = "SELECT id from ${table} where subject='${title}'";
        $results = $db->query($select)->fetch_assoc();

        if(!isset($results['id'])){
            $uuid = UUID::generate(4);
            $insert = <<<EOC
insert into ${table}
  (subject, status, entered, sendformat, embargo, repeatuntil, owner, uuid)
  values(${title}, "draft", now(), "HTML", now(), now(), , %d, "${uuid}" )
EOC;

        }

        $link = $this->featureContext->generateUrl([
            'page' => 'send',
            'id' => $results['id'],
        ]);
        $this->featureContext->visit($link);
    }

    /**
     * @param $name
     * @Given /^I check "(.*)" as target list/
     */
    public function iCheckAsTargetList($name)
    {
        $page = $this->featureContext->getSession()->getPage();
        $element = $page->find('xpath',"//*[contains(text(),'${name}')]");
        $input = $element->find('xpath',"//input[@type='checkbox']");
        $value = $input->getAttribute('name');
        $this->featureContext->checkOption($value);
    }
}
