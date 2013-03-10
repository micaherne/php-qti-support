<?php
 
require '../vendor/twig/twig/lib/Twig/Autoloader.php';
 
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
    private $outputdir = '../src/PHPQTI/Model/Gen';
    private $reservedNames = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');
    
    public function __construct($outputdir = null) {
        if (!is_null($outputdir)) {
            $this->outputdir = $outputdir;
        }
        
        Twig_Autoloader::register();
        
        $loader = new Twig_Loader_Filesystem('templates');
        $this->twig = new Twig_Environment($loader, array(
                //'cache' => 'templates/cache',
        ));
        
        $this->elementTemplate = $this->twig->loadTemplate('gen.tpl');
    }
    
    public function generateElements() {
        $this->dom = new DOMDocument();
        
        $this->dom->load('../qtiv2p1/XSDschemas/imsqti_v2p1.xsd');
                
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
            
            $out = fopen("$this->outputdir/$classname.php", 'w');
            
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
                       
           fputs($out, $this->elementTemplate->render(array('classname' => $classname, 'attrs' => $attrs, 'elementname' => $name)));
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
 
$m = new ModelGenerator();
$m->generateElements();