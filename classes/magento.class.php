<?php
class magento {
    // Based on tutorials: 
    // http://code.tutsplus.com/tutorials/magento-custom-module-development--cms-20643
    
    var $base_dir;
    var $dir_app;
    var $dir_app_local;
    var $dir_app_etc;
    var $dir_app_etc_mods;
    
    var $ssh;
    
    // construct
    public function __construct() {
        $this->base_dir = MAGENTO_BASEDIR;
        $this->ssh = new ssh(MAGENTO_SERVER, MAGENTO_USER, MAGENTO_PASS);
        
        $this->dir_app          = $this->base_dir."app/";
        $this->dir_app_local    = $this->dir_app."code/local/";
        $this->dir_app_etc      = $this->dir_app."etc/";
        $this->dir_app_etc_mods = $this->dir_app_etc."modules/";
    }
    
    public function module_create($ns,$name) {
        // *****************************************************************
        // Create Directories
        $local_ns_dir = $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/");
        if ($local_ns_dir) {
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/Block/");
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/controllers/");
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/etc/");
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/Helper/");
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/Model/");
            $this->ssh->exec("makedm -p ".$this->dir_app_local."$ns/$name/sql/");
        }
        else return -1;
        // *****************************************************************
        // Activate Module
        $source = "/tmp/module.xml";
        $handle = fopen($source,'w');
        if ($handle) {
            fwrite($handle,'<?xml version="1.0"?>
                            <config>
                                <modules>
                                    <'.$ns.'_'.$name.'>
                                    <active>true</active>
                                    <codePool>local</codePool>
                                    </'.$ns.'_'.$name.'>
                                </modules>
                            </config>');
            
            fclose($handle);
            // upload
            $this->ssh->uploadFile($source, $this->dir_app_etc_mods."/$ns"."_"."$name.xml");
            unlink($source);
        }
        else return -2;
        // *****************************************************************
        // Create Configuration File
        $source = "/tmp/config.xml";
        $handle = fopen($source,'w');
        if ($handle) {
            fwrite($handle,'<?xml version="1.0"?>
                            <config>
                                <modules>
                                    <'.$ns.'_'.$name.'>
                                        <version>0.1.0</version>    <!-- Version number of your module -->
                                    </'.$ns.'_'.$name.'>
                                </modules>
                                <global>
                                    <models>
                                        <'.$ns.'>
                                            <class>'.$ns.'_'.$name.'_Model</class>
                                            <resourceModel>'.$ns.'_resource</resourceModel>
                                        </'.$ns.'>
                                        <'.$ns.'_resource>
                                            <class>'.$ns.'_'.$name.'_Model_Resource</class>
                                            <entities>
                                                <'.$name.'>
                                                    <table>[table_name_in_database]</table>
                                                </'.$name.' >
                                            </entities>
                                        </'.$ns.'_resource>
                                    </models>
                                    <helper>
                                        <'.$ns.'_'.$name.'>
                                            <class>'.$ns.'_'.$name.'_Helper</class>
                                        </'.$ns.'_'.$name.'>
                                    </helper>
                                </global>
                                <frontend>
                                    <routers>
                                        <'.$name.'>
                                            <use>standard</use>
                                            <args>
                                                <module>'.$ns.'_'.$name.'</module>
                                                <frontName>'.$name.'</frontName>
                                            </args>
                                        </'.$name.'>
                                    </routers>
                                </frontend>
                            </config>');
            fclose($handle);
            // upload
            $this->ssh->uploadFile($source, $this->dir_app_local."$ns/$name/etc/config.xml");
            unlink($source);
        }
        else return -3;
        // *****************************************************************
        // Create the Controller
        $source = "/tmp/controller.php";
        $handle = fopen($source,'w');
        if ($handle) {
            fwrite($handle,'<?php
                            class '.$ns.'_'.$name.'_IndexController extends Mage_Core_Controller_Front_Action
                            {
                                public function indexAction()
                                {
                                    echo "Module installed successfully.";
                                }
                            }?>');
            fclose($handle);
            // upload
            $this->ssh->uploadFile($source, $this->dir_app_local."$ns/$name/controllers/IndexController.php");
            unlink($source);
        }
        else return -4;
        // *****************************************************************
        // Create the Resource
        $source = "/tmp/$name.php";
        $handle = fopen($source,'w');
        if ($handle) {
            fwrite($handle,'<?php
                            class Frontname_'.$ns.'_Model_'.$name.' extends Mage_Core_Model_Abstract
                            {
                                protected function _construct()
                                {
                                    '.'$this->_init('."'".$ns.'/'.$name."'".');
                                }
                            }?>');
            fclose($handle);
            // upload
            $this->ssh->uploadFile($source, $this->dir_app_local."$ns/$name/Model/$name.php");
        }
        else return -5;
        // *****************************************************************
        // Create the Collection
        $source = "/tmp/Collection.php";
        $handle = fopen($source,'w');
        if ($handle) {
            fwrite($handle,'<?php
                            class '.$ns.'_Model_Resource_'.$name.'_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
                                protected function _construct()
                                {
                                    '.'$this->_init('."'".$ns.'/'.$name."'".');
                                }
                            }?>');
            fclose($handle);
            // upload
            $this->ssh->uploadFile($source, $this->dir_app_local."$ns/$name/Model/Resource/Collection.php");
        }
        else return -5;
        // Done:
        return true;
    }
}
?>