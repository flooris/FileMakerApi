<?php
require_once dirname(__FILE__) . '/Parser/FMResultSet.php';

class FileMaker_Implementation
{
    var $configuration = array('charset' => 'utf-8');
    var $logger = null;

    static function getAPIVersion()
    {
        return '1.1';
    }

    static function getMinServerVersion()
    {
        return '10.0.0.0';
    }

    public function __construct($database = null, $hostspec = null, $username = null, $password = null)
    {
        if ( ! is_null($hostspec)) {
            $this->configuration['hostspec'] = $hostspec;
        }
        if ( ! is_null($database)) {
            $this->configuration['database'] = $database;
        }
        if ( ! is_null($username)) {
            $this->configuration['username'] = $username;
        }
        if ( ! is_null($password)) {
            $this->configuration['password'] = $password;
        }
    }

    function setProperty($prop, $value)
    {
        $this->configuration[$prop] = $value;
    }

    function getProperty($prop)
    {
        return isset($this->configuration[$prop]) ? $this->configuration[$prop] : null;
    }

    function getProperties()
    {
        return $this->configuration;
    }

    function setLogger(&$logger)
    {
        if ( ! is_a($logger, 'Log')) {
            return new FileMaker_Error($this, 'setLogger() must be passed an instance of PEAR::Log');
        }
        $this->logger =& $logger;
    }

    function log($message, $loglevel)
    {
        if ($this->logger === null) {
            return;
        }
        $default_loglevel = $this->getProperty('logLevel');
        if ($default_loglevel === null || $loglevel > $default_loglevel) {
            return;
        }
        switch ($loglevel) {
            case FILEMAKER_LOG_DEBUG:
                $this->logger->log($message, PEAR_LOG_DEBUG);
                break;
            case FILEMAKER_LOG_INFO:
                $this->logger->log($message, PEAR_LOG_INFO);
                break;
            case FILEMAKER_LOG_ERR:
                $this->logger->log($message, PEAR_LOG_ERR);
                break;
        }
    }

    function toOutputCharset($charset)
    {
        if (strtolower($this->getProperty('charset')) != 'iso-8859-1') {
            return $charset;
        }
        if (is_array($charset)) {
            $Vfa816edb = array();
            foreach ($charset as $V3c6e0b8a => $V3a6d0284) {
                $Vfa816edb[$this->toOutputCharset($V3c6e0b8a)] = $this->toOutputCharset($V3a6d0284);
            }

            return $Vfa816edb;
        }
        if ( ! is_string($charset)) {
            return $charset;
        }

        return utf8_decode($charset);
    }

    function &newAddCommand($layout, $values = array())
    {
        require_once dirname(__FILE__) . '/../Command/Add.php';
        $command = new FileMaker_Command_Add($this, $layout, $values);

        return $command;
    }

    function &newEditCommand($layout, $record_id, $values = array())
    {
        require_once dirname(__FILE__) . '/../Command/Edit.php';
        $command = new FileMaker_Command_Edit($this, $layout, $record_id, $values);

        return $command;
    }

    function &newDeleteCommand($layout, $record_id)
    {
        require_once dirname(__FILE__) . '/../Command/Delete.php';
        $command = new FileMaker_Command_Delete($this, $layout, $record_id);

        return $command;
    }

    function &newDuplicateCommand($layout, $record_id)
    {
        require_once dirname(__FILE__) . '/../Command/Duplicate.php';
        $command = new FileMaker_Command_Duplicate($this, $layout, $record_id);

        return $command;
    }

    function &newFindCommand($layout)
    {
        require_once dirname(__FILE__) . '/../Command/Find.php';
        $command = new FileMaker_Command_Find($this, $layout);

        return $command;
    }

    function &newCompoundFindCommand($layout)
    {
        require_once dirname(__FILE__) . '/../Command/CompoundFind.php';
        $command = new FileMaker_Command_CompoundFind($this, $layout);

        return $command;

    }

    function &newFindRequest($layout)
    {
        require_once dirname(__FILE__) . '/../Command/FindRequest.php';
        $command = new FileMaker_Command_FindRequest($this, $layout);

        return $command;

    }

    function &newFindAnyCommand($layout)
    {
        require_once dirname(__FILE__) . '/../Command/FindAny.php';
        $command = new FileMaker_Command_FindAny($this, $layout);

        return $command;
    }

    function &newFindAllCommand($layout)
    {
        require_once dirname(__FILE__) . '/../Command/FindAll.php';
        $command = new FileMaker_Command_FindAll($this, $layout);

        return $command;
    }

