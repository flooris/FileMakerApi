<?php
require_once dirname(__FILE__) . '/../CommandImpl.php';

class FileMaker_Command_Add_Implementation extends FileMaker_Command_Implementation
{
    var $_fields = array();

    function __construct($filemaker, $layout, $values = array())
    {
        FileMaker_Command_Implementation::__construct($filemaker, $layout);
        foreach ($values as $fieldname => $value) {
            if ( ! is_array($value)) {
                $value = array($value);
            }
            $this->_fields[$fieldname] = $value;
        }
    }

    function &execute()
    {
        if ($this->_fm->getProperty('prevalidate')) {
            $validation_result = $this->validate();
            if (FileMaker::isError($validation_result)) {
                return $validation_result;
            }
        }
        $layout =& $this->_fm->getLayout($this->_layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $command_parameters = $this->_getCommandParams();
        $command_parameters['-new'] = true;
        foreach ($this->_fields as $fieldname => $value) {
            if (strpos($fieldname, '.') !== false) {
                list($Vb068931c, $V11e868ac) = explode('.', $fieldname, 2);
                $V11e868ac = '.' . $V11e868ac;
            } else {
                $Vb068931c = $fieldname;
                $field = $layout->getField($fieldname);
                if (FileMaker::isError($field)) {
                    return $field;
                }
                if ($field->isGlobal()) {
                    $V11e868ac = '.global';
                } else {
                    $V11e868ac = '';
                }
            }
            foreach ($value as $V6a992d55 => $V3a6d0284) {
                $command_parameters[$Vb068931c . '(' . ($V6a992d55 + 1) . ')' . $V11e868ac] = $V3a6d0284;
            }
        }
        $result = $this->_fm->_execute($command_parameters);
        if (FileMaker::isError($result)) {
            return $result;
        }

        return $this->_getResult($result);
    }

    function setField($fieldname, $value, $line = 0)
    {
        $this->_fields[$fieldname][$line] = $value;

        return $value;
    }

    function setFieldFromTimestamp($fieldname, $timestamp, $line = 0)
    {
        $layout =& $this->_fm->getLayout($this->_layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $field = $layout->getField($fieldname);
        if (FileMaker::isError($field)) {
            return $field;
        }
        switch ($field->getResult()) {
            case 'date':
                return $this->setField($fieldname, date('m/d/Y', $timestamp), $line);
            case 'time':
                return $this->setField($fieldname, date('H:i:s', $timestamp), $line);
            case 'timestamp':
                return $this->setField($fieldname, date('m/d/Y H:i:s', $timestamp), $line);
        }

        return new FileMaker_Error($this->_fm,
            'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }
}
