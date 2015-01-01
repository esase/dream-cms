<?php
namespace Application\Utility;

use Application\Exception\ApplicationException;
use Exception;

class ApplicationFtp
{
    /**
     * Host
     * @var string
     */
    protected $host;

    /**
     * Port
     * @var integer
     */
    protected $port;

    /**
     * Login
     * @var string
     */
    protected $login;

    /**
     * Password
     * @var string
     */
    protected $password;

    /**
     * Is windows
     * @var boolean
     */
    protected $isWindows;

    /**
     * Connection
     * @var resource|boolean
     */
    protected $connection = false;

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
     * Class constructor
     *
     * @param string $host
     * @param string $login
     * @param string $password
     * @thows ApplicationException
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
     * @thows ApplicationException
     * @return void
     */
    public function createDirectory($ftpDir)
    {
        // create a directory
        if (false === ($result =
                @ftp_mkdir($this->connection, $ftpDir))) {

            throw new ApplicationException('Create dir "' . $ftpDir. '" failed');
        }

        // set permissions
        if (!$this->isWindows) {
            if (false === ($result =
                    @ftp_chmod($this->connection, self::DIR_PERMISSIONS, $ftpDir))) {

                throw new ApplicationException('Set permissions of "' . $ftpDir . '" failed');
            }
        }
    }

    /**
     * Put file
     *
     * @param string $localFile
     * @param string $ftpFile
     * @thows ApplicationException
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
                if (null != ($listFiles = @ftp_nlist($this->connection, $ftpDir))) {
                    foreach($listFiles as $file) {
                        $this->removeDirectory($file);
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