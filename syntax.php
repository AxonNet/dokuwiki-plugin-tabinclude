<?php
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
@require_once(DOKU_PLUGIN.'syntax.php');
/**
 * Tab plugin
 */
class syntax_plugin_tabinclude extends DokuWiki_Syntax_Plugin{
  function getType(){ return 'substition'; }
  function getSort(){ return 158; }
  function connectTo($mode){$this->Lexer->addSpecialPattern('\{\{tabinclude>[^}]*\}\}',$mode,'plugin_tabinclude');}
 /**
  * handle syntax
  */
  function handle($match, $state, $pos, &$handler){
    $match = substr($match,13,-2);
    $pages = explode(',',$match);
    return array($state,$pages);
  }
 /**
  * Render tab control
  */
  function render($mode, &$renderer, $data) {
    global $ID;

    if ($mode=='xhtml'){
        list($state, $pages) = $data;
        $sz = count($pages);
        if($sz==0) return true;

        // loop for tabs
        $tabs = array();
        $init_page_idx = 0;
        for($i=0;$i<$sz;$i++){
          $title='';
          $page = trim($pages[$i]);
          if($page[0]=='*'){
              $init_page_idx=$i;
              $page = substr($page,1);
          }
          $items = explode('|',$page);
          if(count($items)>1){
              list($page,$title)=$items;
          }
          resolve_pageid(getNS($ID),$page,$exists);
          if($title==''){
            $title = $this->getConf('namespace_in_tab')?$page:noNS($page);
          }
          if($this->getConf('use_first_heading')){
            $meta_title= p_get_metadata($page,'title');
            if($meta_title!=''){
              $title = $meta_title;
            }
          }
          if(page_exists($page)==false){
              $tabs[$i] = array('error'=>'# ERROR #');
              msg($this->getLang('error_notfound').' : '.hsc($page));
          }else if($ID==$page){
              $tabs[$i] = array('error'=>'# ERROR #');
              msg($this->getLang('error_parent').' : '.hsc($page));
          }else{
            $tabs[$i] = array('page'=>hsc($page),'title'=>hsc($title));
          }
        }

        // render
        $html.= '<div id="dwpl-ti-container">'.NL;
        $html.='<ul class="dwpl-ti">'.NL;
        for($i=0;$i<$sz;$i++){
            if(empty($tabs[$i]['error'])){
              $selected_class=($init_page_idx==$i)?' selected':'';
              $html.='<li class="dwpl-ti-tab"><div class="dwpl-ti-tab-title'.$selected_class.'" value="'.$tabs[$i]['page'].'">'.$tabs[$i]['title'].'</div></li>'.NL;
            }else{
              $html.='<li class="dwpl-ti-tab"><div class="dwpl-ti-tab-title">'.$tabs[$i]['error'].'</div></li>'.NL;
            }
        }
        $html.= '</ul>'.NL;
        $html.='<div class="dwpl-ti-content-box">';
        if($this->getConf('hideloading')!=1){
          $html.='<div id="dwpl-ti-loading" class="dwpl-ti-loading">'.$this->getLang('loading').'</div>';
        }
        $html.='<div id="dwpl-ti-content" class="dwpl-ti-content">';
        $html.=tpl_include_page($tabs[$init_page_idx]['page'],false);
        $html.= '</div></div>'.NL.'</div>'.NL;

        $renderer->doc.=$html;
        return true;
    }else if($mode=='odt'){
        $renderer->strong_open();
        $renderer->doc.='Tab pages';
        $renderer->strong_close();
        $renderer->p_close();

        $renderer->listu_open();
        for($i=0;$i<$sz;$i++){
            $page = hsc(trim($pages[$i]));
            resolve_pageid(getNS($ID),$page,$exists);
            $title = p_get_metadata($page,'title');
            $title = empty($title)?$page:hsc(trim($title));
            $abstract = p_get_metadata($page);
            $renderer->listitem_open();
            $renderer->p_open();
            $renderer->internallink($page,$title);
            $renderer->p_close();
            $renderer->p_open();
            if(is_array($abstract))
                $renderer->doc.=hsc($abstract['description']['abstract']);
            $renderer->p_close();
            $renderer->listitem_close();
        }
        $renderer->listu_close();
        $renderer->p_open();
        return true;
    }
    return false;
  }
}
?>