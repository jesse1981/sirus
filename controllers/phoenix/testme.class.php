<?php
class testme extends template {
    public function testthis() {
        $tmp = "/tmp/";
        $phpdocx = new phpdocxgen($tmp."template.docx");

        //$phpdocx->addImage("#DOG#",$tmp."example_dog.jpg");


        $phpdocx->assignToHeader("#HEADER1#","Header 1"); // basic field mapping to header
        $phpdocx->assignToFooter("#FOOTER1#","Footer 1"); // basic field mapping to footer


        $phpdocx->assign("#TITLE1#","Pet shop list"); // basic field mapping

        $phpdocx->assignBlock(  "members",
                                array(
                                    array(
                                        "#NAME#"=>"John",
                                        "#SURNAME#"=>"DOE"),
                                        array(
                                            "#NAME#"=>"Jane",
                                            "#SURNAME#"=>"DOE"
                                        )
                                    )
                            ); // this would replicate two members block with the associated values

        $phpdocx->assignNestedBlock(    "pets",
                                        array(
                                            array(
                                                "#PETNAME#"=>"Rex"
                                            )
                                        ),
                                        array("members"=>1)
                                    ); // would create a block pets for john doe with the name rex
        $phpdocx->assignNestedBlock(    "pets",
                                        array(
                                            array(
                                                "#PETNAME#"=>"Rox"
                                            )
                                        ),
                                        array("members"=>2)
                                    ); // would create a block pets for jane doe with the name rox

        $phpdocx->assignNestedBlock(    "toys",
                                        array(
                                            array(
                                                "#TOYNAME#"=>"Ball"),
                                                array("#TOYNAME#"=>"Frisbee"),
                                                array("#TOYNAME#"=>"Box")),
                                                array("members"=>1,"pets"=>1)
                                    ); // would create a block toy for rex
        $phpdocx->assignNestedBlock(    "toys",
                                        array(
                                            array("#TOYNAME#"=>"Frisbee")
                                        ),
                                        array(
                                            "members"=>2,
                                            "pets"=>1
                                        )
                                    ); // would create a block toy for rox

        $phpdocx->save($tmp."pets.docx");

        
        echo "Complete!\n";
    }
    public function newtest() {
        // Library of Regular Expressions to clean
        $regex = array( '/^ /'=>'',
                        '/\(.*\)/'=>'',
                        '/ $/'=>'');
        
        $results = " test me ";
        // use the reg expressions cleaning array
        foreach ($regex as $k1=>$v1) {


            $results = preg_replace($k1, $v1, $results);
            echo "The value of results is '$results'\n";
        }
    }
    
}
?>