<?php 

namespace igk\Markdown;


class MarkdownTableNode extends MarkdownNode{   
    private $m_headers;
    private $m_rows;

    protected $html_tagname = "table";
    public function __construct(){
        parent::__construct("markdown-table"); 
        $this->m_rows = [];
        $this->m_headers = []; 
    }
    public function getHandleRendering(){
        return true;
    }

    public function Render($options=null){         
        $c = igk_createnode("table");        
        $c->header(...$this->m_headers);
        $c->loop($this->m_rows)->host(function($t, $i){
            $tr = $t->tr();
            foreach($i as $v){
                $tr->td()->Content = $v;
            }
        });
        return $c->render($options);
    }
    /**
     * retrieve headers
     * @return array 
     */
    public function headers(){
        return $this->m_headers;
    }
    /**
     * retrieve row data
     * @return array 
     */
    public function rows(){
        return $this->m_rows;
    }
    public function addRow(array $row){
        $this->m_rows[] = $row;
        return $this;
    }
    public function clearRows(){
        $this->m_rows = [];
        return $this;
    }
    public function setHeader(array $header){
        $this->m_headers = $header;
    }
}