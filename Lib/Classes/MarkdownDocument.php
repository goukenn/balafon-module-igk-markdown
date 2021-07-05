<?php

namespace igk\Markdown;
use \IGKHtmlItem; 
use ReflectionClass;

class MarkdownDocument  extends MarkdownNode{
    protected $html_tagname = "div";
    public function __construct(){ 
        parent::__construct("markdown-document");
    }
    public function render_output($output=1){
        $s = "";
        $options =  igk_createobj([
            "Engine" => new MarkdownEngine(igk_app()->getDoc()->Theme)
        ]);
        ob_start();
        $s .= igk_html_render_node($this, $options);       
        if($output){
            echo $s;
        }
        return $s;
    }

   
}