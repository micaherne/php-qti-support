<?php

use PHPQTI\Runtime\AssessmentItemController;

use PHPQTI\Util\ObjectFactory;

/*
 * Engine is a front controller which:
 * 
 * 1. Accepts uploaded files and creates controllers for them
 * 2. Accepts an identifier for an item and instantiates a controller for it
 * 3. Proxies resource files
 * 
 * In theory this should be able to display multiple items on the same page and
 * their controllers should be able to understand whether that particular item instance
 * has been submitted (multiple instances of same item should be allowed)
 */

require_once 'config.php';

use PHPQTI\Runtime\ResourceProvider;
use PHPQTI\Runtime\Impl\SessionPersistence;
use PHPQTI\Runtime\Impl\HttpResponseSource;
use PHPQTI\Runtime\Exception\NotImplementedException;

/**
 * Given a relative URL such as 'images/sign.png' will provide an absolute URL
 * that will serve the given resource.
 * @author Michael
 *
 */
class SimpleResourceProvider implements ResourceProvider {
    // This class isn't part of core.php as there isn't really a sensible
    // generic implementation - it really needs to be implemented by the application

    public $script;
    public $item;
    public $package;
    public $itemid;

    public function __construct($script, $item) {
        $this->script = $script;
        $this->item = $item;
        list($package, $itemid) = explode('/', $item);
        $this->package = $package;
        $this->itemid = $itemid;
    }

    public function urlFor($relativePath) {
        return $this->script . '?resource=true&item=' . urlencode($this->item) . '&path=' . urlencode($relativePath);
    }

}

$item = $_GET['item'];
list($package, $itemid) = explode('/', $item);

// If it's a request for a resource, serve it
if (isset($_GET['resource'])) {
    if (!isset($_GET['path'])) {
        header("HTTP/1.0 400 Bad request");
        die('Path required'); // TODO: Should be bad request header
    }
    $path = "$datadir/{$package}/" . $_GET['path'];
    if (!file_exists($path)) {
        header("HTTP/1.0 404 Not found");
        die("$path Not found");
    }
    
    /*
     * Try to determine certain file types from extension rather
     * than relying on mime magic, as e.g. css files don't work 
     * in some browsers if they aren't served with proper type.
     */
    $mimetype = null;
    if ($ext = pathinfo($path, PATHINFO_EXTENSION)) {
        switch ($ext) {
            case 'css':
                $mimetype = 'text/css';
                break;
        }
    }
    
    if (is_null($mimetype)) {
        $finfo = new finfo(FILEINFO_MIME);
        $mimetype = $finfo->file($path);
    }
    
    header("Content-Type: $mimetype");
    readfile($path);
    exit;
}

$item_file = "$datadir/{$package}/{$itemid}.php";

$item_class = "{$itemid}";

require_once $item_file;

$factory = new $item_class();
$assessmentItem = $factory->getInstance();
$controller = new AssessmentItemController($assessmentItem);

try {
    $controller = new AssessmentItemController($assessmentItem);
} catch (NotImplementedException $e) {
    die("Unable to run as this item contains an unimplemented element: " . $e->elementName);
}
$controller->setPersistence(new SessionPersistence());
$controller->setResponseSource(new HttpResponseSource());
$controller->setResourceProvider(new SimpleResourceProvider($_SERVER['SCRIPT_NAME'], $item));

$controller->show_debugging = true;

// $controller->run is called in view.php in the correct place

