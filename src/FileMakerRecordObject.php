<?php

class FileMakerRecordObject {

	public $record;

	public $layout;
	public $fields;
	public $relatedSets;

	public $resultObj;

	public function __construct($record) {

		$this->record = $record;

		$this->resultObj = new \stdClass();

		$this->layout = $this->record->getLayout();
		$this->fields = $this->layout->getFields();
		$this->relatedSets = $this->layout->getRelatedSets();

		$this->ConvertFieldsToObject();

		$this->ConvertRelatedSetsToObject();

		return $this->resultObj;
	}

	private function ConvertFieldsToObject() {

		$this->resultObj->recordId = (int)$this->record->getRecordId();

		foreach($this->fields as $fieldName => $field) {

			$type = $field->getResult();

			$objFieldName	= $this->GetFriendlyObjectFieldName($fieldName);
			$objFieldValue	= $this->GetFieldValue($this->record, $fieldName, $type);

			$this->resultObj->$objFieldName = $objFieldValue;

		}

	}

	private function ConvertRelatedSetsToObject() {

		foreach($this->relatedSets as $setName => $relatedSet) {

			$relatedSetName = $this->GetFriendlyObjectFieldName($setName);
			$this->resultObj->$relatedSetName = array();

			//$layout = $relatedSet->_impl->_layout;
			//$layout = $relatedSet->getLayout();
			$fields = $relatedSet->getFields();
			$relatedSet = $this->record->getRelatedSet($setName);
			if(FileMaker::isError($relatedSet))
				continue;

			foreach($relatedSet as $record) {
				$resultObj = new \stdClass();

				$resultObj->recordId = (int)$record->getRecordId();

				foreach($fields as $fieldName => $field) {

					$type = $field->getResult();

					$objFieldName	= $this->GetFriendlyObjectFieldName($fieldName);
					$objFieldValue	= $this->GetFieldValue($record, $fieldName, $type);

					$resultObj->$objFieldName = $objFieldValue;

				}

				array_push($this->resultObj->$relatedSetName, $resultObj);
			}

		}

	}


	private function GetFieldValue($record, $fieldName, $type) {

		switch($type) {

			case 'number':
				$result = FileMakerHelper::GetNumber($record, $fieldName);
				break;

			/*
			 * Datum en Tijd gewoon als string verwerken...
			 * Er is nog geen situatie waarbij we een DateTime object nodig hebben...
			 */
			/*case 'timestamp':
				$this->resultObj->$objFieldName = FileMakerHelper::GetTimeStamp($record, $fieldName);
				break;

			case 'date':
				$this->resultObj->$objFieldName = FileMakerHelper::GetDate($record, $fieldName);
				break;*/

			default:
				$result = $record->getField($fieldName);
				break;

		}

		return $result;
	}

	private function GetFriendlyObjectFieldName($fieldName) {

		$objFieldNameStep1 = str_replace('::', '_', $fieldName);
		$objFieldNameStep2 = str_replace('~', '_', $objFieldNameStep1);
		$objFieldNameStep3 = str_replace(' ', '_', $objFieldNameStep2);
        $objFieldNameStep3 = str_replace('.', '_', $objFieldNameStep3);

		return $objFieldNameStep3;
	}

}

?>
