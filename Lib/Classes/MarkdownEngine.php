<?php

namespace igk\Markdown;

use IGK\System\Html\RendererEngineBase;
use IGKHtmlStyleValueAttribute;
use IGKHtmlUtils;
use PHPUnit\TextUI\XmlConfiguration\Group;

use function igk_getv as getv;
use function json_parse;
use function Psy\debug;

/**
 * Markdown builder engine with visitor
 * @package igk\Markdown
 */
class MarkdownEngine extends RendererEngineBase
{
    var $visitor; 
    protected static function GetStyles($node, $oldstyle = null)
    {
        if (method_exists($node, "getStyle")) {
            if (($style = $node->getStyle())  && ($style instanceof IGKHtmlStyleValueAttribute)) {

                $styleprop = json_parse("{" . str_replace(";", ", ", $style->getValue()) . "}", false);
                if ($styleprop) {
                    if ($oldstyle) {
                        foreach ($oldstyle as $k => $v) {
                            if (!property_exists($styleprop, $k)) {
                                $styleprop->$k = $v;
                            }
                        }
                    }
                    return $styleprop;
                }
            }
        }
        return $oldstyle;
    }

    protected static function GetRendererStyleItem($node, $options = null)
    {
        $tab = array(["n" => $node, "p" => null]);
        $output = [];
        $ctype = "r";
        $style_match = "/\<([a-z\-]+)\s*/i";
        $s = "";
        $cstyle = null;
        $ostyle = [];
        // $debug = false;
        while ($q = array_shift($tab)) {
            $n = $q["n"];
            $p = $q["p"];
            // igk_wl("n : ".spl_object_id($n)." vs ". ($p ?  spl_object_id($p) : "null") ." \n");
            // $debug = spl_object_id($n) == "1534";

            // igk_wl(spl_object_id($n) . ": data\n" );

            if ($n->getFlag(IGK_NODETYPE_FLAG) == "c") {
                $tagname = $n->tagName;
            } else {
                $tagname = "igk:" . $n->tagName;
            }
            if (method_exists($n, "getHandleRendering") && $n->getHandleRendering()) {
                $output[] = (object) [
                    "tag" => preg_replace("/[:\-]/", "_", $tagname),
                    "data" => null,
                    "node" => $n,
                    "options"=>$options,
                    "q"=>$q
                ];
                continue;
            }

            $href = getv($q, "href", null);
            $ostyle = array_filter(str_split(getv($q, "style", "")));
          switch ($tagname) {
                case "i":
                case "b":
                case "u":
                    $ctype = $tagname;
                    if (!in_array($ctype, $ostyle)) {
                        array_push($ostyle, $ctype);
                    }
                    $ctype = implode("", $ostyle);
                    $ostyle = [];
                    break;
                case "a":
                case "igk:a":
                    // $ctype = $tagname;
                    $href = $n->Attributes["href"];
                    if (isset($q["style"])) {
                        $ctype = $q["style"];
                    }
                    break;
                default:
                    if (isset($q["style"])) {
                        $ctype = $q["style"];
                    }
                    break;
            }
            if (isset($q["css"])) {
                $cstyle = $q["css"];
            }
            $cstyle = self::GetStyles($n, $cstyle);
            $inner = IGKHtmlUtils::GetContentValue($n, $options);


            if (trim($inner) != "") {
                // igk_wln_e("loading inner ".$inner . " $style_match ");
                if (!$n->getIsLitteralContent() && preg_match($style_match, $inner)) {
                
                    $d = igk_createnode("dummy");
                    $d->load($inner, null, [get_class($n), "CreateWebNode"]);
                    if (!empty($v = $d->getContent())) {
                        $s .= $v;
                    }
                    if ($d->getChildCount() > 0) {
                        $bb = array_reverse($d->getChilds()->to_array());
                        
                        foreach ($bb as $dp) {
                            array_unshift($tab, [
                                "n" => $dp, "p" => $n, "style" => $ctype,
                                "css" => $cstyle, "href" => $href, "q"=>$q
                            ]);  
                        }  
                    }
                    $d->clearChilds();
                    $inner = null;
                } else {
                    $s .= $inner;
                }
            }
            if ($n->getChildCount() > 0) {
                $bb = array_reverse($n->getChilds()->to_array()); 
                foreach ($bb as $dp) {
                    array_unshift($tab, ["n" => $dp, "p" => $n, "style" => $ctype,  "css" => $cstyle, "href" => $href,
                    "q"=>$q
                    ]);
                }
            }
           // if (!empty($inner)){                
                $output[] = (object) [
                    "tag" => preg_replace("/[:\-]/", "_", $tagname),
                    "data" => $inner,
                    "type" => $ctype,
                    "style" => $cstyle,
                    "href" => $href,
                    "node" => $n,
                    "p" => $p,
                    "options"=>$options,
                    "endlist"=>1,
                    "q" =>$q
                ];
           // }            
        }
        return $output;
    }
    private function _visiteItem($visitor, $i, &$fc_list, $matches)
    {
        if (!($fc = getv($fc_list, $i->tag))) {
            foreach ($matches as $k => $v) {
                if (preg_match($k, $i->tag)) {
                    $fc = $v;
                    $fc_list[$i->tag] = $fc;
                    break;
                }
            }
        }
        if (empty($fc)) {
            $fc = "visit_" . $i->tag;
        }
        // igk_wl('visit : '.$fc.":". spl_object_id($i->node).":\n");
        return $visitor->$fc($i);
    }
    private static function _RenderGroup($host, & $group, $visitor, & $fc_list, $matches, $emptystr =true){
        $tn = $group[0];
        $ds = "";
        while ($c = array_pop($group)) {
            // igk_wl("bi:".$c->data."\n");
            if ($ds = $host->_visiteItem($visitor, $c, $fc_list, $matches)) {
                if (($ct = count($group)) > 0){
                    //passeing data to parent
                    $group[$ct - 1]->data .= $ds;
                } 
            }
        }
        if ($ds){
            //passing data to parent root
            $tn->data = $ds;
        }
        if ($emptystr) {
            $tn->data = ltrim($tn->data);
        }
       //  igk_wl("rendergroup:".$tn->data."\n");
        return $tn->data;
    }
    public function Render($node, $options = null)
    {
        $items = self::GetRendererStyleItem($node, $options);
        $s = "";
        $visitor = $this->visitor ?? $this;
        $matches = [
            "/h[1-6]$/" => "visit_header",
        ];
        $fc_list = [];
        $q = null;
        $p = [$node];
        $group = []; 
        $rp = [];
        foreach ($items as $i) {
            // igk_wl("render : ".$i->node->__toString()."\n");//." P:".$i->q["p"]->__toString(). ":\ndata: ".$i->data."\n");
            // $debug = (trim($i->data) == "La vie du sommeil");
            $ct  = count($group);
            if (($i->q["p"]) && ($ct>0)){
                //igk_wl( "P: ".$i->q["p"]->__toString() . " \n");
                
                while($ct && ($i->q["p"] !== $group[$ct-1]->node)){
                    $c = array_pop($group);
                    $ct--;
                    $group[$ct-1]->data .= $this->_visiteItem($visitor, $c, $fc_list, $matches);
                }
                // igk_wl( "Q: ".$group[$ct-1]->node->__toString() . " \n");
                
                // if ($i->q["p"] !== 
            }

            if (($i->node->ChildCount >0) || ($i->data === null)){
                array_push($group, $i);
            }else {
                $r = $this->_visiteItem($visitor, $i, $fc_list, $matches);
                // igk_wl("redim : ".$r."\n")  ;
                if ( ($ct  = count($group)) >0){
                    $group[$ct-1]->data .= $r;
                }else{
                    $s .= $r;
                    igk_wln_e("ERROR: ".$s);
                }
                continue;
            }
            // $p = $i->q["p"];
            // if (count($rp) == 0 ){
            //     array_push($rp, $p);
            // }else {
            //     if ($rp[0] === $p){
            //         array_push($group, $i);
            //         igk_wl("group : ". count($group)."\n");
            //         $s .= self::_RenderGroup($this, $group, $visitor, $fc_list, $matches, empty($s));
            //         igk_wl($s."====\n");
            //         igk_wln("end to parent");
            //     } else {
            //         $r = $this->_visiteItem($visitor, $i, $fc_list, $matches);
            //         $group[count($group) -1]->data .= $r;
            //         continue; 
            //     }
            // }
            // array_push($group, $i);


           
            continue;

             // igk_wl(json_encode($i)."\n");
            // if ($i->data === null) {                
            //     array_push($group, $i); 
            //     $ingp = $i;
            //     continue;
            // } else if (count($group) > 0) {
           
            //    // igk_wln("check ? ".  " \n\n");
            //    //  if ($i->data == "partir un "){
            //         // $c = false;
            //         // $tq = $i->q;
            //         // while($tq){
            //         //     if ($tq["p"] === $ingp->node){
            //         //         $c = true;
            //         //         break;
            //         //     }
            //         //     $tq = getv($tq, "q");
            //         // }
            //         // if ($c){
            //         //     igk_wl("almos found\n");
            //         //     array_push($group, $i);
            //         //     continue;                        
            //         // } else {
            //         //     igk_wln_e("render groups");
            //         // }
            //     //     igk_wln_e("stop", 
            //     //     $i->q,
            //     //     $i->q["p"] === $group[0]->node,
            //     //     // $i->q ? get_class($i->q) : null,
            //     //     get_class($group[0]->node));
            //     // }
            //     array_push($group, $i);
            //     $s .= self::_RenderGroup($this, $group, $visitor, $fc_list, $matches, empty($s));

            //     // $tn = $group[0];
            //     // $ds = "";
            //     // while ($c = array_pop($group)) {
            //     //     //igk_wl("bi:\n");
            //     //     if ($ds = $this->_visiteItem($visitor, $c, $fc_list, $matches)) {
            //     //         if (($ct = count($group)) > 0) {
            //     //             $group[$ct - 1]->data = $ds;
            //     //         }
            //     //         // igk_wl("counting : ".$ct."\n");
            //     //     }
            //     // }
            //     // if ($ds) {
            //     //     $tn->data = $ds;
            //     // }
            //     // if (empty($s)) {
            //     //     $tn->data = ltrim($tn->data);
            //     // }
            //     // $s .= $tn->data;
            //     continue;
            // }
            // // if (($i->node->parentNode === null) && ($i->p)) {
            // //     igk_wl("inline item ".$i->p."  \n");
            // //     $s= rtrim($s)." ";
            // // }
            // // //else {
            // //     if ($p[0] === $i->p){
            // //         igk_wl(":::::writing same parent \n");
            // //     } else {
            // //         if ($i->p === $i->node->parentNode){

            // //         }else {
            // //             igk_wl("request grouping :::::: \n");
            // //         }

            // //         igk_wl("changin parent \n");
            // //         array_unshift($p, $i->p );
            // //     }
            // //}
            // $gg = $this->_visiteItem($visitor, $i, $fc_list, $matches);
            // if (empty($s)) {
            //     $gg = ltrim($gg);
            // }
            // $s .= $gg;
        }
        if (count($group)>0)
            $s .= self::_RenderGroup($this, $group, $visitor, $c, $fc_list, $matches, empty($s));
        // igk_wln(count($group));
        // igk_wln_e("output :::::::::::::::::::::::::::::::::::.".$s."\n"); 
        return $s;
    }
    // protected function _visitGroup($visitor, $q, $group, &$fc_list, $matches)
    // {
    //     $d = "";
    //     if ($q && (count($group) > 0)) {
    //         foreach ($group as $i) {
    //             $d .= $this->_visiteItem($visitor, $i, $fc_list, $matches);
    //         }
    //         $q->data = $d;
    //         $d = $this->_visiteItem($visitor, $q, $fc_list, $matches);
    //     }
    //     return $d;
    // }
    protected function visit_i($t)
    {
        if ($t->data === null) {
            return;
        }
        return "*" . $t->data . "*";
    }
    protected function visit_b($t)
    {
        if ($t->data === null) {
            return;
        }
        return "**" . $t->data . "**";
    }
    protected function visit_li($t)
    {
        $s = "";
        $p = $t->node->parentNode;
        switch ($p->getTagName()) {
            case "markdown-task-list":
                $s .= "\n- [";
                $s .= ($t->node["complete"]) ? "x" : " ";
                $s .= "] ";
                break;
        }
        $s .= $t->data;
        return $s;
    }
    protected function visit_markdown_mention($t)
    {
        return "@" . trim($t->data) . " ";
    }
    protected function visit_markdown_code_block($t)
    {
        return implode("\n", ["\n```" . $t->node->Attributes["type"], $t->data, "```\n"]);
    }
    protected function visit_markdown_image($t)
    {
        return "![" . $t->node["alt"] . "](" . $t->node["src"] . ")";
    }

