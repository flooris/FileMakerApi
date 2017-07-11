<?php
require_once dirname(__FILE__) . '/../Error/Validation.php';
require_once dirname(__FILE__) . '/../Result.php';

class FileMaker_Command_Implementation
{
    var $_fm;
    var $_layout;
    var $layout;
    var $_script;
    var $_scriptParams;
    var $_preReqScript;
    var $_preReqScriptParams;
    var $_preSortScript;
    var $_preSortScriptParams;
    var $record_class;
    var $_recordId;

    public function __construct($filemaker, $layout)
    {
        $this->_fm =& $filemaker;
        $this->_layout = $layout;
        $this->record_class = $filemaker->getProperty('recordClass');
    }

    function setResultLayout($layout)
    {
        $this->layout = $layout;
    }

    function setScript($script, $parameters = null)
    {
        $this->_script = $script;
        $this->_scriptParams = $parameters;
    }

    function setPreCommandScript($script, $parameters = null)
    {
        $this->_preReqScript = $script;
        $this->_preReqScriptParams = $parameters;
    }

    function setPreSortScript($script, $parameters = null)
    {
        $this->_preSortScript = $script;
        $this->_preSortScriptParams = $parameters;
    }

    function setRecordClass($record_class)
    {
        $this->record_class = $record_class;
    }

    function setRecordId($record_id)
    {
        $this->_recordId = $record_id;
    }

    function validate($fieldname = null)
    {
        if ( ! is_a($this, 'FileMaker_Command_Add_Implementation') && ! is_a($this,
                'FileMaker_Command_Edit_Implementation')
        ) {
            return true;
        }
        $layout = &$this->_fm->getLayout($this->_layout);
        if (FileMaker:: isError($layout)) {
            return $layout;
        }
        $validation = new FileMaker_Error_Validation($this->_fm);
        if ($fieldname === null) {
            foreach ($layout->getFields() as $fieldname => $value) {
                if ( ! isset ($this->_fields[$fieldname]) || ! count($this->_fields[$fieldname])) {
                    $field = array(
                        0 => null
                    );
                } else {
                    $field = $this->_fields[$fieldname];
                }
                foreach ($field as $V2063c160) {
                    $validation = $value->validate($V2063c160, $validation);
                }
            }
        } else {
            $value = &$layout->getField($fieldname);
            if (FileMaker:: isError($value)) {
                return $value;
            }
            if ( ! isset ($this->_fields[$fieldname]) || ! count($this->_fields[$fieldname])) {
                $field = array(
                    0 => null
                );
            } else {
                $field = $this->_fields[$fieldname];
            }
            foreach ($field as $V2063c160) {
                $validation = $value->validate($V2063c160, $validation);
            }
        }

        return $validation->numErrors() ? $validation : true;
    }

    function & _getResult($V0f635d0e)
    {
        $V3643b863 = new FileMaker_Parser_FMResultSet($this->_fm);
        $Vb4a88417 = $V3643b863->parse($V0f635d0e);
        if (FileMaker:: isError($Vb4a88417)) {
            return $Vb4a88417;
        }
        $Vd1fc8eaf = new FileMaker_Result($this->_fm);
        $Vb4a88417 = $V3643b863->setResult($Vd1fc8eaf, $this->record_class);
        if (FileMaker:: isError($Vb4a88417)) {
            return $Vb4a88417;
        }

        return $Vd1fc8eaf;
    }

    function _getCommandParams()
    {
        $V21ffce5b = array(
            '-db'  => $this->_fm->getProperty('database'
            ),
            '-lay' => $this->_layout
        );
        foreach (array(
                     '_script'        => '-script',
                     '_preReqScript'  => '-script.prefind',
                     '_preSortScript' => '-script.presort'
                 ) as $Vb2145aac => $Veca07335) {
            if ($this->$Vb2145aac) {
                $V21ffce5b[$Veca07335] = $this->$Vb2145aac;
                $Vb2145aac .= 'Params';
                if ($this->$Vb2145aac) {
                    $V21ffce5b[$Veca07335 . '.param'] = $this->$Vb2145aac;
                }
            }
        }
        if ($this->layout) {
            $V21ffce5b['-lay.response'] = $this->layout;
        }

        return $V21ffce5b;
    }
}