    function &newPerformScriptCommand($layout, $script_name, $parameters = null)
    {
        require_once dirname(__FILE__) . '/../Command/PerformScript.php';
        $command = new FileMaker_Command_PerformScript($this, $layout, $script_name, $parameters);

        return $command;
    }

    function &createRecord($layout, $values = array())
    {
        $layout =& $this->getLayout($layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $record = new $this->configuration['recordClass']($layout);
        if (is_array($values)) {
            foreach ($values as $field_name => $value) {
                if (is_array($value)) {
                    foreach ($value as $index => $line_value) {
                        $record->setField($field_name, $line_value, $index);
                    }
                } else {
                    $record->setField($field_name, $value);
                }
            }
        }

        return $record;
    }

    function &getRecordById($layout, $record_id)
    {
        $find_command =& $this->newFindCommand($layout);
        $find_command->setRecordId($record_id);
        $result =& $find_command->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $records =& $result->getRecords();
        if ( ! $records) {
            $error = new FileMaker_Error($this,
                'Record . ' . $record_id . ' not found in layout "' . $layout . '".');

            return $error;
        }

        return $records[0];
    }

    function &getLayout($layout_name)
    {
        static $layouts = array();
        if (isset($layouts[$layout_name])) {
            return $layouts[$layout_name];
        }
        $result = $this->_execute(array(
            '-db'   => $this->getProperty('database'),
            '-lay'  => $layout_name,
            '-view' => true
        ));
        if (FileMaker::isError($result)) {
            return $result;
        }
        $resultset = new FileMaker_Parser_FMResultSet($this);
        $result = $resultset->parse($result);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layout = new FileMaker_Layout($this);
        $result = $resultset->setLayout($layout);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layouts[$layout_name] =& $layout;

        return $layout;
    }

    function listDatabases()
    {
        $result = $this->_execute(array('-dbnames' => true));
        if (FileMaker::isError($result)) {
            return $result;
        }
        $resultset = new FileMaker_Parser_fmresultset($this);
        $result = $resultset->parse($result);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $databases = array();
        foreach ($resultset->databases as $database) {
            $databases[] = $database['fields']['DATABASE_NAME'][0];
        }

        return $databases;
    }

    function listScripts()
    {
        $result = $this->_execute(array(
            '-db'          => $this->getProperty('database'),
            '-scriptnames' => true
        ));
        if (FileMaker::isError($result)) {
            return $result;
        }
        $resultset = new FileMaker_Parser_FMResultSet($this);
        $result = $resultset->parse($result);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $scripts = array();
        foreach ($resultset->databases as $database) {
            $scripts[] = $database['fields']['SCRIPT_NAME'][0];
        }

        return $scripts;
    }

    function listLayouts()
    {
        $result = $this->_execute(array(
            '-db'          => $this->getProperty('database'),
            '-layoutnames' => true
        ));
        if (FileMaker::isError($result)) {
            return $result;
        }
        $resultset = new FileMaker_Parser_FMResultSet($this);
        $result = $resultset->parse($result);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layouts = array();
        foreach ($resultset->databases as $database) {
            $layouts[] = $database['fields']['LAYOUT_NAME'][0];
        }

        return $layouts;
    }

    function getContainerData($url)
    {
        if ( ! function_exists('curl_init')) {
            return new FileMaker_Error($this, 'cURL is required to use the FileMaker API.');
        }
        if (strncasecmp($url, '/fmi/xml/cnt', 11) != 0) {
            return new FileMaker_Error($this, 'getContainerData() does not support remote containers');
        } else {
            $container_url = $this->getProperty('hostspec');
            if (substr($container_url, -1, 1) == '/') {
                $container_url = substr($container_url, 0, -1);
            }
            $container_url .= $url;
            $container_url = htmlspecialchars_decode($container_url);
            $container_url = str_replace(" ", "%20", $container_url);
        }
        $this->log('Request for ' . $container_url, FILEMAKER_LOG_INFO);
        $ch = curl_init($container_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $has_headers = false;
        if ( ! headers_sent()) {
            $has_headers = true;
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        $this->_setCurlWPCSessionCookie($ch);

        if ($this->getProperty('username')) {
            $auth = base64_encode($this->getProperty('username') . ':' . $this->getProperty('password'));
            $headers = [
                'Authorization: Basic ' . $auth,
                'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='
            ]);
        }
        if ($curl_options = $this->getProperty('curlOptions')) {
            foreach ($curl_options as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }
        $result = curl_exec($ch);
        $this->_setClientWPCSessionCookie($result);
        if ($has_headers) {
            $result = $this->_eliminateContainerHeader($result);
        }
        $this->log($result, FILEMAKER_LOG_DEBUG);
        if ($curl_errno = curl_errno($ch)) {
            return new FileMaker_Error($this, 'Communication Error: (' . $curl_errno . ') ' . curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }

    function _execute($parameters, $filename = 'fmresultset')
    {
        if ( ! function_exists('curl_init')) {
            return new FileMaker_Error($this, 'cURL is required to use the FileMaker API.');
        }
        $request_options = array();
        foreach ($parameters as $key => $parameter) {
            if (strtolower($this->getProperty('charset')) != 'utf-8' && $parameter !== true) {
                $parameter = utf8_encode($parameter);
            }
            $request_options[] = urlencode($key) . ($parameter === true ? '' : '=' . urlencode($parameter));
        }
        $url = $this->getProperty('hostspec');
        if (substr($url, -1, 1) != '/') {
            $url .= '/';
        }
        $url .= 'fmi/xml/' . $filename . '.xml';
        $this->log('Request for ' . $url, FILEMAKER_LOG_INFO);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $has_headers = false;
        if ( ! headers_sent()) {
            $has_headers = true;
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        $this->_setCurlWPCSessionCookie($ch);

        if ($this->getProperty('username')) {
            $auth = base64_encode(utf8_decode($this->getProperty('username')) . ':' . utf8_decode($this->getProperty('password')));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw==',
                'Authorization: Basic ' . $auth,
            ));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='
            ));
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $request_options));
        if ($curl_options = $this->getProperty('curlOptions')) {
            foreach ($curl_options as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }
        $result = curl_exec($ch);
        $this->_setClientWPCSessionCookie($result);
        if ($has_headers) {
            $result = $this->_eliminateXMLHeader($result);
        }
        $this->log($result, FILEMAKER_LOG_DEBUG);
        if ($curl_errno = curl_errno($ch)) {

            if ($curl_errno == 52) {
                return new FileMaker_Error($this,
                    'Communication Error: (' . $curl_errno . ') ' . curl_error($ch) . ' - The Web Publishing Core and/or FileMaker Server services are not running.',
                    $curl_errno);
            } else if ($curl_errno == 22) {
                if (stristr("50", curl_error($ch))) {
                    return new FileMaker_Error($this,
                        'Communication Error: (' . $curl_errno . ') ' . curl_error($ch) . ' - The Web Publishing Core and/or FileMaker Server services are not running.',
                        $curl_errno);
                } else {
                    return new FileMaker_Error($this,
                        'Communication Error: (' . $curl_errno . ') ' . curl_error($ch) . ' - This can be due to an invalid username or password, or if the FMPHP privilege is not enabled for that user.',
                        $curl_errno);
                }
            } else {
                return new FileMaker_Error($this, 'Communication Error: (' . $curl_errno . ') ' . curl_error($ch),
                    $curl_errno);
            }
        }
        curl_close($ch);

        return $result;
    }

    function getContainerDataURL($url)
    {
        if (strncasecmp($url, '/fmi/xml/cnt', 11) != 0) {
            $container_url = htmlspecialchars_decode($url);
        } else {
            $container_url = $this->getProperty('hostspec');
            if (substr($container_url, -1, 1) == '/') {
                $container_url = substr($container_url, 0, -1);
            }
            $container_url .= $url;
            $container_url = htmlspecialchars_decode($container_url);
        }

        return $container_url;
    }

    function _setCurlWPCSessionCookie($ch)
    {
        if (isset($_COOKIE["WPCSessionID"])) {
            $cookie_value = $_COOKIE["WPCSessionID"];
            if ( ! is_null($cookie_value)) {
                curl_setopt($ch, CURLOPT_COOKIE, "WPCSessionID=" . $cookie_value);
            }
        }
    }

    function _setClientWPCSessionCookie($cookies)
    {
        $result = preg_match('/WPCSessionID=(\d+?);/m', $cookies, $matches);
        if ($result) {
            setcookie("WPCSessionID", $matches[1]);
        }
    }

    function _getContentLength($headers)
    {
        $result = preg_match('/Content-Length: (\d+)/', $headers, $matches);
        if ($result) {
            return $matches[1];
        } else {
            return -1;
        }
    }

    function _eliminateXMLHeader($result)
    {
        $position = strpos($result, "<?xml");
        if ($position !== false) {
            return substr($result, $position);
        } else {
            return $result;
        }
    }

    function _eliminateContainerHeader($result)
    {
        $offset = strlen("\r\n\r\n");
        $position = strpos($result, "\r\n\r\n");
        if ($position !== false) {
            return substr($result, $position + $offset);
        } else {
            return $result;
        }
    }
}
