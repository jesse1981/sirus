<?php
// http://www.magentocommerce.com/api/rest/introduction.html
// http://www.magentocommerce.com/api/rest/Resources/resource_customers.html

// http://www.magentocommerce.com/api/soap/customer/customer_group.html
// http://www.magentocommerce.com/api/soap/customer/customer.info.html
// http://www.magentocommerce.com/api/soap/customer/customer.list.html

// http://help.sweettoothrewards.com/article/259-create-a-points-transfer-from-code
// http://help.sweettoothrewards.com/article/365-api-sample-code
// http://help.sweettoothrewards.com/article/367-api-documentation

// http://www.magentocommerce.com/api/soap/create_your_own_api.html

// http://stackoverflow.com/questions/17099455/magento-add-custom-fields-to-customer-account-tutorial-issue
// http://www.smashingmagazine.com/2012/03/01/basics-creating-magento-module/

class tvpanel_v3 {
    // Constants
    const ERR_NO_SOAP = -1;
    const ERR_NO_PANEL = -2;
    const ERR_INVALID_KEY = -3;
    
    // REST Variables
    var $callbackUrl;
    var $temporaryCredentialsRequestUrl;
    var $adminAuthorizationUrl;
    var $accessTokenRequestUrl;
    var $apiUrl;
    var $consumerKey;
    var $consumerSecret;
    
    
    // SOAP Variables
    var $TPV3Username;
    var $TPV3Password;
    var $TPV3SoapURL;
    var $TPV3Client;
    var $TPV3Session;
    var $TPV3SoapStarted;
    
    var $con;
    
    public function __construct() {
        
        $this->callbackUrl                      = "";
        $this->temporaryCredentialsRequestUrl   = TV_3_base_temporaryCredentialsRequestUrl;
        $this->adminAuthorizationUrl            = TV_3_adminAuthorizationUrl;
        $this->accessTokenRequestUrl            = TV_3_accessTokenRequestUrl;
        $this->apiUrl                           = TV_3_apiUrl;
        $this->consumerKey                      = TV_3_consumerKey;
        
        $this->TPV3Username                     = TV_3_username;
        $this->TPV3Password                     = TV_3_password;
        
        $this->TPV3SoapStarted = $this->getSoapClient();
        
        //$this->con = getOtherConnection('TVPANEL_V3_DB');
    }
    
    // private
    private function getCustomerId($panels=array()) {
        
        if ($this->TPV3SoapStarted) {
            $result = array();
            $customers = $this->TPV3Client->call($this->TPV3Session,'customer.list',array(array()));
            
            foreach ($customers as $c) {
                
                if ((isset($c["panel"])) && (in_array((int)$c["panel"],$panels))) {
                    $result[] = (int)$c["customer_id"];
                }
            }
            
            return $result;
        }
        else return false;
    }
    private function getSoapClient() {
        try {
            $this->TPV3Client = new SoapClient($this->apiUrl);
            $this->TPV3Session = $this->TPV3Client->login($this->TPV3Username,$this->consumerKey);
            
            return true;
        }
        catch (Exception $e) {
            echo ($e->getMessage());
            return false;
        }
    }
    
