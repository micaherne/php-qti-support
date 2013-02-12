<?php

/*
 * Check the QTI XSD for elements which do not have either a corresponding
 * object in PHPQTI\Runtime\Element or a closure generating function in
 * PHPQTI\Runtime\FunctionGenerator.php
 * 
 * Requires a copy of the QTI 2.1 final bundle in qtiv2p1
 */

require_once 'vendor/autoload.php';

use PHPQTI\Runtime\FunctionGenerator;

$fg = new FunctionGenerator();

// Identity elements (i.e. ones where we're happy to recreate the XML as HTML verbatim
$x = array(
	// Text elements
	'abbr', 'acronym', 'address', 'blockquote', 'br', 'cite', 'code', 'dfn',
	'div', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'kbd', 'p', 'pre', 'q', 'samp',
	'span', 'strong', 'var',
	// List elements
	'dl', 'dt', 'dd', 'ol', 'ul', 'li',
	// Presentation elements
	'b', 'big', 'hr', 'i', 'small', 'sub', 'sup', 'tt',
	// Table elements
	'caption', 'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th',
	'thead', 'tr',
	// Hypertext element
	'a'
);

$xsd = new DOMDocument();
$xsd->load('qtiv2p1/XSDschemas/imsqti_v2p1.xsd');

$elements = $xsd->getElementsByTagNameNS("http://www.w3.org/2001/XMLSchema", "element");
$covered = 0;
$notcovered = array();
foreach($elements as $element) {
	$name = $element->getAttribute('name');
	if (empty($name)) {
		continue;
	}
	// echo "$name: ";
	if (in_array($name, $x)) {
		$covered++;
	} else if(class_exists("PHPQTI\\Runtime\\Element\\" . ucfirst($name))) {
		// echo "class";
		$covered++;
	} else if (method_exists($fg, '_' . $name)) {
		// echo "method";
		$covered++;
	} else {
		// echo "NOT FOUND";
		$notcovered[$name] = 1;
	}
	
	//echo "\n";
			
}

// We're seeing repetitions of certain elements, so this avoids that
foreach(array_keys($notcovered) as $name) {
	echo "$name\n";
}
$notcoveredcount = count(array_keys($notcovered));

echo "\nCovered: $covered\nNot covered: $notcoveredcount\n";
