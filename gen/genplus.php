<?php
 
use PHPQTI\Runtime\FunctionGenerator;

require '../vendor/twig/twig/lib/Twig/Autoloader.php';
require '../vendor/autoload.php';
 
/*
 * Generate a very basic class model.
 * 
 * NB - this is not intended as a general purpose tool, only for
 * the QTI spec.
 * 
 */
 
// TODO: Ignores non QTI NS attributes (e.g. xml:lang)
// TODO: No check for invalid variable names
 
class ModelGenerator {
    
    private $elementTemplate;
    private $reservedNames = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');
    
    public function __construct() {
        Twig_Autoloader::register();
        
        $loader = new Twig_Loader_Filesystem('templates');
        $this->twig = new Twig_Environment($loader, array(
                //'cache' => 'templates/cache',
        ));
        
        $this->elementTemplate = $this->twig->loadTemplate('element.php');
    }
    
    public function generateElements() {
        $this->dom = new DOMDocument();
        
        $this->dom->load('../qtiv2p1/XSDschemas/imsqti_v2p1.xsd');
        
        $outputdir = 'output';
        
        // get complex types
        $complexTypes = $this->dom->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'complexType');
        foreach($complexTypes as $complexType) {
            $name = $complexType->attributes->getNamedItem('name');
            $this->complexTypes[$name->nodeValue] = $complexType;
        }
        
        // get attribute groups
        $attributeGroups = $this->dom->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'attributeGroup');
        foreach($attributeGroups as $attributeGroup) {
            $name = $attributeGroup->getAttribute('name');
            if (!empty($name)) {
                $this->attributeGroups[$name] = $attributeGroup;
            }
        }
        
        // get all elements
        $elements = $this->dom->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'element');
        
        // foreach element, find the type
        foreach($elements as $element) {
 
            $name = $element->getAttribute('name');
            // ignore element references
            if (empty($name)) {
                continue;
            }
            $elementType = $element->getAttribute('type');
            
            if (in_array($name, $this->reservedNames)) {
                $classname = 'QTI' . ucfirst($name);
            } else {
                $classname = ucfirst($name);
            }
            
            try {
                if (method_exists('PHPQTI\Runtime\FunctionGenerator', "_$name")) {
                    $code = FunctionGeneratorParser::elementCode($name);
                } else {
                    $code = null;
                    echo "Function not found for $name\n";
                }
            } catch (Exception $e) {
                echo "Couldn't parse function for $name\n";
                $code = null;
            }
            
            $out = fopen("$outputdir/$classname.php", 'w');
            
            $attrs = array();
            
            if (isset($this->complexTypes[$elementType])) {
                $type = $this->complexTypes[$elementType];
 
                $attributeGroups = $type->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'attributeGroup');
                foreach($attributeGroups as $attributeGroup) {
                    $ref = $attributeGroup->getAttribute('ref');
                    $attributes = $this->getAttributes($ref);
                    foreach($attributes as $attribute) {
                        $attrName = $attribute->getAttribute('name');
                        if (!empty($attrName)) {
                            $attrs[] = $attribute;
                        }
                    }
                }
            
           } else {
               echo "\n$elementType not found\n";
           }
                       
           fputs($out, $this->elementTemplate->render(array('classname' => $classname, 'attrs' => $attrs, 'elementname' => $name, 'code' => $code)));
           fclose($out);
        }
    }
    
    private function getAttributes($attributeGroupName) {
        if (!isset($this->attributeGroups[$attributeGroupName])) {
            return null;
        }
        
        $result = array();
        $attributeGroup = $this->attributeGroups[$attributeGroupName];
        $attributes = $attributeGroup->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'attribute');
        foreach($attributes as $attribute) {
            $result[] = $attribute;
        }
        
        return $result;
    }
    
}


require_once('../vendor/micaherne/php-qti/src/PHPQTI/Runtime/FunctionGenerator.php');


class FunctionGeneratorParser {
    
    public static function elementCode($name) {
        $fg = new FunctionGenerator();
        $func = new ReflectionMethod('PHPQTI\Runtime\FunctionGenerator', "_$name");
        $filename = $func->getFileName();
        $start_line = $func->getStartLine(); // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line - 1;
        
        $source = file($filename);
        //$body = implode("", array_slice($source, $start_line, $length));
        $result = '';
        $funcfound = false;
        foreach(array_slice($source, $start_line, $length) as $line) {
            // if (preg_match('/return\w+function\w+\(\$controller\)\w+use\w+\(\$attrs, \$children\)/', $line)) {
            if (preg_match('/return\s+function\s*\((ItemController\s*)?\$controller\)\s*use\s*/', $line)) {
                $l = 'public function __invoke($controller) {' . "\n";
                $funcfound = true;
            } else {
                $l = $line;
            }
            
            $l = str_replace('$children', '$this->_children', $l);
            $l = preg_replace('/\$attrs\[\'(.*?)\'\]/', '$this->$1', $l);
            $l = str_replace('->__invoke', '', $l);
            
            // formatting
            $l = preg_replace('/^ {8}/', '    ', $l);
            $result .= $l;
        }
        
        if (!$funcfound) {
            // it's probably not implemented
            try {
                $funcname = "_$name";
                $fg->$funcname(null, null);

                echo "Null returned for $name\n";
            } catch (PHPQTI\Runtime\Exception\NotImplementedException $e) {
                // nothing - it's fine
            }
            return null;
        }
        // get rid of the trailing semi-colon
        $result = preg_replace('/;\s*$/', '', $result);
        
        return $result;
    }
    
}

//echo FunctionGeneratorParser::elementCode('setOutcomeValue');

$m = new ModelGenerator();
$m->generateElements();