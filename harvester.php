<?php
/**
 * This file fetches some parameters from the command line, and passes
 * them to the Harvester class before executing it.
 *
 * PHP version 5
 *
 * @category  Camera
 * @package   Harvester
 * @author    Jachim Coudenys <jachimcoudenys@gmail.com>
 * @copyright 2010 Jachim Coudenys <jachimcoudenys@gmail.com>
 * @link      http://github.com/coudenysj/camera-harvester
 */

/**
 * The Harvester class fetches all images and videos (extensions can be added)
 * from a directory (possible Camera) and copies them to a time based
 * directory system.
 *
 * @category  Camera
 * @package   Harvester
 * @author    Jachim Coudenys <jachimcoudenys@gmail.com>
 * @copyright 2010 Jachim Coudenys <jachimcoudenys@gmail.com>
 * @version   Release: @package_version@
 * @link      http://github.com/coudenysj/camera-harvester
 */
class Harvester
{

    /**
     * The list of extensions to harvest.
     *
     * @var array
     */
    private $_extensions = array('jpg', 'avi');

    /**
     * The directory where to copy from.
     *
     * @var DirectoryIterator
     */
    private $_fromDirectory;

    /**
     * The directory where to copy to (and create time based directory).
     *
     * @var string
     */
    private $_toDirectory;

    /**
     * Create an instance of the Harvester class.
     *
     * @param string  $from      The directory to copy from
     * @param string  $to        The directory to copy to
     * @param boolean $recursive The flag to process the from
     *                           directory recursively
     *
     * @return unknown
     */
    public function __construct($from, $to, $recursive = false)
    {
        if (!is_dir($from)) {
            throw new InvalidArgumentException(
                '$from \'' . $from .'\' is not a directory'
            );
        }
        if (!is_dir($to)) {
            throw new InvalidArgumentException(
                '$to \'' . $to .'\' is not a directory'
            );
        }
        if ($recursive) {
            $this->_fromDirectory = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($from)
            );
        } else {
            $this->_fromDirectory = new DirectoryIterator($from);
        }
        $this->_toDirectory = realpath($to);
        if (extension_loaded('exif')) {
            echo 'EXIF extension found' . PHP_EOL;
        }
    }

    /**
     * Start to copy all files in the correct directories.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->_fromDirectory as $file) {
            $extension = strtolower(
                pathinfo($file->getPathName(), PATHINFO_EXTENSION)
            );
            if ($file->isFile() && in_array($extension, $this->_extensions)) {
                $source = $file->getPathName();
                $target = $this->_toDirectory;
                if (   extension_loaded('exif')
                    && ($exif = exif_read_data($source))
                    && isset($exif['FileDateTime'])
                ) {
                    $time = $exif['FileDateTime'];
                } else {
                    $time = $file->getCTime();
                }
                $target .= date('/Y/m/d', $time);
                if (!is_dir($target)) {
                    if (!mkdir($target, null, true)) {
                        throw new Exception(
                            'Cannot create directory \'' . $target . '\''
                        );
                    }
                }
                $fileCounter = 1;
                do {
                    $targetFile = $target . date('/His', $time);
                    if ($fileCounter > 1) {
                        $targetFile .= '-' . $fileCounter;
                    }
                    $targetFile .= '.' . $extension;
                    $fileCounter++;
                } while (is_file($targetFile));
                if (!copy($source, $targetFile)) {
                    throw new Exception(
                        'Cannot copy \'' . $source . '\''
                    );
                }
                echo 'Created ' . realpath($targetFile) . PHP_EOL;
            }
        }
    }
}

if ($argc < 3) {
    echo 'Usage:' . PHP_EOL;
    echo ' ' . $argv[0] . ' from-dir to-dir';
    die();
} else {
    $harvester = new Harvester($argv[1], $argv[2], true);
    $harvester->run();
}