    // public (void)
    public function createAddress($data) {
        if (!$data) {
            $dash = new dashboard();
            $post = $dash->getPost();
            foreach ($post as $k=>$v) {
                $data[$k] = $v;
            }
        }
        if ($this->TPV3SoapStarted) {
            try {
                $result = $this->TPV3Client->call($this->TPV3Session,'customer_address.create',  array($data["customer_id"],
                                                                                            array(
                                                                                                'firstname'     => $data["firstname"], 
                                                                                                'lastname'      => $data["lastname"], 
                                                                                                'country_id'    => 1, 
                                                                                                'city'          => $data["city"], 
                                                                                                'street'        => $data["street"], 
                                                                                                'telephone'     => $data["telephone"],
                                                                                                'postcode'      => $data["postcode"],
                                                                                                
                                                                                                "is_default_billing"  => true,
                                                                                                "is_default_shipping" => true
                                                                                            )
                                                                                        )
                                                                                    );
                if (php_sapi_name()!="cli") echo json_encode ($result);
                else return $result;
            }
            catch (Exception $e) {
                echo "Error: ".$e->getMessage()."\n";
                return false;
            }
        }
        else {
            if (php_sapi_name()!="cli") echo ERR_NO_SOAP;
            return ERR_NO_SOAP; // SOAP failed to start
        }
    }
    public function createPanel($data=array()) {
        if (!$data) {
            $dash = new dashboard();
            $post = $dash->getPost();
            foreach ($post as $k=>$v) {
                $data[$k] = $v;
            }
        }
        
        if ($this->TPV3SoapStarted) {
            try {
                $result = $this->TPV3Client->call($this->TPV3Session,'customer.create',  array(
                                                                                            array(
                                                                                                'email'         => $data["email"], 
                                                                                                'prefix'        => $data["prefix"], 
                                                                                                'firstname'     => $data["firstname"], 
                                                                                                'lastname'      => $data["lastname"], 
                                                                                                'password'      => $data["password"], 
                                                                                                'panel'         => $data["panel"], 
                                                                                                //'dob'           => $data["dob"],
                                                                                                'website_id'    => 1, 
                                                                                                'store_id'      => 1, 
                                                                                                'group_id'      => 1
                                                                                            )
                                                                                        )
                                                                                    );
                if (php_sapi_name()!="cli") echo json_encode ($result);
                else return $result;
            }
            catch (Exception $e) {
                echo "Error: ".$e->getMessage()."\n";
                return false;
            }
        }
        else {
            if (php_sapi_name()!="cli") echo ERR_NO_SOAP;
            else return ERR_NO_SOAP; // SOAP failed to start
        }
    }
    public function importPanels() {
        $user_keys = array("prefix","firstname","lastname","email","panel","password");
        $addr_keys = array("country_id","city","telephone","postcode","firstname","lastname");
        
        $db  = getOtherConnection('ELDORADO');
        $sql = "SELECT  title as prefix,
                        firstname,
                        name as lastname,
                        email,
                        panelno as panel,
                        'agbnmr123' as password,
                        
                        'Australia' as country_id,
                        c.city,
                        suburb,
                        address1,
                        address2,
                        phoneno as telephone,
                        postcode
                FROM householder h
                INNER JOIN region r on h.regionid = r.regionid
                INNER JOIN city c ON r.cityid = c.cityid
                WHERE lastcallstatus = 2";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        foreach ($tab as $item) {
            
            echo "Importing Panel: ".$item["panel"]."...\n";
            
            // Create Customer
            $params = array();
            foreach ($user_keys as $u) {
                $params[$u] = $item[$u];
            }
            $customer_id = $this->createPanel($params);
            
            // Create Address for Customer
            $params = array();
            foreach ($addr_keys as $a) {
                $params[$a] = $item[$a];
            }
            
            $street = (strlen(trim($item["address1"]))>=1) ? $item["address1"].", ":"";
            $street .= $item["address2"];
            $params["street"] = array($street,$item["suburb"]);
            
            $address_id = $this->createAddress($customer_id,$params);
        }
    }
    public function importPoints() {
        $db = getOtherConnection('ELDORADO');
        // Credits
        echo "Getting Credits...\n";
        $sql = "SELECT  h.panelno as panel,
                        pts.interactiontype as description,
                        p.*
                FROM pointstransaction p
                INNER JOIN points pts ON p.interactiontypeID = pts.interactiontypeID
                INNER JOIN householder h ON p.hhnum = h.hhnum
                WHERE h.lastcallstatus = 2";
        $res = $db->query($sql);
        $credits = $db->getTable($res);
        
        // Debits
        echo "Getting Debits...\n";
        $sql = "SELECT  h.panelno as panel,
                        c.description,
                        p.*
                FROM pointsredeemed p
                INNER JOIN catalogue c ON c.catalogueno = p.catalogueno
                INNER JOIN householder h ON p.hhnum = h.hhnum
                WHERE h.lastcallstatus = 2";
        $res = $db->query($sql);
        $debits = $db->getTable($res);
        
        // Insert Credits
        echo "Applying Credits (".count($credits).")...\n";
        foreach ($credits as $c) {
            echo ".";
            $panel      = $c["panel"];
            $points     = $c["pointsearned"];
            $comment    = $c["description"];
            $this->transfer($panel, $points, $comment);
        }
        echo "\n";
        
        // Insert Debits
        echo "Applying Debits (".count($debits).")...\n";
        foreach ($debits as $d) {
            echo ".";
            $panel      = $c["panel"];
            $points     = ((int)$c["pointsvalue"] * -1);
            $comment    = $c["description"];
            $this->transfer($panel, $points, $comment);
        }
    }
    public function transfer($panel=0,$points=0,$comment="") {
        if (!$panel) {
            $dash = new dashboard();
            $post = $dash->getPost();
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
        }
        
        if ($this->TPV3SoapStarted) {
            $customer = $this->getCustomerId($panel);
            
            if ($customer) {
                foreach ($customer as $c) {
                    //echo "Adding $points to $c";
                    $transfer = $this->TPV3Client->call($this->TPV3Session,'rewards.maketransfer',array($c,1,$points,$comment));
                    if (php_sapi_name()!="cli") echo "0";
                    else return 0;
                }
            }
            else {
                if (php_sapi_name()!="cli") echo ERR_NO_PANEL;
                else return ERR_NO_PANEL; // Panel number not found
            }
        }
        else {
            if (php_sapi_name()!="cli") echo ERR_NO_SOAP;
            else return ERR_NO_SOAP; // SOAP failed to start
        }
    }
    public function updateCustomer($panel,$data) {
        $valid_keys = array("email","firstname","lastname","password","group_id","prefix","suffix","gender","middlename");
        foreach ($data as $k=>$v) {
            if (!in_array($k, $valid_keys)) {
                return ERR_INVALID_KEY;
            }
        }
        
        $id = $this->getCustomerId($panels);
        $this->TPV3Client->__soapCall('customer.update',array($id,$data));
    }
    
    // Get's
    public function getBalance($panel) {
        
        if ($this->TPV3SoapStarted) {
            $id = $this->getCustomerId($panel);
            
            if ($id) {
                $balance = $this->TPV3Client->call($this->TPV3Session,'rewards.getbalancebyid',array($id[0]));
                if (php_sapi_name()!="cli") echo $balance[1];
                else return (int)$balance[1];
            }
            else return ERR_NO_PANEL; // Panel number not found or no customers attached
        }
        else return ERR_NO_SOAP; // SOAP failed to start
    }
    public function getCustomer($id) {
        if ($this->TPV3SoapStarted) {
            $result = array();
            $customer = $this->TPV3Client->call($this->TPV3Session,'customer.info',$id);
            return $customer;
        }
    }
    public function getPanels($panels=array()) {
        $res = array();
        if (!is_array($panels)) $panels = array($panels);
        $ids = $this->getCustomerId($panels);
        foreach ($ids as $i) {
            $res[] = $this->getCustomer($i);
        }
        if (php_sapi_name()!="cli") echo json_encode ($res);
        else return $res;
    }

    // Mailchimp
    public function getMCcampaigns() {
        $mamc = new Mailchimp();
        $camp = $mamc->campaigns->getList();
        
        if (php_sapi_name()!="cli") echo json_encode($camp);
        else return $camp;
    }
    public function getMCLists() {
        $mc = new Mailchimp();
        $result = $mc->lists->getList();
        
        if (php_sapi_name()!="cli") echo json_encode($result);
        else return $result;
    }
    public function getMCListMembers($list_id) {
        $mamc = new Mailchimp();
        $res = $mamc->lists->members($list_id);
        
        if (php_sapi_name()!="cli") echo json_encode($res);
        else return $res;
    }
    public function MCSubscribe($data=array()) {
        if (!$data) {
            $dash = new dashboard();
            $post = $dash->getPost();
            foreach ($post as $k=>$v) {
                $data[$k] = $v;
            }
            if (!is_array($data["panels"])) $data["panels"] = json_decode($data["panels"]);
        }
        $mamc = new Mailchimp();
        
        $customers = $this->getPanels($data["panels"]);
        foreach ($customers as $c) {
            $sub = $mamc->lists->subscribe($data["list_id"], array("email"=>$c["email"]), array("PANELID"=>$c["panel"],"FNAME"=>$c["firstname"],"LNAME"=>$c["lastname"]), "html", false, true);
        }
    }
}
?>