    protected function visit_markdown_table($t)
    {

        $node = $t->node;
        $tc = igk_createnode("notagnode");
        $rows = [];
        $s = "";
        $h = $node->headers();
        $hh = [];
        foreach($h as $i){
            $tc->clearChilds();
            $tc->load($i);
            $hh[] = igk_html_render_node($tc, $t->options );

        }
        $s .= "\n".implode("|", $hh);
        $s .= "\n-" . str_repeat("-|-", count($h) - 1) . "-";
        $rows = $node->rows();
        foreach ($rows as $r) {
  
            $rr = [];
            foreach($r as $i){
                $tc->clearChilds();
                $tc->load($i);
                $rr[] = igk_html_render_node($tc, $t->options );

            }
            $s .= "\n".implode("|", $rr);
        }
        return $s;
    }
    protected function visit_markdown_quotes($t)
    {      
        return "\n" . implode("\n", array_map(function ($v) {
            return "> " . $v;
        }, explode("\n", $t->data)));
    }

    protected function visit_markdown_li($t)
    { 
        $depth = 0;
        $tq = [$t->q];
        
        $p = $t->p;
        // $debug = $t->data === "bool";

        // if ($debug){
        //     igk_wl("detect: ".spl_object_id($t->node)." -&gt; ".spl_object_id($t->q["p"]). " R ".spl_object_id($t->q["q"]["p"])."\n");
        // }
        $cout = 0;
        while($q = array_pop($tq)){
           
            while(isset($q["q"])){      
                $tn = $q["q"]["n"]->getTagName();
                // if ($debug){
                //     igk_wl("tn : $tn ".spl_object_id($q["q"]["n"]). " ".$q["q"]["n"]->render(). " \n");
                // }
                if (preg_match("/^markdown-(ul|ol|quotes)$/",$tn)){
                    $depth++;
                }                
                $q = $q["q"];
                $cout++;
            }
        }
        // if ($debug){
        //     igk_wln_e("count : ".$cout . " depth ".$depth);
        // }
        if ($depth>0){
            $depth--;
        }  
        $c = "* ";
        if ( $p && ($p->getTagName() === "markdown-ol")){
            // get counter 
            $i = 1;
        
            foreach($p->getChilds()->to_array() as $m){
                if ($m===$t->node){
                    break;
                }
                $i++;
            }
            $c = "{$i}. ";
        }
        return "\n".str_repeat("\t", $depth) .$c. $t->data;
    }

    protected function visit_header($t)
    {
        return "\n" . str_repeat("#", substr($t->tag, 1)) . " " . $t->data;
    }

    protected function visit_br($t)
    {
        return "\n" . $t->data;
    }
    protected function visit_a($t)
    {
        return " [" . $t->data . "](" . $t->href . ")";
    }
    protected function visit_p($t)
    {
        return "\n" . $t->data; // ."](".$t->href.")";
    }
    protected function visit_div($t)
    {
        return "\n" . $t->data; // ."](".$t->href.")";
    }
  

    protected function visit_igk_a($t)
    {
        if (is_object($ob = $t->href)) {
            $t->href = $t->href->getValue();
        }
        return $this->visit_a($t);
    }
    protected function visit($t)
    {
        if ($t->data === null) {
            return "";
        }
        return $t->data;
    }


    public function __call($name, $arguments)
    {
        if (strpos($name, "visit") == 0) {
            return $this->visit(...$arguments);
        }
    }
}
