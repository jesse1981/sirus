<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @link        https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2014 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord;

use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\String;
use PhpOffice\PhpWord\Shared\ZipArchive;

class TemplateProcessor
{
    /**
    * ZipArchive
    *
    * @var ZipArchive
    */
    private $_objZip;

    /**
    * Temporary Filename
    *
    * @var string
    */
    private $_tempFileName;

    /**
    * Document XML
    *
    * @var string
    */
    private $_documentXML;


    /**
    * Create a new Template Object
    *
    * @param string $strFilename
    */
    public function __construct($strFilename) {
            $path = dirname($strFilename);
            $this->_tempFileName = $path.DIRECTORY_SEPARATOR.time().'.docx';

            copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File

            $this->_objZip = new ZipArchive();
            $this->_objZip->open($this->_tempFileName);

            $this->_documentXML = $this->_objZip->getFromName('word/document.xml');
    }

    /**
    * Set a Template value
    *
    * @param mixed $search
    * @param mixed $replace
    */
    public function setValue($search, $replace, $limit=-1) {
            if(substr($search, 0, 1) !== '{' && substr($search, -1) !== '}') {
                    $search = '{'.$search.'}';
            }
            preg_match_all('/\{[^}]+\}/', $this->_documentXML, $matches);
            foreach ($matches[0] as $k => $match) {
                    $no_tag = strip_tags($match);
                    if ($no_tag == $search) {
                            $match = '{'.$match.'}';
                            $this->_documentXML = preg_replace($match, $replace, $this->_documentXML, $limit);	
                            if ($limit == 1) {
                                    break;
                            }			
                    }
            }
    }

    /**
    * Save Template
    *
    * @param string $strFilename
    */
    public function save($strFilename) {
            if(file_exists($strFilename)) {
                    unlink($strFilename);
            }

            $this->_objZip->addFromString('word/document.xml', $this->_documentXML);

            // Close zip file
            if($this->_objZip->close() === false) {
                    throw new Exception('Could not close zip file.');
            }

            rename($this->_tempFileName, $strFilename);
            return $strFilename;
    }

    /**
    * Clone Rows in tables
    *
    * @param string $search
    * @param array $data
    */
    public function cloneRow($search, $data=array()) {		
            // remove ooxml-tags inside pattern				
            foreach ($data as $nn => $fieldset) {
                    foreach ($fieldset as $field => $val) {
                            $key = '{'.$search.'.'.$field.'}';
                            $this->setValue($key, $key, 1);
                    }
            }
            // how many clons we need
            $numberOfClones = 0;
            if (is_array($data)) {
                    foreach ($data as $colName => $dataArr) {
                            if (is_array($dataArr)) {
                                    $c = count($dataArr);
                                    if ($c > $numberOfClones)
                                            $numberOfClones = $c;
                            }
                    }
            }
            if ($numberOfClones > 0) {
                    // read document as XML
                    $xml = \DOMDocument::loadXML($this->_documentXML, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

                    // search for tables
                    $tables = $xml->getElementsByTagName('tbl');
                    foreach ($tables as $table) {
                            $text = $table->textContent;
                            // search for pattern. Like {TBL1.
                            if (strpos($text, '{'.$search.'.') !== false) {
                                    // search row for clone
                                    $patterns = array();
                                    $rows = $table->getElementsByTagName('tr');
                                    $isUpdate = false;
                                    $isFind = false;
                                    foreach ($rows as $row) {
                                            $text = $row->textContent;
                                            $TextWithTags = $xml->saveXML($row);
                                            if (
                                                    strpos($text, '{'.$search.'.') !== false // Pattern found in this row
                                                    OR
                                                    (strpos($TextWithTags, '<w:vMerge/>') !== false AND $isFind) // This row is merged with upper row (Upper row have pattern)
                                            )
                                            {
                                                    // This row need to clone
                                                    $patterns[] = $row->cloneNode(true);
                                                    $isFind = true;
                                            } else {
                                                    // This row don't have any patterns. It's table header or footer
                                                    if (!$isUpdate and $isFind) {
                                                            // This is table footer
                                                            // Insert new rows before footer								
                                                            $this->InsertNewRows($table, $patterns, $row, $numberOfClones);
                                                            $isUpdate = true;
                                                    }
                                            }
                                    }
                                    // if table without footer					
                                    if (!$isUpdate and $isFind) {
                                            $this->InsertNewRows($table, $patterns, $row, $numberOfClones);
                                    }
                            }
                    }
                    // save document
                    $res_string = $xml->saveXML();
                    $this->_documentXML = $res_string;

                    // parsing data
                    foreach ($data as $colName => $dataArr) {
                            $pattern = '{' . $search . '.' . $colName . '}';
                            foreach ($dataArr as $value) {
                                    $this->setValue($pattern, $value, 1);
                            }
                    }
            }
    }

    /**
    * Insert new rows in table
    *
    * @param object &$table
    * @param object $patterns
    * @param object $row
    * @param int $numberOfClones
    */
    protected function InsertNewRows(&$table, $patterns, $row, $numberOfClones)	{
            for ($i = 1; $i < $numberOfClones; $i++) {
                    foreach ($patterns as $pattern) {
                            $new_row = $pattern->cloneNode(true);
                            $table->insertBefore($new_row, $row);
                    }
            }
    }
    public function saveAs($fileName)
    {
        $tempFileName = $this->save(tempnam('tmp', 'docx'));

        if (file_exists($fileName)) {
            unlink($fileName);
        }

        rename($tempFileName, $fileName);
    }
}
