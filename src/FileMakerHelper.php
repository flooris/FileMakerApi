<?php

class FileMakerHelper {
	public static function Normalize($str = '', $max_length = 0, $remove_newlines = false) {
		
		$encoding = 'UTF-8';
		$str = html_entity_decode($str, ENT_COMPAT, $encoding);
		
		if($max_length > 0) {
			$str = substr($str, 0, $max_length);
		}
		if ($remove_newlines) {
			$str = trim(preg_replace('/\s\s+/', ' ', $str));
		}
		
		return $str;
	}
	
	public static function NormalizeExtended($str = '', $max_length = 0) {
		
		$str = str_replace('#', '', $str);
		$str = str_replace(';', '', $str);
		$str = str_replace('<', '', $str);
		$str = str_replace('>', '', $str);
		$str = str_replace('{', '', $str);
		$str = str_replace('}', '', $str);
		$str = str_replace('=', '', $str);
		$str = str_replace('$', '', $str);
		$str = str_replace('€', '', $str);
		
		return self::Normalize($str, $max_length);
	}
	
	public static function NormalizeMoney($str = '') {
		if($str == '')
			return 0;
		
		$moneyString = str_replace(',', '.', self::NormalizeExtended($str));
		
		return floatval($moneyString);
	}
	
	public static function GetNumber($record, $fieldName) {
		$valueString = str_replace(',', '.', $record->getField($fieldName));
		
		if(strstr($valueString, '.')) {
			$result = floatval($valueString);
		} else {
			$result = intval($valueString);
		}
		
		return $result;
	}
	
	public static function GetTimeStamp($record, $fieldName) {
		$valueString = $record->getField($fieldName);
		
		$result = $valueString;
		
		return $result;
	}
	
	public static function GetDate($record, $fieldName) {
		$valueString = $record->getField($fieldName);
		
		$result = $valueString;
		
		return $result;
	}
	
	public static function ConvertJsonRelatedSet($record, $fieldNames) {
		$targetObject = array();
		$firstFieldName = reset($fieldNames);
		if(!is_array($record->$firstFieldName)) {
			
			$key = 0;
			$targetObject = self::setFields($fieldNames, $key, $targetObject, $record);
			
		} else {
			
			$fieldRecords = $record->$firstFieldName;
			foreach($fieldRecords as $key => $firstFieldValue) {
				$targetObject = self::setFields($fieldNames, $key, $targetObject, $record);
			}
			
		}
		
		return $targetObject;
	}
	private function setFields($fieldNames, $key, $targetObject, $record) {
		
		$fieldObject = new \stdClass();
		foreach($fieldNames as $shortName => $longName) {
			$tempFieldValue = $record->$longName;
			if(is_array($tempFieldValue))
				$fieldValue = $tempFieldValue[$key];
			else
				$fieldValue = $tempFieldValue;

			$fieldObject = self::setField($fieldObject, $shortName, $fieldValue);
			$targetObject[$key] = $fieldObject;
		}
		return $targetObject;
	}
	private function setField($fieldObject, $fieldName, $fieldValue) {
		$fieldObject->$fieldName = $fieldValue;
		return $fieldObject;
	}
    
    
    public static function SqlDateToFmDate($mysql_datum_string) {
        if ($mysql_datum_string == "0000-00-00" || $mysql_datum_string == "0000-00-00 00:00:00") {
            return '';
        }
        
        $datum = date('m/d/Y',   strtotime($mysql_datum_string));
        return $datum;
    }
    public static function SqlDateTimeToFmDate($mysql_datumtime_string) {
        if ($mysql_datumtime_string == "0000-00-00" || $mysql_datumtime_string == "0000-00-00 00:00:00") {
            return '';
        }
        
        $datum = date('m/d/Y H:i:s', strtotime($mysql_datumtime_string));
        return $datum;
    }
}

?>
