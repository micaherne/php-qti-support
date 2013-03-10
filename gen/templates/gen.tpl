<?php
 
namespace PHPQTI\Model\Gen;

use PHPQTI\Model\Base\AbstractClass;
 
class {{classname}} extends AbstractClass {

    protected $_elementName = '{{elementname}}';

{% for attr in attrs %}
    public ${{attr.attribute("name")}};
{% endfor %}
{% if code is defined %}
    {{code|raw}}{% endif %}

}