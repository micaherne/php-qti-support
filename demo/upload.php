<?php

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

use PHPQTI\Compile\ItemCompiler;

if($_FILES) {
    // Deal with uploaded files
    
    // TODO: Implement this sensibly
    // Create a folder
    $folderno = 0;
    while (file_exists($datadir . '/' . $folderno)) {
        $folderno++;
    }
    
    $basedir = $datadir . '/' . $folderno;
    mkdir($basedir);
    // foreach not really necessary but easier
    foreach($_FILES as $file) {
        $filepath = $basedir . '/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $filepath);
        $uploadedfileinfo = pathinfo($filepath);
        switch ($uploadedfileinfo['extension']) {
            case 'xml':
                // Generate a controller file
                $filename = $uploadedfileinfo['filename'];
                $dom = new DOMDocument();
                $dom->load($uploadedfileinfo['dirname'] . '/' . $uploadedfileinfo['basename']);
                $gen = new ItemCompiler($dom);
                $out = fopen($uploadedfileinfo['dirname'] . '/' . "{$filename}_controller.php", 'w');
                fputs($out, $gen->generate_controller($filename));
                fclose($out);
                                
                header('Location: index.php');
                break;
            case 'zip':
                echo "Content packaged items not implemented";
                break;
            default:
                echo "ERROR $uploadedfiletype";
                break;
        }
    }
    exit;
}