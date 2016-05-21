<?php

class template {
    var $view;
    var $template;
    var $interface;
    var $dataSet;
    
    public function __construct($module="",$action="",$id="") {
        if (!$module) $module = MODULE;
        if (!$action) $action = ACTION;
        if (!$id) $id = ID;
        
        try {
            $this->dataSet = new object($module);
            if (($id) && ($id>=0)) $this->dataSet->load($id);
        }
        catch (Exception $e) {
            //
        }
        
        // Global test if user has access to this object
        $session = new session();
        $mylevel = (int)$session->getKey('access');
        if (isset($this->access)) {
            if ((php_sapi_name()!="cli") && ($mylevel<$this->access)) {
                die('You do not have enough privelege to access this object!');
            }
        }
        
        $function = $action;
        ini_set('memory_limit', '-1');
        $this->$function($id);
        
        
    }
    public function __destruct() {
        ini_set('memory_limit', '128M');
    }
    public function __call($method, $args) {
        if(method_exists($this, $method)) {
          return call_user_func_array(array($this, $method), $args);
        }
        else{
          //throw new Exception(sprintf('The required method "%s" does not exist for %s', $method, get_class($this)));
        }
    } 
    
    public function output($name="") {
        if (FORMAT) $view = "views".DIR_SEP.FORMAT.DIR_SEP."template.php";
        else if ($this->view!="") $view = "views".DIR_SEP.$this->view.DIR_SEP."template.php";
        else if ($name!="") $view = "views".DIR_SEP."$name".DIR_SEP."template.php";
        else $view = 'views'.DIR_SEP.'template.php';
        
        $template = ($this->template) ? "templates".DIR_SEP.$this->template:"";
        
        if (!file_exists($view)) die("<h1>The specified view filename $view does not exist!</h1><p>".$this->view."</p>");
        if (!$this->interface) {
            include $view;
            // Master Template
            if (($template) && (file_exists($template))) {
                include $template;
            }
        }
        else $this->renderInterface($view);
    }
    private function renderInterface($view,$obj=false) {
        $handle = false;
        if ($view) $view = "interfaces".DIR_SEP."$view.xml";
        if (!$obj) $handle = fopen($view,'r');
        if ($handle||$obj) {
            if ($handle) {
                $buffer = fread($handle,filesize($view));
                fclose($handle);
                $interface = new SimpleXMLElement($buffer);
            }
            else $interface = $obj;
            foreach ($interface->interface as $object) {
                $children = (array)$object->children();
                foreach ($children as $k => $v) {
                    switch ($k) {
                        case "load":
                            $module = $v;
                            $action = "";
                            $id = "";
                            $atts = (array)$object->load->attributes();
                            if (count($atts)) {
                                $action = "";
                                $id = "";
                                foreach ($atts["@attributes"] as $a=>$b) {
                                    switch ($a) {
                                        case "action":
                                            $action = $b;
                                            break;
                                        case "value":
                                            $id = $b;
                                            break;
                                    }
                                }
                            }
                            require_once "/controllers/$module.class.php";
                            $load = new $module($module,$action,$id);
                            $load->output();
                            break;
                        case "tabs":
                            $tabitems = (array)$children[$k]->children();
                            echo '<div class="tabs"><ul>';
                            foreach ($tabitems["item"] as $item) {
                                $tabatts = (array)$item->attributes();
                                echo '<li><a href="#tabs-'.$this->formatTabId($tabatts["@attributes"]["title"]).'">'.$tabatts["@attributes"]["title"].'</a></li>';
                            }
                            echo '</ul>';
                            foreach ($tabitems["item"] as $item) {
                                $tabatts = (array)$item->attributes();
                                $tabchild = $item->children();
                                echo '<div id="tabs-'.$this->formatTabId($tabatts["@attributes"]["title"]).'">';
                                $this->renderInterface('', $tabchild);
                                echo '</div>';
                            }
                            echo '</div>';
                            break;
                    }
                }
            }
        }
        $this->interface = false;
    }
    private function loadPartialView($filename) {
        $filename = str_replace("/",DIR_SEP,$filename);
        if (!file_exists($filename)) echo "<h1>Error: $filename does not exist.</h1>";
        ob_start();
        include $filename;
        return ob_get_clean();
    }
    private function formatTabId($v) {
        $v = strtolower($v);
        $v = str_replace(' ', '-', $v);
        $v = str_replace('/', '-', $v);
        $v = str_replace("\\", '-', $v);
        return $v;
    }
    private function outputList() {
        $items = $this->dataSet->getAllItems($this->dataSet->name);
        
        ?><div class="list"><table><thead><tr><?php
        
        foreach ($this->dataSet->labels as $v) {
            echo "<th>$v</th>";
        }
        
        ?></tr></thead><tbody><?php
        
        foreach ($items as $item) {
            echo '<tr rel="'.$item->id.'">';
            foreach ($item->fields as $f=>$v) {
                echo "<td>";
                $this->renderInputControl($obj, $f, $v, false);
                echo "</td>";
            }
        }
        
        ?></tbody></div></table><?php
    }
    private function outputForm() {
        $obj = $this->dataSet;
        $dash = new dashboard();
        $postValues = $dash->getPost();
        
        if (count($postValues)) {
            foreach ($obj->fields as $f=>$v) {
                if (!is_object($v)) $obj->fields[$f] = ($dash->_request($f))?$dash->_request($f):"";
            }
            $obj->save();
        }
        echo '<form method="POST" accept-charset="UTF-8" enctype="multipart/form-data" class="input-form">';

        foreach ($obj->fields as $f=>$v) {
            if ((!is_object($v))||((is_object($v)) && ($v->parentRel=="to_one") && (count($v->objects)==1))) {
                if (is_object($v)) $v = $v->objects[0]->id;
                echo '<label for="'.$f.'">'.$obj->labels[$f]."</label>";
                $value = ($edit) ? $v:"";
                $this->renderInputControl($obj,$f,$value);
            }
        }
        // Submit/Reset
        echo '<input class="btn btn-primary" type="submit" value="Save" /> <input class="btn" type="reset" value="Reset" /> <button class="btn" style="float: left;height: 26px;margin-left: 5px" onClick="history.go(-1);">Cancel</button>';
        echo '</form>';
    }
    private function outputSearch() {
        
    }
    private function outputXml($obj=null) {
        if ($obj == null) $obj = $this->dataSet;
        foreach ($obj->fields as $field) {
            if (is_object($field)) {
                echo "<".$obj->fields[$field].">\n";
                foreach ($field->objects as $join) $this->outputXml($join);
                echo "</".$obj->fields[$field].">\n";
            }
            else echo "<$field>".$obj[$field]."</$field>\n";
        }
    }
    
