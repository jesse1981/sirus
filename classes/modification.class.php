<?php
class modification {
    
    var $table;
    var $fields;
    var $values;
    var $modifiedOn;
    var $modifiedBy;
    var $saved_id;
    
    public function __construct($table,$fields,$values,$modifiedBy) {
        $this->table = $table;
        $this->fields = $fields;
        $this->values = $values;
        $this->modifiedOn = date('Y-m-d H:i:s',time());
        $this->modifiedBy = $modifiedBy;
        $this->saved_id = $values[0]["item_id"];
    }
    
    public function save() {
        $database = new database();
        foreach ($this->fields as $field) {
            
            if (isset($this->values[$this->table."_".$field->name])) {
            
                $sql = "INSERT INTO modification (module,field,newvalue,modifiedBy,modifiedOn,ref) VALUES (";
                $sql .= '"'.$this->table.'","'.$field->name.'",';

                if ($field->datatype!="blob") $value = '"'.$this->values[$this->table."_".$field->name].'"';
                else $value = "'File'";

                $sql .= "$value,'".$this->modifiedBy."','".$this->modifiedOn."',".$this->saved_id;

                $sql .= ")";
                $database->query($sql);
            }
        }
    }
    
}
?>