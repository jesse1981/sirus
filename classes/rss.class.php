<?php
class rss extends template {
// VARIABLES
    // channel vars
    var $channel_url;
    var $channel_title;
    var $channel_description;
    var $channel_lang;
    var $channel_copyright;
    var $channel_date;
    var $channel_creator;
    var $channel_subject;   
    // image
    var $image_url;
    // items
    var $items = array();
    var $nritems;
    
// FUNCTIONS
    // constructor
    public function main() {
        $this->nritems=0;
        $this->channel_url='';
        $this->channel_title='';
        $this->channel_description='';
        $this->channel_lang='';
        $this->channel_copyright='';
        $this->channel_date='';
        $this->channel_creator='';
        $this->channel_subject='';
        $this->image_url='';
        
        //header('Content Type: application/xml; charset=UTF-8');
        header('Content-Type: application/rss+xml; charset=ISO-8859-1');
        
        $this->setView('blank');
        $this->loadData();
        echo $this->OutputRSS();
    }   
    // set channel vars
    private function SetChannel($url, $title, $description, $lang, $copyright, $creator, $subject) {
        $this->channel_url=$url;
        $this->channel_title=$title;
        $this->channel_description=$description;
        $this->channel_lang=$lang;
        $this->channel_copyright=$copyright;
        $this->channel_date=date("Y-m-d").'T'.date("H:i:s").'+01:00';
        $this->channel_creator=$creator;
        $this->channel_subject=$subject;
    }
    // set image
    private function SetImage($url) {
        $this->image_url=$url;  
    }
    // set item
    private function SetItem($url, $title, $description, $published) {
        $this->items[$this->nritems]['url']=$url;
        $this->items[$this->nritems]['title']=$title;
        $this->items[$this->nritems]['description']=$description;
        $this->items[$this->nritems]['published']=$published;
        $this->nritems++;   
    }
    // output feed
    private function OutputRSS() {
        $output =  '<rss version="2.0">'."\n";
            $output .= '<channel>'."\n";
                $output .= '<title>'.$this->channel_title.'</title>'."\n";
                $output .= '<description>'.$this->channel_description.'</description>'."\n";
                $output .= '<language>'.$this->channel_lang.'</language>'."\n";
                $output .= '<image>'."\n";
                    $output .= '<title>'.$this->channel_subject.'</title>'."\n";
                    $output .= '<url>'.$this->image_url.'</url>'."\n";
                    $output .= '<link>'.$this->channel_url.'</link>'."\n";
                $output .= '</image>'."\n";
                $output .= '<copyright>'.$this->channel_copyright.'</copyright>'."\n";
                $output .= '<lastBuildDate>'.$this->channel_date.'</lastBuildDate>'."\n";

                for($k=0; $k<$this->nritems; $k++) {
                    $output .= '<item>'."\n";
                        $output .= '<title>'.$this->items[$k]['title'].'</title>'."\n";
                        $output .= '<link>'.$this->items[$k]['url'].'</link>'."\n";
                        $output .= '<pubDate>'.$this->items[$k]['published'].'</pubDate>'."\n";
                        $output .= '<description>'.$this->items[$k]['description'].'</description>'."\n";
                    $output .= '</item>'."\n";

                }
            $output .= "</channel>";
        $output .= "</rss>";
        return $output;
    }
    // populate variables
    private function loadData() {
        $title = $_GET["subject"];
        $creator = $_GET["creator"];
        $url = htmlentities("http://aussvap0812.agbnielsen.com.au/?module=".$_GET["object"]."&id=".$_GET["id"]);
        $this->SetChannel($url, "$title", "CS Central RSS Feed - $title.  Created by $creator", "en", "Test Copyright", $creator, "$title");
        $this->SetImage("http://aussvap0812.agbnielsen.com.au/img/NielsenTAM_logo3.gif");
        
        // load the module
        $mod = strtolower($_GET["object"]);
        require_once "classes/$mod.class.php";
        $mod = new $mod;
        $groups = $mod->getGroups();
        $fields = $mod->getFields();
        $conds = $mod->getConditions();
        $a = 0;
        $b = 0;
        $c = 0;
        
        //var_dump($fields);

        // load the database
        $db = new database;
        // get items
        $data = $db->getListData($groups, $fields, array(), $conds);
        //var_dump($data);
        // transfer to rss
        foreach ($data as $group) {
            foreach ($group as $row) {
                $title = "";
                $desc = "";
                foreach ($row as $col) {
                    
                    //echo "WORKING ON $a, $c<br/><br/>";
                    
                    switch($fields[$a][$c]->name) {
                        case "staff_id":
                            $title .= "[Created By: $col]";
                            break;
                        case "created":
                            //echo "WORKING ON $col<br/><br/>";
                            
                            
                            $datetime = explode(" ",$col);
                            $date = explode("-",$datetime[0]);
                            $time = explode(":",$datetime[1]);
                            $mktime = mktime($time[0],$time[1],$time[2],$date[1],$date[2],$date[0]);
                            
                            
                            $pubDate = date("D, d M Y H:i:s T",$mktime);
                            break;
                        case "body":
                            $desc = htmlentities($col);
                            break;
                        default:
                            //echo "Ignoring field ".$fields[$a][$c]->name."<br/>";
                    }
                    $c++;
                }
                //echo "$title - $desc<br/>";
                $this->SetItem($url, $title, $desc, $pubDate);
                $c = 0;
                $b++;
            }
            $b=0;
            $a++;
        }
    }
};