<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Utility;

use Application\Exception\ApplicationException;
use Exception;

class ApplicationFtp
{
    /**
     * Default ftp port
     */
    const DEFAULT_FTP_PORT = 21;

    /**
     * Default timeout
     */
    const DEFAULT_TIMEOUT = 5;

    /**
     * Dir permissions
     */
    const DIR_PERMISSIONS = 0755;

    /**
     * File permissions
     */
    const FILE_PERMISSIONS = 0644;

    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * Port
     *
     * @var integer
     */
    protected $port;

    /**
     * Login
     *
     * @var string
     */
    protected $login;

    /**
     * Password
     *
     * @var string
     */
    protected $password;

    /**
     * Is windows
     *
     * @var boolean
     */
    protected $isWindows;

    /**
     * Connection
     *
     * @var resource|boolean
     */
    protected $connection = false;

    /**
     * Class constructor
     *
     * @param string $host
     * @param string $login
     * @param string $password
     * @param integer $port
     * @param integer $timeout
     * @throws \Application\Exception\ApplicationException
     */
    public function __construct($host, $login, $password, $port = self::DEFAULT_FTP_PORT, $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;

        // try to connect
        if (false === ($this->connection = @ftp_connect($host, $port, $timeout))) {
            throw new ApplicationException('Cannot connect to ftp host');
        }

        // ty to login
        if (true !== ($result = @ftp_login($this->connection, $this->login, $this->password))) {
            throw new ApplicationException('Ftp login failed');
        }

        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->connection) {
            ftp_close($this->connection);
        }
    }

    /**
     * Is directory exists
     *
     * @param string $dir
     * @return boolean
     */
    public function isDirExists($dir)
    {
        $currentDir = ftp_pwd($this->connection);

        if ($currentDir !== false && @ftp_chdir($this->connection, $dir)) {
            ftp_chdir($this->connection, $currentDir);

            return true;
        }

        return false;
    }

    /**
     * Create directory
     *
     * @param string $ftpDir
     * @param boolean $skipExisting
     * @throws \Application\Exception\ApplicationException
     * @return void
     */
    public function createDirectory($ftpDir, $skipExisting = false)
    {
        if ($skipExisting && $this->isDirExists($ftpDir)) {
            return;
        }

        // create a directory
        if (false === ($result = @ftp_mkdir($this->connection, $ftpDir))) {
            throw new ApplicationException('Create dir "' . $ftpDir. '" failed');
        }

        // set permissions
        if (!$this->isWindows) {
            if (false === ($result = @ftp_chmod($this->connection, self::DIR_PERMISSIONS, $ftpDir))) {
                throw new ApplicationException('Set permissions of "' . $ftpDir . '" failed');
            }
        }
    }

    /**
     * Put file
     *
     * @param string $localFile
     * @param string $ftpFile
     * @throws \Application\Exception\ApplicationException
     * @return void
     */
    public function putFile($localFile, $ftpFile)
    {
        // put the files
        if (false === ($result = @ftp_put($this->
                connection, $ftpFile, $localFile, FTP_BINARY))) {

            throw new ApplicationException('Upload of "' . $ftpFile . '" failed');
        }

        // set permissions
        if (!$this->isWindows) {
            if (false === ($result = @ftp_chmod($this->
                    connection, self::FILE_PERMISSIONS, $ftpFile))) {

                throw new ApplicationException('Set permissions of "' . $ftpFile . '" failed');
            }
        }
    }

    /**
     *  Remove directory
     *
     * @param string $ftpDir
     * @return boolean|string
     */
    public function removeDirectory($ftpDir)
    {
        try {
            // here we attempt to delete the file/directory
            if (false === ($result = @ftp_rmdir($this->connection, $ftpDir)) 
                    && false === ($result = @ftp_delete($this->connection, $ftpDir))) {

                // perhaps it's a not empty directory    
                if (null != ($listFiles = @ftp_nlist($this->connection, '-a ' . $ftpDir))) {
                    foreach($listFiles as $file) {
                        $baseFileName = basename($file);

                        if ($baseFileName != '.' && $baseFileName != '..') {
                            $this->removeDirectory($file);    
                        }                        
                    }

                    //removeDirectory
                    if (false === ($result = @ftp_rmdir($this->connection, $ftpDir)))  {
                        throw new ApplicationException('Remove the directory "' . $ftpDir. '" failed');
                    }
                }
                else {
                    throw new ApplicationException('Remove the directory "' . $ftpDir. '" failed');
                }
            }
        }
        catch (Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Copy directory
     *
     * @param string $directory
     * @param string $ftpDirectory
     * @return boolean|string
     */
    public function copyDirectory($directory, $ftpDirectory)
    {
        $result = true;

        try {
            $readDirectory = dir($directory);

            // do this for each file in the directory
            while($file = $readDirectory->read()) {
                // to prevent an infinite loop
                if ($file != '.' && $file != '..') {
                    // do the following if it is a directory
                    if (is_dir($directory . '/' . $file)) {
                        // create directories that do not yet exist
                        if (false === ($result = $this->isDirExists($ftpDirectory . '/' . $file))) {
                            $this->createDirectory($ftpDirectory . '/' . $file);
                        }

                        // recursive part
                        $this->copyDirectory($directory . '/' . $file, $ftpDirectory . '/' . $file); 
                    }
                    else {
                        $this->putFile($directory . '/' . $file, $ftpDirectory . '/' . $file);
                    }
                }
            }
        }
        catch (Exception $e) {
            $result = $e->getMessage();
        }

        $readDirectory->close();

        return $result;
    }
}