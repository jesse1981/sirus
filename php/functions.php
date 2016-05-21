<?php
// int $errno , string $errstr [, string $errfile [, int $errline [, array $errcontext ]]]
function amLoggedIn() {
    $sess = new session();
    $id = $sess->getKey('user_id');
    if ((int)$id) return true;
    else return false;
}
// ======================================================
function error_handler($errno,$errstr,$errfile,$errline,$errcontext) { 
    $errorstr = "Error number: $errno\n".
                "Error string: $errstr\n".
                "Error file: $errfile\n".
                "Error line: $errline\n".
                "Context: $errcontext";
    log_error($errfile, "error_handler", $errorstr);
}
function exception_handler($exception) {
    log_error('exception_handler', 'unknown', $exception->getMessage());
}
function log_error($module,$function,$errorstr) {
    $db = new database();
    $sql = "INSERT INTO error_log(`browser`,`ip`,`date`,`time`,`module`,`function`,`errmsg`) VALUES(
        '".CLIENT_BROWSER."',
        '".CLIENT_IP."',
        '".EXECUTED_DATE."',
        '".EXECUTED_TIME."',
        '$module',
        '$function',
        '$errorstr'
    )";
}
// ======================================================
function boolToString($val) {
    if ($val) return "true";
    else return "false";
}
function caseFirst($str) {
    $ret = strtoupper(substr($str, 0, 1)).substr($str, 1);
    return $ret;
}
function cleanXMLTags($data) {
    $xml = DOMDocument::loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
    return strip_tags($xml->saveXML());
}
function convertWindowsEOL($filename) {
    $handle = fopen($filename,'r+');
    if ($handle) {
        $buffer = fread($handle,filesize($filename));
        // first clean any \r\n with \n
        $buffer = str_replace("\r\n", "\n", $buffer);
        // then convert the \n back to \r\n
        $buffer = str_replace("\n", "\r\n", $buffer);
        // go to the beginning of the file
        if (fseek($handle,0)) return false; // failed to seek to beginning
        fwrite($handle,$buffer);
        fclose($handle);
    }
    else return false;
}
function csvToArray($filename,$newline="\r\n",$delim=",") {
    $result = array();
    $line = 0;
    
    $handle = fopen($filename,'r');
    if ($handle) {
        $buffer = fread($handle,filesize($filename));
        $lines = explode($newline,$buffer);
        foreach ($lines as $l) {
            $cols = explode($delim,$l);
            $field = 0;
            foreach ($cols as $c) {
                $result[$line][$field] = $c;
                $field++;
            }
            $line++;
        }
        fclose($handle);
    }
    return $result;
}
function getPostValues($arr) {
    $res  = array();
    $dash = new dashboard();
    $post = $dash->getPost();
    foreach ($arr as $a) {
        $res[$a] = (isset($post[$a])) ? $post[$a]:false;
    }
    return $res;
}
function cleanRes($tab,$char) {
    for ($i=0;$i<count($tab);$i++) {
        foreach ($tab[$i] as $k=>$v) {
            $tab[$i][$k] = str_replace($char, "", $tab[$i][$k]);
        }
    }
    return $tab;
}
// ======================================================
function mysqlToUnix($time,$datetime=true) {
    $mkdate = array(0,0,0);
    $mktime = array(0,0,0);
    if ($datetime) {
        $arrDatetime = explode(" ",$time);
        $mkdate = explode("-", $arrDatetime[0]);
        $mktime = explode(":", $arrDatetime[1]);
    }
    else $mkdate = explode ("-",$time);
    return mktime($mktime[0],$mktime[1],$mktime[2],$mkdate[1],$mkdate[2],$mkdate[0]);
}
function unixToMysql($time,$datetime=true) {
    if ($datetime) return date('Y-m-d H:i:s',$time);
    else return date('Y-m-d',$time);
}
function calcTimeRemaining($i,$x) {
    // $i = iterations/sec
    // $x = remaining iterations
    
    if (!$i) $i=1;
    $now = new DateTime();
    $sec = ($x/$i);
    $end = new DateTime(date('Y-m-d H:i:s',(time()+$sec)));
    $int = $end->diff($now);
    
    return $int->format("%hHr %iM %sS");
}
// ======================================================
function fileToVar($tmpName) {
    set_time_limit(90);
    // read
    $handle = fopen($tmpName, 'r');
    $value = fread($handle, filesize($tmpName));
    //$value="";
    //while ($buffer = mysql_real_escape_string(fread($handle, 50000))){$value .= $buffer;}
    fclose($handle);
    // done
    $value = mysql_real_escape_string($value);
    return $value;
}
function fileToHash($tmpName) {
    $value = md5_file($tmpName);
    return $value;
}
// ======================================================
function http_digest_parse($txt) {
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}
function directoryToArray($directory, $recursive, $filesOnly = false) {
    $array_items = array();
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if (($file != "." && $file != "..") && 
                (is_dir($directory. DIR_SEP . $file)) && 
                ($recursive)) $array_items = array_merge($array_items, 
                                                        directoryToArray($directory. DIR_SEP . $file, $recursive));
            if ((!$filesOnly) || ($filesOnly && !is_dir($directory. DIR_SEP . $file))) {
                    $file = $directory . DIR_SEP . $file;
                    $array_items[] = preg_replace("/\/\//si", DIR_SEP, $file);
            }
        }
        closedir($handle);
    }
    return $array_items;
}
function valIntIfNumeric($val) {
    if (is_numeric($val)) return (int)$val;
    else return $val;
}
// ======================================================
function renderFlexGrid($objects,$fields) {
    $rows = array();
    foreach ($objects as $d) {
        $row = array();
        $row[] = $d->id;
        foreach ($fields as $f) {
            if (!is_object($d->fields[$f])) $row[] = ((isset($d->fields[$f])) && ($d->fields[$f]!="")) ? $d->fields[$f]:"";
            else if ((is_object($d->fields[$f])) && 
                    (count($d->fields[$f]->objects)==1)) {
                $joined = new object($d->fields[$f]->name);
                $row[] = ($joined->select) ? $d->fields[$f]->objects[0]->fields[$joined->select]:"";
            }
            else $row[]="";
        }
        $rows[] = array("id"=>$d->id,"cell"=>$row);
    }
    $result = array("page"=>1,"total"=>count($objects),"rows"=>$rows);
    return $result;
}
function renderFlexCols($object) {
    $obj = new object($object);
    $res = array();
    $res[] = array("name"=>"id","display"=>"id","sortable"=>true,"IsObject"=>false,"ObjItems"=>array());
    foreach ($obj->fields as $k=>$v) {
        $IsObject = (is_object($v)) ? true:false;
        $ObjItems = array();
        if ($IsObject) {
            $selectObject = new object($v->name);
            $selectFields = explode(",",$selectObject->select);
            $selectItems = $selectObject->getAllItems($selectObject->name);
            foreach ($selectItems as $item) {
                $itemValue = "";
                foreach ($selectFields as $field) {
                    $itemValue .= ($itemValue) ? " ":"";
                    $itemValue .= $item->fields[$field];
                }
                $ObjItems[] = array("id"=>$item->id,"value"=>$itemValue);
            }
        }
        $res[] = array("name"=>$k,"display"=>$k,"sortable"=>true,"IsObject"=>$IsObject,"ObjItems"=>$ObjItems);
    }
    return $res;
}
// ======================================================
function convUnixDate($date) {
    //echo "$date\n";
    $date = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6);
    return $date;
}
// ======================================================
// Array, Serialization, XLS and CSV Functions
// ======================================================
function consumeCSV($filename,$delim=",") {
    $result = array();
    $handle = fopen($filename,'r');
    if ($handle) {
        $buffer = fread($handle, filesize($filename));
        fclose($handle);
        
        $lines = explode("\r\n",$buffer);
        $buffer = null;
        foreach ($lines as $row) {
            $cols = explode($delim,$row);
            $col_data = array();
            foreach ($cols as $col) {
                $col_data[] = $col;
            }
            $result[] = $col_data;
        }
        return $result;
    }
    else return false;
}
function serializeArray($arr,$delim=",") {
    $return = "";
    foreach ($arr as $a) {
        if ($return) $return .= $delim;
        $return .= $a;
    }
    return $return;
}
function serializeDbField($array,$field,$delim=",") {
    $result = "";
    $count = 0;
    foreach ($array as $item) {
        if (isset($item[$field])) {
            if ($count) $result .= $delim;
            $result .= $item[$field];
            $count++;
        }
    }
    return $result;
}
function exportResToCSV($filename,$data,$delimiter="|") {
    $handle     = fopen($filename,'w');
    $count      = 0;
    if ($handle) {
        foreach($data[0] as $k=>$v) {
            if ($count) fwrite ($handle, $delimiter);
            fwrite($handle,$k);
            $count++;
        }
        foreach($data as $row) {
            fwrite($handle,"\r\n");
            $count = 0;
            foreach($row as $col) {
                if ($count) fwrite($handle,$delimiter);
                fwrite($handle,$col);
                $count++;
            }
        }
        fclose($handle);
    }
}
function exportResToXLS($filename,$data,$header=0) {
    $xls    = new Spreadsheet_Excel_Writer("$filename");
    $sheet  = $xls->addWorksheet();
    $col    = -1;
    $row    = $header;
    foreach ($data[$header] as $k=>$v) {
        $col++;
        $sheet->write($row, $col, $k);
    }
    foreach($data as $item) {
        $row++;
        $col = -1;
        foreach ($item as $k=>$v) {
            $col++;
            $sheet->write($row, $col, $v);
        }
    }
    $xls->close();
}
function getArrayIndex($array,$field,$value) {
    if (is_array($array)) {
        for ($i=0;$i<count($array);$i++) {
            if ($array[$i][$field]==$value) {
                return $i;
                break;
            }
        }
        return false;
    }
    else return false;
}
function importXlsToArray($filename,$sheet=0) {
    $res = array();
    $xls = new Spreadsheet_Excel_Reader($filename);
    $sheet = $xls->sheets[$sheet];
    $rows = $sheet['cells'];
    $rowCount = count($rows);
    
    foreach($rows as $row) {
        $row_data = array();
        foreach ($row as $col) {
            $row_data[] = $col;
        }
        $res[] = $row_data;
    }
    
    return $res;
}
function sortArray($data,$field,$datatype="text",$direction="desc") {
    $cycle = false;
    for ($i=0;$i<(count($data)-1);$i++) {
        switch($datatype) {
            case "date":
                $arrDate1 = explode("-", $data[$i][$field]);
                $mkDate1 = mktime(1,1,1,$arrDate1[1],$arrDate1[2],$arrDate1[0]);
                $arrDate2 = explode("-", $data[$i+1][$field]);
                $mkDate2 = mktime(1,1,1,$arrDate2[1],$arrDate2[2],$arrDate2[0]);
              
                $cycle = (($direction=="desc") && ($mkDate1<$mkDate2)) ? true:false;
                if (!$cycle) {
                    $cycle = (($direction=="asce") && ($mkDate1>$mkDate2)) ? true:false;
                }
                break;
            case "text":
                $cycle = (($direction=="desc") && (strcmp($data[$i][$field], $data[$i+1][$field])<0)) ? true:false;
                if (!$cycle) {
                    $cycle = (($direction=="asce") && (strcmp($data[$i][$field], $data[$i+1][$field])>0)) ? true:false;
                }
                break;
            case "number":
                $cycle = (($direction=="desc") && ((int)$data[$i][$field]<(int)$data[$i+1][$field])) ? true:false;
                if (!$cycle) {
                    $cycle = (($direction=="asce") && ((int)$data[$i][$field]>(int)$data[$i+1][$field])) ? true:false;
                }
               break;
           default:
               // end
               break;
        
        }
        if ($cycle) {
            //echo "Swapping for index: $i\n";
            $tempArray = $data[$i+1];
            $data[$i+1] = $data[$i];
            $data[$i] = $tempArray;
                        
            $data = sortArray($data,$field,$datatype,$direction);
        }
    }
    return $data;
}
function unset_index($arr,$index) {
    $return = array();
    for ($i =0;$i<count($arr);$i++) {
        if ($i!=$index) $return[] = $arr[$i];
    }
    return $return;
}
// ======================================================
function vCsvLookup($filename,$val,$returnCol,$exact=true,$delimiter=",") {
    $fileArray = pathinfo($filename);
    $file_ext  = $fileArray['extension'];
    
    switch ($file_ext) {
        case "csv":
            $handle = fopen($filename,'r');
            if ($handle) {
                $buffer = fread($handle,filesize($filename));
                fclose($handle);
                $lines = explode("\n", $buffer);
                foreach($lines as $row) {
                    $cols = explode($delimiter,$row);
                    foreach ($cols as $item) {
                        if (($exact) && ($item==$val)) { return $cols[((int)$returnCol-1)]; break; }
                        elseif ((!$exact) && (strpos($val, $item)!==false)) { return $cols[((int)$returnCol-1)]; break; }
                    }
                }
                return "N/A";
            }
            else return false;
            break;
        case "xls":
            $handle = new Spreadsheet_Excel_Reader($filename);
            
            $rows = $handle->rowcount();
            $cols = $handle->colcount();
            
            for ($x=1;$x<=$rows;$x++) {
                for ($y=1;$y<=$cols;$y++) {
                    $item = $handle->val($x, $y);
                    if (($exact) && ($item==$val)) { return $handle->val($x, $returnCol); break; }
                    elseif ((!$exact) && (strpos($val, $item)!==false)) { return $handle->val($x, $returnCol); break; }
                }
            }
            break;
    }
}
// ======================================================
function getPageSource($url,$postdata=array(),$username="") {
    $field_string = "";
    if ($postdata) {
        foreach ($postdata as $k=>$v) {
            if ($field_string) $field_string .= "&";
            $v2 = urlencode($v);
            $field_string .= "$k=$v2";
        }
    }
    
    
    
    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // Check for HTTP authentication method
    if (($username) && (!isset($_SERVER['PHP_AUTH_USER']))) {
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        header('HTTP/1.0 401 Unauthorized');
        die('Authentication Required');
        exit;
    } else if ($username) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
        curl_setopt($ch, CURLOPT_USERPWD, $_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW']);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE);

    }
    
    if ($postdata) {
        curl_setopt($ch,CURLOPT_POST, count($postdata));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $field_string);
        
    }
    
    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);
    
    return $result;
}
function getPageFields($source) {
    $res    = array();
    $xml    = @simplexml_import_dom(DOMDocument::loadHTML($source));
    $xpath  = $xml->xpath("//html//form//input");

    foreach ($xpath as $x) {

        $res[(string)$x->attributes()->name] = (string)$x->attributes()->value;
    }
    
    $xpath  = $xml->xpath("//html//form//select");

    foreach ($xpath as $x) {
        $res[(string)$x->attributes()->name] = (string)$x->attributes()->value;
    }
    
    $xpath  = $xml->xpath("//html//form//textarea");

    foreach ($xpath as $x) {
        $res[(string)$x->attributes()->name] = (string)$x;
    }
    
    return $res;
}
// ======================================================
// Sharepoint Functions
// ======================================================
function spLoadMainHolder($url) {
    $sess = new session();
    $user = $sess->getKey('username');
    $html = getPageSource($url,array(),$user);
    
    $remove = array("suitebar","ms-hctest","s4-ribbonrow","s4-titlerow","sideNavBox");
    
    $xml    = @simplexml_import_dom(DOMDocument::loadHTML($html));
    
    // download all (jQuery) scripts
    $js     = $xml->xpath("//script");
    $script  = "";
    $data   = "";
    foreach ($js as $c) {
        if (strpos($c->attributes()->src,"jquery")) {
            if (substr($c->attributes()->src,0,4)=="http") {
                $data = getPageSource($c->attributes()->src,array(),$user);
            }
            else {
                $data = getPageSource("../".$c->attributes()->src,array(),$user);
            }

            if ($data) $script .= $data;
            }
        unset ($c[0]);
    }
    //$xml->head->addChild('script',$script);

    // replace images with the base64 encoding
    $img    = $xml->xpath("//img");

    foreach ($img as $i) {
        $b = $i->attributes()->src;
        $type = pathinfo($b, PATHINFO_EXTENSION);

        
        $data = getPageSource("http://sharepoint.agbnielsen.com.au".$b,array(),$user);

        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        $i->attributes()->src = $base64;
    }

    // download all external stylesheets
    $css    = $xml->xpath("//link");
    $style  = "";
    $data   = "";
    foreach ($css as $c) {
        if (substr($c->attributes()->href,0,4)=="http") {
            $data = getPageSource($c->attributes()->href,array(),$user);
        }
        else {
            $data = getPageSource("http://sharepoint.agbnielsen.com.au".$c->attributes()->href,array(),$user);
        }

        if ($data) $style .= $data;
        unset ($c[0]);
    }
    $xml->head->addChild('style',$style);
    
    // Delete all unwanted DIV's
    foreach ($remove as $r) {
        $div = $xml->xpath("//div[id='$r']");
        //if (count($div)) unset($div[0][0]);
    }
    
    // fix style to contentBox
    $content = $xml->xpath("//div[id='contentBox']");
    //$content[0]->attributes()->style .= "margin:0;";
    

    // save back out to html
    $buffer     = $xml->asXML();
    return $buffer;
}
// ======================================================
// Form / Database Helpers
// ======================================================
function storePostToTable($table) {
	$data = new database();
	$dash = new dashboard();
	$post = $dash->getPost();
	$result = false;

	if (count($post)) {
		$definitions = $data->getFieldDefinitions($table);
		$fields = array();
		$values = array();
		$columns = explode(",",serializeDbField($definitions,"column_name"));
		$checks = (in_array("active",$columns)) ? array("active"):array();
		$id = 0;
		$password = "";
		
		foreach ($post as $k=>$v) {
			if ($k=="id") {
				$id = $v;
			}
			else {
				if ($k=="password") $password = $v;
				foreach ($definitions as $d) {
					if ($d["column_name"]==$k) {
						$fields[] = $k;
						$v = ($v!="on") ? $v:"T";
						$values[] = ($d["data_type"]=="int") ? $v:"'$v'";
					}
				}
			}
			
		}
		foreach ($checks as $c) {
			if (!in_array($c,$fields)) {
				$fields[] = $c;
				$values[] = "'F'";
			}
		}
		if ((int)$id) {
			$sql = "UPDATE $table SET ";
			for ($i=0;$i<count($fields);$i++) {
				if ($i) $sql .= ",";
				if ($fields[$i]=="password") $password = $values[$i];
				$sql .= $fields[$i]." = ".$values[$i];
			}
			$sql .= " WHERE id = $id";
		}
		else $sql = "INSERT INTO $table (".serializeArray($fields).") VALUES (".serializeArray($values).")";
		$res = $data->query($sql);
		
		if (!(int)$id) $id = $data->getLastId();
		
		// need to fix the password?
		if ($table == "users") {
			$salt = "This is CookieJar! $id";
                        $password = trim($password,"'");
			$hash = md5($salt . $password);
                        
			$sql = "UPDATE users SET password = '$hash' WHERE id = $id";
                        $res = $data->query($sql);
		}
		
		if ($res) $result = true;
	}
	return $result;
}
function getTableVars($table) {
	$data = new database();
	$values = array();
	if (ACTION=="add") {
		$sql = "SELECT * FROM $table WHERE id = ".ID;
		$res = $data->query($sql);
		$tab = $data->getTable($res);
		if (isset($tab[0])) {
			foreach ($tab[0] as $k=>$v) {
				$values[$k] = $v;
			}
		}
	}
	else {
		$fields = $data->getFieldDefinitions($table);
		foreach ($fields as $f) {
			$values[$f["column_name"]] = "";
		}
	}
	return $values;
}
// ======================================================
// DOCX Functions
// ======================================================
function docxInsertHtml($html,$filename="",$options=array(),$save=true,$docx="",$h2d="",$dom="",$writer="") {
    if (!$h2d)      $h2d    = new html2docx();
    if (!$dom)      $dom    = new simple_html_dom();
    if (!$docx)     $docx   = new \PhpOffice\PhpWord\PhpWord();
    if (!$writer)   $writer = PhpOffice\PhpWord\IOFactory::createWriter($docx, 'Word2007');
    
    $section = $docx->createSection();
    $dom->load($html);
    $html_dom_array = $dom->find('html',0)->children();
    
    $paths = $h2d->htmltodocx_paths();
    
    // Settings
    $initial_state = array(
        // Required parameters:
        'phpword_object' => &$docx, // Must be passed by reference.
        //
        // 'base_root' => 'http://test.local', // Required for link elements - change it to your domain.
        // 'base_path' => '/htmltodocx/documentation/', // Path from base_root to whatever url your links are relative to.

        'base_root' => $paths['base_root'],
        'base_path' => $paths['base_path'],

        // Optional parameters - showing the defaults if you don't set anything:
        'current_style' => array('size' => '11'), // The PHPWord style on the top element - may be inherited by descendent elements.
        'parents' => array(0 => 'body'), // Our parent is body.
        'list_depth' => 0, // This is the current depth of any current list.
        'context' => 'section', // Possible values - section, footer or header.
        'pseudo_list' => TRUE, // NOTE: Word lists not yet supported (TRUE is the only option at present).
        'pseudo_list_indicator_font_name' => 'Wingdings', // Bullet indicator font.
        'pseudo_list_indicator_font_size' => '7', // Bullet indicator size.
        'pseudo_list_indicator_character' => 'l ', // Gives a circle bullet point with wingdings.
        'table_allowed' => TRUE, // Note, if you are adding this html into a PHPWord table you should set this to FALSE: tables cannot be nested in PHPWord.
        'treat_div_as_paragraph' => TRUE, // If set to TRUE, each new div will trigger a new line in the Word document.

        // Optional - no default:    
        'style_sheet' => $h2d->htmltodocx_styles_example(), // This is an array (the "style sheet") - returned by htmltodocx_styles_example() here (in styles.inc) - see this function for an example of how to construct this array.
    );
    
    // Extend Options
    foreach ($options as $k=>$v) {
        $initial_state[$k] = $v;
    }
    
    // Convert the HTML and put it into the PHPWord object
    $h2d->htmltodocx_insert_html($section, $html_dom_array[0]->nodes, $initial_state);

    // Clear the HTML dom object:
    $dom->clear(); 
    unset($dom);

    // Save File
    if (($save) && (!$filename)) return false;
    if (($save) && (file_exists($filename))) {
        $h2d_file_uri = tempnam('', 'htd');
        $writer->save($h2d_file_uri);

        $copied = copy($h2d_file_uri, $filename);
        if ($copied) {
            unlink($h2d_file_uri);
            return true;
        }
    }
    else return $docx;
}
function docxUpdateTemplate($key,$value,$filename="",$processor="") {
    if ((!$processor) && ($filename) && (file_exists($filename))) $processor = new PhpOffice\PhpWord\TemplateProcessor($filename);
    else if (!$processor) return false;
    
    echo "Setting $key to $value in $filename\n";
    
    $processor->setValue($key, $value);
    return $processor;
}
function docxGetObject() {
    $docx = new \PhpOffice\PhpWord\PhpWord();
    return $docx;
}
function docxGetWriter($docx=false) {
    if (!$docx)     $docx   = new \PhpOffice\PhpWord\PhpWord();
    $writer = PhpOffice\PhpWord\IOFactory::createWriter($docx, 'Word2007');
    return $writer;
}
function docxGetNewSection($docx) {
    if (!$docx)     $docx   = new \PhpOffice\PhpWord\PhpWord();
    $section = $docx->createSection();
    return $section;
}
function docxSaveTemplate($processor="",$filenameIn="",$filenameOut="",$save=true) {
    if ((!$processor) && ($filenameIn) && (file_exists($filenameIn))) $processor = new PhpOffice\PhpWord\TemplateProcessor($filenameIn);
    else if (!$processor) return false;
    
    if (($save) && ($filenameOut)) {
        echo "Now saving to: $filenameOut\n";
        $processor->saveAs($filenameOut);
        return true;    
    }
    else if ($save) return false;
    else {
        $processor->save();
        return true;
    }
}
// ======================================================
function getDistinctValues($column,$tables=array()) {
	$db = new database();
	$result = array();
	foreach ($tables as $t) {
		$sql = "SELECT DISTINCT $column FROM $t";
		$res = $db->query($sql);
		$tab = $db->getTable($res);
		$val = explode(",",serializeDbField($tab,$column));
		$result = array_merge($result,$val);
	}
	return array_unique($result);
}
function getLookupValues($table,$id="id",$label="description") {
	$db = new database();
	$sql = "SELECT $id as id,$label as label FROM $table";
	$res = $db->query($sql);
	$tab = $db->getTable($res);
	return $tab;
}
function getSimpleDbVal($con,$table,$field,$condField,$condValue,$extraCond=array()) {
    $sql = "SELECT $field FROM $table WHERE $condField = $condValue";
    if ($extraCond) {
        foreach ($extraCond as $k=>$v) {
            $sql .= " AND $k = $v";
        }
    }
    $res = $con->query($sql);
    $tab = $con->getTable($res);
    if (isset($tab[0][$field])) return $tab[0][$field];
    else return false;
}
// ======================================================
function buildGoogleCalEvent($title,$startDate,$startTime,$finishDate,$finishTime,$description,$location) {
    
    $startDate      = date('Y-m-d',strtotime($startDate));
    $startDate      = explode("-", $startDate);
    $startTime      = str_replace("AM", "", $startTime);
    $startTime      = str_replace("PM", "", $startTime);
    $startTime      = str_replace(":", "", $startTime);
    
    $hh             = substr($startTime,0,4);
    $mm             = substr($startTime,2,2);
    
    // fix GMT issue
    $start          = mktime($hh,$mm,0,$startDate[1],$startDate[2],$startDate[0]);
    $start          = strtotime("+10 hours",$start);
    
    $startdatetime  = date('Ymd',$start)."T".date('His',$start)."Z";
    
    $finishDate      = date('Y-m-d',strtotime($finishDate));
    $finishDate      = explode("-", $finishDate);
    $finishTime      = str_replace("AM", "", $finishTime);
    $finishTime      = str_replace("PM", "", $finishTime);
    $finishTime      = str_replace(":", "", $finishTime);
    
    $hh             = substr($finishTime,0,4);
    $mm             = substr($finishTime,2,2);
    
    // fix GMT issue
    $finish          = mktime($hh,$mm,0,$finishDate[1],$finishDate[2],$finishDate[0]);
    $finish          = strtotime("+10 hours",$finish);
    
    $finishdatetime  = date('Ymd',$finish)."T".date('His',$finish)."Z";
    
    $title          = urlencode ($title);
    $description    = urlencode ($description);
    $location       = urlencode ($location);
    
    $ret = "http://www.google.com/calendar/event?action=TEMPLATE&text=$title&dates=".strtoupper($startdatetime)."/".strtoupper($finishdatetime)."&details=$description&location=$location&trp=false&sprop=&sprop=name:";
    
    return $ret;
}
// ======================================================
// Prettyify Functions
// ======================================================
function prettifyXML($filename) {
    $simpleXml = simplexml_load_file($filename);
    $handle = fopen($filename,'w+');
    if ($handle) {
        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($simpleXml->asXML());
        
        fwrite($handle,$dom->saveXML());
        fclose($handle);
    }
    else return false;
}
// ======================================================
// Regex Helpers
// ======================================================
function regexMakeNumRange($min,$max=0) {
    // Build time Regular Expression
    $post = array("MIN"=>$time_from,"MAX"=>$time_to,"revision"=>"50","btnRun"=>"Go!");
    $data = getPageSource('http://utilitymill.com/utility/Regex_For_Range',$post);

    $xml    = @simplexml_import_dom(DOMDocument::loadHTML($data));
    $xpath  = "/html/body/div/div[2]/div[2]/div/div/div/div[2]/textarea";

    $node = $xml->xpath($xpath);
    $timeRegex = str_replace("\n", "", (string)$node[0]);
    $timeRegex = str_replace("\r", "", $timeRegex);

    return $timeRegex;
}
// ======================================================
// External Database Extraction
// ======================================================
function getOtherConnection($group) {
    $options = array();
    $settings = parse_ini_file('settings.ini',true);
    if (!$settings) die('<h1>Settings file is missing, or cannot be parsed!</h1>');
    
    foreach ($settings as $k=>$v) {
        if ($k==$group) {
            foreach ($v as $a=>$b) {
                $options[$a]=$b;
            }
        }
    }
    
    $db = new database($options);
    return $db;
}
function transferTable($table,$source,$dest,$cond=array()) {
    $db_s   = ("POLLUX" == strtoupper($source)) ? getPolluxConnection():  getOtherConnection(strtoupper($source));
    $db_d   = ("POLLUX" == strtoupper($dest))   ? getPolluxConnection():  getOtherConnection(strtoupper($dest));
    
    echo "Getting field definitions...\n";
    $fields = $db_s->getFieldDefinitions($table);
    
    echo "Getting Data...\n";
    $sql    = "SELECT * FROM $table";
    if ($cond) {
        $sql = " WHERE ";
        $cnt = 0;
        foreach ($cond as $c) {
            if ($cnt) $sql .= " AND ";
            $sql .= $c;
            $cnt++;
        }
    }
    $res    = $db_s->query($sql);
    $tab    = $db_s->getTable($res);
    
    echo "Inserting Data...\n";
    // refresh...
    $sql = "TRUNCATE TABLE $table";
    $db_d->query($sql);
    foreach ($tab as $row) {
        $sql = "INSERT INTO $table (";
        $cnt = 0;
        foreach ($row as $k=>$v) {
            if ($cnt) $sql .= ",";
            $sql .= "$k";
            $cnt++;
        }
        $sql .= ") VALUES (";
        $cnt = 0;
        foreach ($row as $k=>$v) {
            if ($cnt) $sql .= ",";
            
            foreach ($fields as $f) {
                if (trim($f["COLUMN_NAME"])==strtoupper($k)) break;
            }
            if ((trim($f["DATA_TYPE"])=='CHAR') || (trim($f["DATA_TYPE"])=='VARCHAR')) $sql .= "'". htmlentities ($v,ENT_QUOTES)."'";
            else if (!$v) $sql .= '0';
            else $sql .= "$v";
            
            $cnt++;
        }
        $sql .= ")";
        $db_d->query($sql);
        echo ".";
    }
}
// ======================================================
// Pollux Extraction Specific Functions
// ======================================================
function getPolluxConnection($server="",$env="National",$date="db2000") {
    $options = array();
    $settings = parse_ini_file('settings.ini',true);
    if (!$settings) die('<h1>Settings file is missing, or cannot be parsed!</h1>');
    
    foreach ($settings as $k=>$v) {
        if ($k=="POLLUX") {
            foreach ($v as $a=>$b) {
                $options[$a]=$b;
            }
        }
    }
    
    if ($server) $options["SERVER"] = $server;
    if (($env!="National") && ($env)) $options["NAME"] = str_replace("National", $env, $options["NAME"]);
    if (($date!="db2000") && ($date)) $options["NAME"] = str_replace("db2000", implode("/",explode("-", $date)), $options["NAME"]);
    
    $db = new database($options);
    return $db;
}
// ======================================================
// Pollux Functions
// ======================================================
function getPanelType($con,$panel) {
    $sql = "SELECT CUSTOM8 FROM TFAM WHERE PANEL = $panel";
    $res = $con->query($sql);
    $tab = $con->getTable($res);
    
    $result = "";
    if ((isset($tab[0]["CUSTOM8"])) && ((int)$tab[0]["CUSTOM8"])) {
        switch ((int)$tab[0]["CUSTOM8"]) {
            case 1:
                $result = "Live";
                break;
            case 2:
                $result = "iPanel";
                break;
            default:
                $result = "Unknown";
                break;
        }
        return $result;
    }
    else return "ERR_NO_PANEL";
}
function getMeterType($meter_version) {
    $result = "";
    $meter_version = (float)$meter_version;
    if (($meter_version==2.18) || ($meter_version==2.41) || ($meter_version==2.46)) $result = "UNITAM Classic";
    else if ($meter_version==2.111)                                                 $result = "TVM5";
    else if ($meter_version>=3)                                                     $result = "UNITAM 3";
    else                                                                            $result = "Unknown";
    
    return $result;
}
function getCountMetered($con,$type,$panel) {
    if ($con->getDbType() == 'isql') {
        $status = ($type=="TV") ? "0":"6";
        $sql = "SELECT COUNT(*)
                FROM TUNIT
                WHERE   STATUS = $status
                        AND PANEL = $panel";
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        $num = $tab[0]["COUNT"];
        return $num;
    }
    else return false;
}
function getInstallDate($con,$type,$panel) {
    if ($con->getDbType() == 'isql') {
        $field = ($type=="LIVE") ? "INSTALLDATE":"VAL";
        $table = "";
        $econd = "";
        switch($type) {
            case "LIVE":
                $table = "TFAM";
                break;
            case "TEST":
                $table = "TEXTRAFAM";
                $econd = "CODE=11";
                break;
            case "NETSIGHT":
                $table = "TEXTRATV";
                $econd = "UNITID=100 AND CODE=4";
                break;
            default:
                return "";
                break;
        }
        $sql = "SELECT $field FROM $table WHERE PANEL=$panel";
        if ($econd) $sql .= "AND $econd";
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        if (!isset($tab[0])) return false;
        return $tab[0][$field];
    }
    return false;
}
function getDisinstallDate($con,$type,$panel) {
    if ($con->getDbType() == 'isql') {
        $field = ($type=="LIVE") ? "DISINSTDATE":"VAL";
        $table = "";
        $econd = "";
        switch($type) {
            case "LIVE":
                $table = "TFAM";
                break;
            case "TEST":
                $table = "TEXTRAFAM";
                $econd = "CODE=12";
                break;
            case "NETSIGHT":
                $table = "TEXTRATV";
                $econd = "UNITID=100 AND CODE=5";
                break;
            default:
                return "";
                break;
        }
        $sql = "SELECT $field FROM $table WHERE PANEL=$panel";
        if ($econd) $sql .= "AND $econd";
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        if (!isset($tab[0])) return false;
        return $tab[0][$field];
    }
    else return false;
}
function getHouseholdType($con,$panel) {
    $live_status    = array('5.0','5.1','5.2','5.3','5.4','5.5','5.6','5.7','5.8','5.9','65V');
    $live_sec       = array('65V'=>'704','65V'=>'790');
    $code_status     = "'1','3','14','15','16','17','18','19','20'";
    
    $sql = "SELECT	DISTINCT
                F.PANEL,
                F.STATUS,
                F.SECSTATUS,
                F.CUSTOM8,
                F.INSTALLDATE,
                F.DISINSTDATE,
                (
                    SELECT VAL
                    FROM TEXTRAFAM E1
                    WHERE   E1.CODE = 11
                            AND E1.VAL IS NOT NULL
                            AND E1.PANEL = F.PANEL
                ) AS TEST_PANEL,
                (
                    SELECT VAL
                    FROM TEXTRAFAM E1
                    WHERE   E1.CODE = 12
                            AND E1.VAL IS NOT NULL
                            AND E1.PANEL = F.PANEL
                ) AS TEST_PANEL_DISINSTALL,
                (
                    SELECT VAL
                    FROM TEXTRATV T1
                    WHERE   T1.CODE = 4
                            AND T1.VAL IS NOT NULL
                            AND T1.UNITID=100
                            AND T1.PANEL = F.PANEL
                ) AS NETSIGHT_PANEL,
                (
                    SELECT VAL
                    FROM TEXTRATV T1
                    WHERE   T1.CODE = 5
                            AND T1.VAL IS NOT NULL
                            AND T1.UNITID=100
                            AND T1.PANEL = F.PANEL
                ) AS NETSIGHT_PANEL_DISINSTALL
            FROM TFAM F
            LEFT JOIN TEXTRAFAM E ON (F.PANEL=E.PANEL AND VAL IS NOT NULL)
            LEFT JOIN TEXTRATV T ON (F.PANEL=T.PANEL AND T.UNITID=100 AND VAL IS NOT NULL)
            WHERE   (
                       F.STATUS IN ('".  implode("','", $live_status)."')
                       OR
                       (
                                E.CODE = 9
                                AND E.VAL NOT IN ($code_status)
                       )
                       OR
                       (
                                T.CODE = 2
                                AND T.VAL NOT IN ($code_status)
                       )
                    )
                    AND F.PANEL = $panel";
    
    $res = $con->query($sql);
    $tab = $con->getTable($res);
    
    if ($tab) {
        $continue = true;
    
        // Resolve if match secondary status condition
        if (in_array($tab[0]["STATUS"],$live_sec)) {
            $continue = false;
            foreach ($live_sec as $k=>$v) {
                if (($tab[0]["STATUS"]==$k) && ($tab[0]["SECSTATUS"]==$v)) {
                    $continue = true;
                    break;
                }
            }
        }
        
        if ($continue) {
            // work backwards
            if (
                    ($tab[0]["TEST_PANEL"]) && 
                    (!$tab[0]["TEST_PANEL_DISINSTALL"]) && 
                    (
                        (
                            ((int)$tab[0]["INSTALLDATE"]) && 
                            ((int)$tab[0]["TEST_PANEL"] < (int)$tab[0]["INSTALLDATE"])
                        ) ||
                        (!(int)$tab[0]["INSTALLDATE"])
                    )
            )      return "TEST";
            elseif (
                    ($tab[0]["NETSIGHT_PANEL"]) && 
                    (!$tab[0]["NETSIGHT_PANEL_DISINSTALL"]) && 
                    (
                        (
                            ((int)$tab[0]["INSTALLDATE"]) && 
                            ((int)$tab[0]["NETSIGHT_PANEL"] < (int)$tab[0]["INSTALLDATE"])
                        ) ||
                        (!(int)$tab[0]["INSTALLDATE"])
                    )
            )  return "NETSIGHT";
            
            elseif (isset($tab[0]["INSTALLDATE"]) && ($tab[0]["INSTALLDATE"])   && (!$tab[0]["DISINSTDATE"]))                                                   return "LIVE";
            elseif (isset($tab[0]["STATUS"])) return $tab[0]["STATUS"];
            else return "";
        }
        else return $tab["STATUS"];
    }
    else return false;
}
?>