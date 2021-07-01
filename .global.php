<?php
// @file: .global.php
// @author: C.A.D. BONDJE DOUE
// @description: 
// @copyright: igkdev © 2019
// @license: Microsoft MIT License. For more information read license.txt
// @company: IGKDEV
// @mail: bondje.doue@igkdev.com
// @url: https://www.igkdev.com

///<summary>convert md file to html expression </summary>
function git_convert_md_to_html($d, $content){
    $lines=explode("\n", $content);
    $cline=count($lines);
    $p=0;
    $mode="";
    $tab=array();
    $matching_func=function($pattern, $str, $pos, & $expression){
        if(preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE, $pos)){
            $expression=$matches["expression"];
            return 1;
        }
        return 0;
    };
    $expressionlist=array(
            array(
                "pattern"=>"/\`(?P<expression>[^\`]+)\`/i",
                "offset"=>1,
                "express_data"=>"<code class=\"git-code-expression dispib\">{0}</code>"
            ),
            array(
                "pattern"=>"/\*\*(?P<expression>[^\*]+)\*\*/i",
                "offset"=>2,
                "express_data"=>"<b>{0}</b>"
            ),
            array(
                "pattern"=>"/\*(?P<expression>[^\*]+)\*/i",
                "offset"=>1,
                "express_data"=>"<i>{0}</i>"
            ),
            array(
                "pattern"=>"/#(?P<expression>(.)+)$/i",
                "offset"=>1,
                "express_data"=>"<span class=\"git-comment\">{0}</span><br />"
            ),
            
        );
    for($i=0; $i < $cline; $i++){
        $str=trim($lines[$i]);
        array_push($tab, $str);
        $depth=0;
        $marker="";
        while($str=array_pop($tab)){
            $ln=strlen($str);
            if($ln > 0){
                switch($str[0]){
                    case '#':
                    $d->addDiv()->setClass("git-comment")->Content=$str;
                    $p=0;
                    $mode="";
                    break;
                    case '*':
                    if(($ln > 0) && ($str[1] == ' ')){
                        if(!$p || ($mode == ''))
                            $p=$d->add('ul');
                        array_push($tab, substr($str, 2));
                        $mode='li';
                        $depth=1;
                    }
                    else{
                        array_push($tab, substr($str, 1));
                        $marker .= "*";
                    }
                    break;default: 
                    if(!empty($marker)){
                        $str=$marker.$str;
                        $marker="";
                    };
                    if(($c=strpos($str, "```")) !== false){
                        $type="";
                        if($mode != 'code'){
                            $type=substr($str, $c + 3);
                            $mode='code';
                            $p=$d->add('code');
                            $depth=1;
                            $p["class"]=$type;
                        }
                        else{
                            if($c == 0){
                                $mode='';
                                $p=0;
                                $depth=0;
                            }
                            else{
                                $depth=2;
                            }
                        }
                        array_push($tab, substr($str, $c + strlen($type) + 3));
                        if($c > 0)
                            array_push($tab, substr($str, 0, $c));
                        continue;
                    }
                    if(empty($mode) || !preg_match("/^(code)$/i", $mode)){
                        $pos=0;
                        //$matches=0;
                        $out="";
                        $f=1;
                        $r=igk_createObj();
                        while($f && ($pos < $ln)){
                            $f=0;
                            //$src_pos=$pos;
                            foreach($expressionlist as $k){
                                if($matching_func($k["pattern"], $str, $pos, $t) && ((!$f) || ($f && ($t[1] < $r->pos)))){
                                    $offset=$k["offset"];
                                    $r->pos=$t[1];
                                    $r->sub=$t[1] - $pos - $offset;
                                    $r->data=igk_str_format($k["express_data"], $t[0]);
                                    $r->offset=$offset;
                                    $r->newpos=$t[1] + strlen($t[0]) + $offset;
                                    $f=1;
                                }
                            }
                            if($f){
                                $out .= substr($str, $pos, $r->sub).$r->data;
                                $pos=$r->newpos;
                            }
                        }
                        if(!empty($out)){
                            $str=$out.substr($str, $pos);
                        }
                    }
                    if(($depth == 0) && preg_match("/li/i", $mode)){
                        $p=0;
                        $mode='';
                    }
                    switch($mode){
                        case 'li':
                        $p->add("li")->Content=$str;
                        break;
                        case 'code';
                        if($p->childCount > 0)
                            $p->childs[$p->childCount-1]->append("\n".$str);
                        else
                            $p->addText($str);
                        if($depth == 2){
                            $depth=0;
                            $p=0;
                            $mode='';
                        }
                        break;default:
                        if(!$p)
                            $p=$d->add('p');
                        $p->add('span')->content=$str." ";
                        break;
                    }
                    break;
                }
            }
        }
    }
}
