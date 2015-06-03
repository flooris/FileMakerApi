<?php

namespace Flooris\FMApi;

class FileMakerApi extends FileMaker {

	private $layoutName = '';
	private $username = '';
	private $password = '';
	private $database = '';
	private $host = '';
	private $throwException = 0;

	public $sortCriteria;

	public $findRange;

	public $tableCount;
	public $foundSetCount;
	public $fetchCount;

	public function __construct($layoutName, $database = NULL, $hostspec = NULL, $username = NULL, $password = NULL, $throwException = 0) {
		$this->layoutName		= $layoutName;
		$this->host			    = $hostspec;
		$this->database			= $database;
		$this->password			= $password;
		$this->throwException	= $throwException;
	}


	/*
	 * FIND
	 */
	public function findRecord($criteria) {
		$records = $this->findRecords($criteria);
		if(!$records) {
			if($this->throwException) {
				throw new \Exception('Record not found in Layout: ' . $this->layoutName . PHP_EOL . print_r($criteria, true));
			}
			return 0;
		}

		return $records[0];
	}


    /**
     * Find filemaker record object
     *
     * @param $criteria
     * @return FileMakerRecordObject
     * @throws Exception
     */
	public function findRecordObject($criteria) {
		$record = $this->findRecord($criteria);
		if (!$record) {
			return null;
		}

		$resultObject = new \FileMakerRecordObject($record);
		return $resultObject;
	}

    /**
     * Find filemaker record objects
     *
     * @param $criteria
     * @return FileMakerRecordObject[]
     * @throws Exception
     */
	public function findRecordObjects($criteria) {
		$records = $this->findRecords($criteria);
		$resultArray = array();
		if(!$records) {
			return $resultArray;
		}
		foreach($records as $record) {
			$resultObject = new \FileMakerRecordObject($record);
			$resultArray[] = $resultObject;
		}

		return $resultArray;
	}

	public function findRecords($criteria) {
		if($this->layoutName == '' || count($criteria) == 0)
			return 0;

		$fm = $this->newFileMaker();
		$findCommand =& $fm->newFindCommand($this->layoutName);

		foreach($criteria as $fieldName => $fieldValue) {

			$findCommand->addFindCriterion($fieldName, $fieldValue);

		}

		$this->addSortRules($findCommand);
		$this->addFindRange($findCommand);

		/** @var FileMaker_Result $result */
		$result = $this->executeFileMakerCommand($findCommand);

		if(!$result) {
			if($this->throwException) {
				throw new Exception('Record not found in Layout: ' . $this->layoutName . PHP_EOL . print_r($criteria, true));
			}
			return 0;
		}

		$this->tableCount 		= $result->getTableRecordCount();
		$this->foundSetCount 	= $result->getFoundSetCount();
		$this->fetchCount 		= $result->getFetchCount();

		$records = $result->getRecords();

		return $records;
	}


	/*
	 * GET
	 */
	public function getContainerData($url) {
		$fm = $this->newFileMaker();
		$dataString = $fm->getContainerData($url);

		if (FileMaker::isError($dataString)) {
			if($dataString->code != "401") {
				$this->throwError($url, $dataString);
			}
			return 0;
		}

		return $dataString;
	}


	/*
	 * EDIT
	 */
	public function editRecord($recordId, $updatedValues) {
		$fm = $this->newFileMaker();
		$editCommand =& $fm->newEditCommand($this->layoutName, $recordId, $updatedValues);

		$result = $this->executeFileMakerCommand($editCommand);

		if(!$result) {
			if($this->throwException) {
				throw new \Exception('Failed Edit Command on Layout: ' . $this->layoutName . PHP_EOL . 'RecordID: ' . $recordId . PHP_EOL . print_r($updatedValues, true));
			}
			return 0;
		}
		return 1;
	}


	/*
	 * INSERT
	 */
	public function insertRecord($values) {
		$fm = $this->newFileMaker();
		$addCommand =& $fm->newAddCommand($this->layoutName, $values);

		$result = $this->executeFileMakerCommand($addCommand);

		if(!$result) {
			if($this->throwException) {
				throw new \Exception('Failed Insert Command on Layout: ' . $this->layoutName . PHP_EOL . 'values: ' . print_r($values, true) . PHP_EOL);
			}
			return 0;
		}
		$records = $result->getRecords();

		return $records[0];
	}


	/*
	 * EXECUTE SCRIPT
	 */
	public function executeScript($scriptName, $scriptParameters = null) {
		if($this->layoutName == '' || $scriptName == '') {
			return 0;
		}

		$fm = $this->newFileMaker();
		$scriptCommand =& $fm->newPerformScriptCommand($this->layoutName, $scriptName, $scriptParameters);

		$result = $this->executeFileMakerCommand($scriptCommand);

		return $result;
	}

	/*
	 * BASIC FILEMAKER FUNCTIONS
	 */
	private function newFileMaker() {
        return new FileMaker($this->database, $this->host, $this->username, $this->password);
	}

	private function executeFileMakerCommand($command) {
		$result = $command->execute();

		if (FileMaker::isError($result)) {
			if($result->code != "401") {
				$this->throwError($command, $result);
			}
			return 0;
		}

		return $result;
	}

	private function throwError($command, $fileMakerError) {
		$errorMessage   = $fileMakerError->getMessage();
		$errorCode      = $fileMakerError->getCode();

		$exceptionMessage = ''
							. 'FileMaker Error! ' . PHP_EOL . ' '
							. 'Error Message: '     . $errorMessage . PHP_EOL . ' '
							. 'Error Code: '        . $errorCode . PHP_EOL . ' '
							. 'Trace: '             . print_r($command, true);
		throw new \Exception($exceptionMessage, $errorCode);
	}


	/*
	 * EXTENDED FILEMAKER FUNCTIONS
	 */
	private function addSortRules($findCommand) {
		if(count($this->sortCriteria)) {

			$sortIndex = 1;
			foreach($this->sortCriteria as $fieldName => $sortDirection) {
				$findCommand->addSortRule($fieldName, $sortIndex, $sortDirection);
				$sortIndex++;
			}

		}
		return $findCommand;
	}

	private function addFindRange($findCommand) {
		if(count($this->findRange)) {

			foreach($this->findRange as $start => $end) {
				if($end > 0) {
					$findCommand->setRange($start, $end);
				}
			}

		}
		return $findCommand;
	}
}

?>