    private function renderInputControl($obj,$field,$value="",$editable = true) {
        if (is_object($obj->fields[$field])) {
            echo '<SELECT id="'.$field.'" name="'.$field.'">';
            
            echo '<OPTION value="0">-- Please Select --</OPTION>';
            
            $selectObject = new object($obj->fields["$field"]->name);
            $selectFields = explode($selectObject->select);
            $selectItems = $selectObject->getAllItems($selectObject->name);
            
            foreach ($selectItems as $item) {
                $selected = ($item->id==$value) ? "selected":"";
                
                $label = "";
                for ($i=0;$i<count($selectFields);$i++) {
                    if ($i) $label.=" ";
                    $label .= $item->fields[$selectFields[$i]];
                }
                
                echo '<OPTION value="'.$item->id.'" '.$selected.'>'.$label.'</OPTION>';

            }
            
            echo '</SELECT>';
        }
        else if ($obj->datatypes[$field]=="datetime") {
            $value = date('Y-m-d H:i:s',$value);
            // date/time picker
            if ($editable) echo '<input type="text" class="datetime" id="'.$field.'" name="'.$field.'" value="'.$value.'" />';
            else echo '<span class="value">'.$value.'</span>';
        }
        else if ($obj->datatypes[$field]=="date") {
            $value = date('Y-m-d',$value);
            // date/time picker
            if ($editable) echo '<input type="text" class="datepick" id="'.$field.'" name="'.$field.'" value="'.$value.'" />';
            else echo '<span class="value">'.$value.'</span>';
        }
        else if ($obj->datatypes[$field]=="string") {
            // do a text area
            if ($editable) echo '<textarea id="'.$field.'" name="'.$field.'" />'.$value.'</textarea>';
            else echo '<span class="value">'.$value.'</span>';
        }
        else if ($obj->datatypes[$field]=="integer") {
            // just a simple text input
            if ($editable) echo '<input type="text" id="'.$field.'" name="'.$field.'" value="'.$value.'" />';
            else echo '<span class="value">'.$value.'</span>';
        }
        else if ($obj->datatypes[$field]=="blob") {
            // file input control
            if ($editable) echo '<input type="file" id="'.$field.'" name="'.$field.'" />';
            else echo '<a class="download"><span class="value">'.$value.'</span></a>';
        }
    }
    private function renderListHeadings($group,$groupname="") {
        $count = 0;
        echo '<thead>';
        
        echo '<tr><th class="shadow" colspan="'.($this->getFieldListCount($this->fields[$group])).'">'.$groupname.'</th>';
        
        echo $this->getStyleCell();
        echo '</tr>';
        
        echo '<tr class="columnnames">';
        foreach ($this->fields[$group] as $field) {
            if (($count != $this->getPk($group)) && ($field->name!=$this->parentId) && ($field->inList)) {
                $colspan = ($count == ($this->getFieldListCount($this->fields[$group]))) ? "2":"1";
                echo '<th colspan="'.$colspan.'">'.$field->label.'<br/><input class="quick-search" type="text" /></th>';
            }
            $count++;
        }
        echo '</tr></thead>';
    }
    private function renderFilterType($table,$fieldObj) {
        $objName = $table."_".$fieldObj->name;
        $typesValues = array('>=','<=','>','<','=','<>','like','range');
        $notallowed = array();
        echo '<select name="'.$objName.'_filtertype">';
        foreach ($typesValues as $v) {
            $datatype = ($fieldObj->getIsByRef()) ? "string":$fieldObj->datatype;
            switch ($datatype) {
                case "string":
                    $notallowed = array('>=','<=','>','<','range');
                    break;
                case "integer":
                    $notallowed = array('like');
                    break;
                case "datetime":
                    $notallowed = array('like');
                    break;
                case "blob":
                    $notallowed = array('>=','<=','>','<','range','like');
                    break;
            }
            if (!in_array($v, $notallowed)) {
                echo "<option>$v</option>";
            }
        }
        echo '</select>';
    }
    private function renderModelForm($id=0) {
		$class	= get_class($this);
		$db		= new database();
		$fields = $db->getFieldDefinitions($class);
		$result = "";
		
		$result .= '<form method="post" name="'.$class.'">';
		for ($i=0;$i<(count($fields));$i++) {
			if ($fields[$i]["column_name"]!="id") $result .= '<label for="'.$fields[$i]["column_name"].'">'.$fields[$i]["column_name"].'</label><input data-type="'.$fields[$i]["data_type"].'" type="input" name="'.$fields[$i]["column_name"].'" id="'.$fields[$i]["column_name"].'"/>';
		}
		if ($id) $result .= '<input type="hidden" name="id" value="'.$id.'"/>';
		$result .= '<input type="hidden" name="table" value="'.$class.'"/>';
		$result .= '<input id="save_model_form" type="button" value="Submit" class="btn btn-primary">';
		$result .= "</form>";
		echo $result;
	}
	
    public function setView($view,$template="",$interface = false) {
        // Update, take first setting as priority.
        if ($view) $this->view = $view;
        if ($template) $this->template = $template;
        if ($interface) $this->interface = $interface;
    }
    
    private function hasChildAttach($id) {
        $childMod = new $this->childMod;
        $childFields = $childMod->getFields();
        $childConds = $childMod->getConditions($id);
        $childTables = $childMod->getTables();
        foreach ($childFields[0] as $field) {
            if ($field->datatype=='blob') {
                // an attachment field type - run a query on this field
                $sql = "SELECT ".$field->name." FROM ".$childTables[0]." WHERE LENGTH(".$field->name.") > 0 AND ".$childConds[0][0];
                //echo "$sql<br/>";
                $res = $this->db->query($sql);
                if ($this->db->getNumRows($res)) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }
}
?>