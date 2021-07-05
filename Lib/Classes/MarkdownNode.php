<?php

namespace igk\Markdown;
use \IGKHtmlItem; 
use ReflectionClass;

abstract class MarkdownNode  extends IGKHtmlItem{

    protected $html_tagname;

    protected static function _GetTagName($node)
    {
        if ($node instanceof MarkdownNode) {
            if (!empty($c = $node->html_tagname)) {
                return $c;
            }
        }
        return $node->getTagName();
    }
    public function isCloseTag($n){
        $t = self::_GetTagName($this);
        return $n == $t;
    }
    public function __construct($tagname=null){
        if ($tagname==null){
            $tagname = "markdown-".str_replace("\\", "-", strtolower(static::class));
        }
        parent::__construct($tagname);
    }

    public static function CreateWebNode($name, $attributes = null, $indexOrArgs = null)
    {
        
        if (class_exists($cl = __NAMESPACE__."\\Markdown".ucfirst($name)."Node") && !(new ReflectionClass($cl))->isAbstract()){
            if ($indexOrArgs==null){
                $indexOrArgs = [];
            }
            $o = new $cl(...$indexOrArgs);
            if ($attributes)
            $o->setAttribute($attributes); 
            return $o;
        }        
        $o = IGKHtmlItem::CreateWebNode($name, $attributes, $indexOrArgs);
        $o->setTempFlag("RootNS", [static::class, __FUNCTION__]);
        return $o;
    }
}