<?php
/*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*/

if (!defined('_CAN_LOAD_FILES_'))
  exit;

class MegaMenu extends Module
{
  private $url = '';
  public $id_shop;
  
  public function __construct()
  {
   
    $this->name = 'megamenu';
	$this->tab = 'front_office_features';
    $this->version = '2.9';
    $this->author = 'DevSoft';
    $this->url = 'index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getValue('token');
    $this->module_key = '8f4a74d1ca13857f3db5529b2d08cbf4';
    $this->bootstrap = true;
    parent::__construct();
    $this->displayName = $this->l('Mega Menu');
    $this->description = $this->l('Mega Drop Down Menu');
    $path = dirname(__FILE__);
     
    if (strpos(__FILE__, 'Module.php') !== false)
          $path .= '/../modules/'.$this->name;
		  
    if (version_compare(_PS_VERSION_, '1.5.0.0')  == +1) {
		include_once($path.'/classes/15/MegaMenuClass.php');
	} else {		
		include_once($path.'/classes/14/MegaMenuClass.php');
	}	
    if(!defined('_MYSQL_ENGINE_')) {
        define('_MYSQL_ENGINE_', 'MyISAM');
    }
  }

  public function install()
  {
  
  	if (version_compare(_PS_VERSION_, '1.5.0.0')  == +1) {
		$left = 0;
	} else {		
		$left = -265;
	}
		
    if(!Db::getInstance()->Execute('
      CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'megamenu` (
                    `id_megamenu` int(11) NOT NULL AUTO_INCREMENT,	
			`id_shop` int(11) unsigned NOT NULL,				
                    `parent` int(11) NOT NULL DEFAULT 0,
                    `parent_custom` BOOL NOT NULL DEFAULT 0,
                    `active` BOOL NOT NULL DEFAULT 1,
			`position` float(10) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id_megamenu`, `id_shop`)
                  ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'))
           	return false;        
                  
    if(!Db::getInstance()->Execute('
      CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'megamenu_lang` (
                    `id_megamenu` int(10) unsigned NOT NULL,
                    `id_lang` int(10) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `url` varchar(255) NOT NULL,
                    `title` varchar(255) NOT NULL,
                    PRIMARY KEY (`id_megamenu`, `id_lang`))
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'))
            return false;

  
    if (!parent::install() OR
      !$this->registerHook('top') OR
      //mega menu
      !Configuration::updateValue('MEGAMENU_CATS', 'a:0:{}') OR 
      !Configuration::updateValue('MEGAMENU_COLOR', 'blue') OR
      !Configuration::updateValue('MEGAMENU_ROWITEMS', 4) OR
      !Configuration::updateValue('MEGAMENU_SPEED', 'fast') OR
      !Configuration::updateValue('MEGAMENU_EFFECT', 'fade') OR
      !Configuration::updateValue('MEGAMENU_EVENT', 'hover') OR
      !Configuration::updateValue('MEGAMENU_FULLWIDTH', '0') OR
	  !Configuration::updateValue('MEGAMENU_DISPLAYIMAGES', '0') OR
      !Configuration::updateValue('MEGAMENU_LIMITSUBS', '0') OR
	  //global
	  !Configuration::updateValue('MEGAMENU_MARGINLEFT', $left) OR 
	  !Configuration::updateValue('MEGAMENU_MARGINTOP', '20') OR 
	  !Configuration::updateValue('MEGAMENU_MARGINRIGHT', '0') OR 
	  !Configuration::updateValue('MEGAMENU_MARGINBOTTOM', '20')){
        return false;
    }  
      
    return true;
  }
 
  public function uninstall()
  {
    
    Db::getInstance()->Execute('DROP TABLE '._DB_PREFIX_.'megamenu');
    Db::getInstance()->Execute('DROP TABLE '._DB_PREFIX_.'megamenu_lang');
      
    if (!parent::uninstall() OR
      !Configuration::deleteByName('MEGAMENU_CATS') OR     
	  !Configuration::deleteByName('MEGAMENU_POSITION') OR  
      !Configuration::deleteByName('MEGAMENU_COLOR') OR
      !Configuration::deleteByName('MEGAMENU_ROWITEMS') OR
      !Configuration::deleteByName('MEGAMENU_SPEED') OR
      !Configuration::deleteByName('MEGAMENU_EFFECT') OR
      !Configuration::deleteByName('MEGAMENU_EVENT') OR
      !Configuration::deleteByName('MEGAMENU_FULLWIDTH') OR
      !Configuration::deleteByName('MEGAMENU_DISPLAYIMAGES') OR  
      !Configuration::deleteByName('MEGAMENU_LIMITSUBS') OR
	  !Configuration::deleteByName('MEGAMENU_MARGINLEFT') OR 
	  !Configuration::deleteByName('MEGAMENU_MARGINTOP') OR 
	  !Configuration::deleteByName('MEGAMENU_MARGINRIGHT') OR 
	  !Configuration::deleteByName('MEGAMENU_MARGINBOTTOM')){
        return false;
    }        
      
    return true;
  }
  
  public function hookCategoryDeletion()
  {
	$id_shop = (int)Context::getContext()->shop->id;
	    $cats = Db::getInstance()->ExecuteS('
	    SELECT c.`id_category`
	    FROM `'._DB_PREFIX_.'category` c
	    LEFT JOIN `'._DB_PREFIX_.'category_shop` cs
	    ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$id_shop.')
	    WHERE cs.`id_shop` = '.(int)$id_shop.'');
 
    $CATS = Configuration::get('MEGAMENU_CATS');
    $UN_CATS = array();
    $UN_CATS = unserialize($CATS);
    //echo print_r($UN_CATS);
	if(is_array($UN_CATS))
	{
		foreach($UN_CATS as $key => $value){
          	if(in_array( array('id_category' => $value), $cats)){
            
          	} else{
              	unset( $UN_CATS[$key] );
          	} 
     	}
	    //corect keys
	    $UN_CATS = array_values($UN_CATS);   
	    //serialize and save configuration  
	    $SE_CATS = serialize($UN_CATS);
      	Configuration::updateValue('MEGAMENU_CATS', $SE_CATS);
      }

      /* Get childs categories */
      $nodes = Db::getInstance()->ExecuteS('
      SELECT `id_megamenu`, `parent`
      FROM `'._DB_PREFIX_.'megamenu`
      WHERE parent_custom = 0
      AND id_shop='.$id_shop);

      if(sizeof($nodes) > 0)
      {
        foreach ($nodes AS $node)
        {
          if(in_array( array('id_category' => $node['parent']), $cats)){
            
          } else{
              $mm = new MegaMenuClass($node['id_megamenu']);
              $mm->delete();
          }       
        }

      }
      
      return true;
  }

  public function getContent()
  {
    $this->hookCategoryDeletion();
    
    $output = '<h2>'.$this->displayName.'</h2>';
    $defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
    $errors = '';
    
    if (Tools::isSubmit('submitMegaMenu'))
    {		
        Configuration::updateValue('MEGAMENU_DISPLAY', Tools::getValue('MEGAMENU_DISPLAY'));
        Configuration::updateValue('MEGAMENU_COLOR', Tools::getValue('MEGAMENU_COLOR'));
        Configuration::updateValue('MEGAMENU_ROWITEMS', (int)Tools::getValue('MEGAMENU_ROWITEMS'));
        Configuration::updateValue('MEGAMENU_SPEED', Tools::getValue('MEGAMENU_SPEED'));
        Configuration::updateValue('MEGAMENU_EFFECT', Tools::getValue('MEGAMENU_EFFECT'));
        Configuration::updateValue('MEGAMENU_EVENT', Tools::getValue('MEGAMENU_EVENT'));
        Configuration::updateValue('MEGAMENU_FULLWIDTH', Tools::getValue('MEGAMENU_FULLWIDTH'));
        Configuration::updateValue('MEGAMENU_LIMITSUBS', Tools::getValue('MEGAMENU_LIMITSUBS'));  
		Configuration::updateValue('MEGAMENU_DISPLAYIMAGES', Tools::getValue('MEGAMENU_DISPLAYIMAGES'));
		
		Configuration::updateValue('MEGAMENU_MARGINLEFT', Tools::getValue('MEGAMENU_MARGINLEFT'));
		Configuration::updateValue('MEGAMENU_MARGINTOP', Tools::getValue('MEGAMENU_MARGINTOP'));
		Configuration::updateValue('MEGAMENU_MARGINRIGHT', Tools::getValue('MEGAMENU_MARGINRIGHT'));
		Configuration::updateValue('MEGAMENU_MARGINBOTTOM', Tools::getValue('MEGAMENU_MARGINBOTTOM'));
  
        $this->_clearmegamenuCache();
        $output .= '<div class="conf confirm alert alert-success"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';
                                               
    }    
    
    return $output.$this->displayForm();
  }

  public function displayForm()
  {   
    $defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
    $mmtoken = sha1('mm'._COOKIE_KEY_.'mmmegamenu');
    $id_shop =  (int)Context::getContext()->shop->id;

     $output = ' 
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/js/jquery-1.6.2.min.js"></script>
      <script type="text/javascript">
         jq162 = jQuery.noConflict();
      </script>   
	  <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/js/jquery.livequery.min.js"></script>
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/js/jquery.liveflex.Tree.js"></script>
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/js/jquery-ui-1.8.16.custom.min.js"></script>
      <link href="'._MODULE_DIR_.'megamenu/css/ui/jquery-ui-1.8.16.custom.css"  type="text/css" rel="stylesheet"> 
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/js/tree.js"></script>    
      <script type="text/javascript">
            var urlJson = "'._MODULE_DIR_.'megamenu/megamenu-json.php?id_shop='.$id_shop.'&mmtoken='.$mmtoken.'";
	    var _MODULE_DIR_ = "'._MODULE_DIR_.'";
            var action = null;
      </script>
      <div style="display:none;">
        <h3 style="text-align:center;">Custom link form</h3>
           <div id="add_form" title="Add new link">  '.$this->displayAddForm().'</div>
     </div>
     <div style="display:none;">
       <h3 style="text-align:center;margin-top:20px;">Copy link from cms</h3>
           '.$this->displayAddCmsForm().'
     </div>';

    $output .= $this->displayList();
           
     return $output;
  }

  public function displayList()
  {    
   	$cat = Category::getRootCategory(Context::getContext()->cookie->id_lang);
	$id_root  = $cat->id;
      $output = '
      <link href="'._MODULE_DIR_.'megamenu/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/uploadify/swfobject.js"></script>
      <script type="text/javascript" src="'._MODULE_DIR_.'megamenu/uploadify/jquery.uploadify.v2.1.4.min.js"></script>
       <script>
         jq162(document).ready(function() {
            jq162("#file_upload").uploadify({
              "hideButton": true,
              "wmode"     : "transparent",
              "uploader"  : "'._MODULE_DIR_.'megamenu/uploadify/uploadify.swf",
              "script"    : "'._MODULE_DIR_.'megamenu/uploadify/uploadify.php",
              "cancelImg" : "'._MODULE_DIR_.'megamenu/uploadify/cancel.png",
              "folder"    : "'._MODULE_DIR_.'megamenu/images",
              "auto"      : true,
              "multi"     : false,
              "scriptData"  : {
                  "id": getId()
              },
              onSelect : function(){
                jq162("#file_upload").uploadifySettings(
                    "scriptData", {
                        "id": getId()
                    })
              }
            }); 

            function getId()
            {
               if(jq162("#PageTree a.selected").parent().hasClass("custom"))
               {
                   return "c" + jq162("#PageTree a.selected").attr("rel");
               }
               else
               {
                   return jq162("#PageTree a.selected").attr("rel");
               }
            }
       }); 
       </script>
        <div id="maintabs">
        <ul>
          <li><a href="#maintabs-1">Create Menu</a></li>
          <li><a href="#maintabs-2">Settings</a></li>
        </ul>  
        
        <div id="maintabs-1">
        <div >
         <fieldset id="tree_fieldset">     

         <div id="msg">Customize your menu</div>

         <div id="tools" style="">
           <a id="add" href="#" style="margin-top:20px;clear:both;">
              <img title="add" alt="add" src="'._MODULE_DIR_.'megamenu/css/admin/img/add.png" />
            </a>
            </br>  
             <a id="copy" href="#">
              <img title="copy" alt="copy" src="'._MODULE_DIR_.'megamenu/css/admin/img/copy.png" />
            </a>     
            </br> 
            <div style="width:48px;height:48px;"> 
              <a id="edit" href="#add_form">
                <img title="Edit" alt="edit" src="'._MODULE_DIR_.'megamenu/css/admin/img/edit.png" />
              </a>
            </div>
            <div style="width:48px;height:48px;">
              <a id="delete" href="#" >
                 <img title="Delete" alt="Delete" src="'._MODULE_DIR_.'megamenu/css/admin/img/delete.png">
              </a>
            </div> 
            <div style="width:48px;height:48px;" class="uploadWrapper">
              <input title="image upload" id="file_upload" name="file_upload" type="file" />
            </div>
              <a id="enable" href="#">
                <img src="'._MODULE_DIR_.'megamenu/css/admin/img/active.png" alt="active" title="enable"/>
              </a>
         </div> 

         <div id="tree_wrap">  
          <div class="demo">
            <ul id="PageTree" class="tree sortable">
              <li id="home" class="parent collapsed" data-id="'.$id_root.'" pos="-1">
                <a class="caption ui-droppable" rel="'.$id_root.'"  >Home</a>
              </li>
            </ul>
          </div>
      
          <div id="preloder"><img src="'._MODULE_DIR_.'megamenu/icons/ajax-loader.gif" /></div>         
        
         </div> 
         <div id="version">'.$this->l('version : 2.9').'</div>
        </fieldset>
        </div>
        </div>
        
          <div id="maintabs-2">';
               $output .= $this->displaySettingsForm();
        $output .= '</div>
        </div>
        </br>
        <div>
            <div class="alert alert-info">
                <ul>
                    <li><img src="'._MODULE_DIR_.'megamenu/icons/custom.png" alt="custom" title="custom"/> '.$this->l('Custom link').'</li>
                    <li><span id="legend-blue"></span> '.$this->l('Selected element').' </li>
                    <li><span id="legend-delete">txt</span> '.$this->l('Disable element').' </li>
                </ul> 
           </div>
            <div class="alert alert-info">
                <ul>
                    <li>Upload images only for second level custom links.</li>
                    <li>Custom links can be sort using drag and drop.</li>
                    <li>Edit and delete button is display only for custom links</li>
                </ul>
            </div>
        </div>

        <style>
         li a.selected {color:blue;font-weight:bold;}
         #tree_wrap {position:relative;min-height:295px;margin: 6px 10px 4px 65px;background:#FFFFF0;-moz-border-radius: 7px;border-radius: 7px;}
         a {font-weight:normal;}     
         #tree_fieldset {background:#535353 !important;-moz-border-radius: 7px;border-radius: 7px;padding: 0.5em 1em 0.4em;} 
         .ui-widget-content {display:block;}
         .ui-widget-content a {text-decoration: none; font-size:13px;}
         #legend {
           position: absolute;
           right: 30px;
           top: 40px;
         }
         #legend-blue {
           width:16px;
           height:16px;
           background:blue;
           margin-right:5px;
           display:block;
           float:left;
         }
         #legend-delete{
           text-decoration: line-through ;
           margin-right:5px;
         }
         #preloder {
            display: none;
            left: 275px;
            position: absolute;
            top: 130px;
         }
         span.custom-img {
           background:url(../modules/megamenu/icons/custom.png) no-repeat left center;
           width:16px;
           height:16px;
           display:block;
           position:absolute;
         } 
         #version {
           text-align:center;
           color:#ffffff;
         } 
         label {font-weight:normal;}
         form p { margin: 0.5em 0 0 20px;padding: 0 0 0.5em;text-align: left;}
         #settings label{color:#ffffff;font-weight:normal;}
         .hide{display:none;}
         #msg {width:100%;text-align:center;color: #FFFFFF;height:15px;}
         #tools {width:50px;float:left;margin-top:-10px;padding-right:5px;}
         a.disable{text-decoration: line-through ;}
    
   
          .over{outline:solid 1px #afa}
          .on{background:green;border:solid 1px green;}
          .below{border-bottom:solid 2px #ddf;}
          .below span{}
          .above{border-top:solid 2px #ddf;}
          .tree_hover{background:#fafaff}
        
          ul.tree, ul.tree ul{list-style-type:none;padding:0;}
          ul.tree li, ul.tree li ul li{
            line-height:1.7em;cursor:default;
            background:url(../modules/megamenu/icons/line.png) no-repeat;;
            padding-left:16px;
          }
          ul.tree li a.caption{
            background:url(../modules/megamenu/icons/page.png) no-repeat left center;
            padding-left:20px;
            min-width:200px;
          }
          ul.tree li.parent.expanded>a.caption{
          background:url(../modules/megamenu/icons/folder.png) no-repeat left center;
          }
          ul.tree li.parent.collapsed>a.caption{
          background-image:url(../modules/megamenu/icons/folder_page.png);
          }
          ul.tree li:last-child{
            background:url(../modules/megamenu/icons/end.png) no-repeat;
          }
          .uploadWrapper object {
            background: url(../modules/megamenu/css/admin/img/img.png) no-repeat;
            width:48px;
            height:48px;
          } 
          .uploadWrapper{
            background: url(../modules/megamenu/css/admin/img/img.png) no-repeat;
          } 
        
        
          /* IE */
          ul.tree li.last{
            background:url(../modules/megamenu/icons/end.png) no-repeat;
          }
          
          span.callback{
            color:#aaa;
            font-size:.8em;
            margin-right:5px;
          }
          ul.tree li span.cmds{
            float:right;
            display:block;
            line-height:.8em;
          }
          #Events{border:solid 1px #aaa;padding:10px;font-family:courier;font-size:10px;color:#666;height:80px;max-height:80px;overflow:auto;}
            
          div.demo,div.syntaxhighlighter{padding:10px 20px;}
          div.syntaxhighlighter div.toolbar{display:none;}
          table.options tr td:first-child{font-family:courier;padding-right:1em;vertical-align:top;}
        </style>';
      
      return $output;
  }
   
  public function displaySettingsForm()
  {    
    $SUPERFISH_COLOR_L1       = (Configuration::get('SUPERFISH_COLOR_L1')       ? Configuration::get('SUPERFISH_COLOR_L1') : 0);
    $SUPERFISH_COLOR_L2       = (Configuration::get('SUPERFISH_COLOR_L2')       ? Configuration::get('SUPERFISH_COLOR_L2') : 0);
    $SUPERFISH_COLOR_L3       = (Configuration::get('SUPERFISH_COLOR_L3')       ? Configuration::get('SUPERFISH_COLOR_L3') : 0);
    $SUPERFISH_COLOR_FONT     = (Configuration::get('SUPERFISH_COLOR_FONT')       ? Configuration::get('SUPERFISH_COLOR_FONT') : 0);
    $SUPERFISH_COLOR_HOVER    = (Configuration::get('SUPERFISH_COLOR_HOVER') ? Configuration::get('SUPERFISH_COLOR_HOVER') : 0); 
    
    $output = '
    <form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="">
        <div class="form-group">
            <label class="control-label col-lg-3">'.$this->l('Margin (px)').':</label>
            <div class="margin-form col-lg-9">
                <span style="float:left;margin-left:5px;">'.$this->l('top').'</span>
                <input type="text" id="margin_top"  name="MEGAMENU_MARGINTOP" style="margin-left:5px;width:40px;float:left;"  value="'.Configuration::get('MEGAMENU_MARGINTOP').'" />
                <span style="float:left;margin-left:5px;">'.$this->l('right').'</span>
                <input type="text" id="margin_right"  name="MEGAMENU_MARGINRIGHT" style="margin-left:5px;width:40px;float:left;"  value="'.Configuration::get('MEGAMENU_MARGINRIGHT').'" />
                <span style="float:left;margin-left:5px;">'.$this->l('bottom').'</span>
                <input type="text" id="margin_bottom"  name="MEGAMENU_MARGINBOTTOM" style="margin-left:5px;width:40px;float:left;"  value="'.Configuration::get('MEGAMENU_MARGINBOTTOM').'" />
                <span style="float:left;margin-left:5px;">'.$this->l('left').'</span>
                <input type="text" id="margin_left"  name="MEGAMENU_MARGINLEFT" style="margin-left:5px;width:40px;float:left;"  value="'.Configuration::get('MEGAMENU_MARGINLEFT').'" />
            </div>
        </div>
        <div class="form-group" style="clear:both;">
            <label class="control-label col-lg-3">'.$this->l('Menu color :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_COLOR" style="width:170px;">              
                    <option value="default"    '.(Configuration::get('MEGAMENU_COLOR') == "default"    ? 'selected="selected"' : '').'>'.$this->l('default').'</option>
                    <option value="black"      '.(Configuration::get('MEGAMENU_COLOR') == "black"      ? 'selected="selected"' : '').'>'.$this->l('black').'</option>
                    <option value="grey"       '.(Configuration::get('MEGAMENU_COLOR') == "grey"       ? 'selected="selected"' : '').'>'.$this->l('grey').'</option>
                    <option value="blue"       '.(Configuration::get('MEGAMENU_COLOR') == "blue"       ? 'selected="selected"' : '').'>'.$this->l('blue').'</option>
                    <option value="orange"     '.(Configuration::get('MEGAMENU_COLOR') == "orange"     ? 'selected="selected"' : '').'>'.$this->l('orange').'</option>               
                    <option value="red"        '.(Configuration::get('MEGAMENU_COLOR') == "red"        ? 'selected="selected"' : '').'>'.$this->l('red').'</option>
                    <option value="green"      '.(Configuration::get('MEGAMENU_COLOR') == "green"      ? 'selected="selected"' : '').'>'.$this->l('green').'</option>
                    <option value="light-blue" '.(Configuration::get('MEGAMENU_COLOR') == "light-blue" ? 'selected="selected"' : '').'>'.$this->l('light blue').'</option>
                    <option value="white"      '.(Configuration::get('MEGAMENU_COLOR') == "white"      ? 'selected="selected"' : '').'>'.$this->l('white').'</option>        
			        </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">'.$this->l('Row items :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_ROWITEMS" style="width:170px;">              
                    <option value="1" '.(Configuration::get('MEGAMENU_ROWITEMS') == 1 ? 'selected="selected"' : '').'>'.$this->l('1').'</option>
                    <option value="2" '.(Configuration::get('MEGAMENU_ROWITEMS') == 2 ? 'selected="selected"' : '').'>'.$this->l('2').'</option>
                    <option value="3" '.(Configuration::get('MEGAMENU_ROWITEMS') == 3 ? 'selected="selected"' : '').'>'.$this->l('3').'</option>
                    <option value="4" '.(Configuration::get('MEGAMENU_ROWITEMS') == 4 ? 'selected="selected"' : '').'>'.$this->l('4').'</option>
                    <option value="5" '.(Configuration::get('MEGAMENU_ROWITEMS') == 5 ? 'selected="selected"' : '').'>'.$this->l('5').'</option>
                    </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">'.$this->l('Speed :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_SPEED" style="width:170px;">              
                    <option value="fast" '.(Configuration::get('MEGAMENU_SPEED') == "fast" ? 'selected="selected"' : '').'>'.$this->l('fast').'</option>
                    <option value="slow" '.(Configuration::get('MEGAMENU_SPEED') == "slow" ? 'selected="selected"' : '').'>'.$this->l('slow').'</option>
                </select>
            </div>
        </div>
        <div class="form-group">    
            <label class="control-label col-lg-3">'.$this->l('Effect :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_EFFECT" style="width:170px;">              
                    <option value="slide" '.(Configuration::get('MEGAMENU_EFFECT') == "slide" ? 'selected="selected"' : '').'>'.$this->l('slide').'</option>
                    <option value="fade" '.(Configuration::get('MEGAMENU_EFFECT')  == "fade"  ? 'selected="selected"' : '').'>'.$this->l('fade').'</option>
                </select>
			    <p style="color:red;padding:0;">Dont use slide if you are using images.</p>
            </div>
        </div>
        <div class="form-group">   
            <label class="control-label col-lg-3">'.$this->l('Event :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_EVENT" style="width:170px;">              
                    <option value="hover" '.(Configuration::get('MEGAMENU_EVENT') == "hover" ? 'selected="selected"' : '').'>'.$this->l('hover').'</option>
                    <option value="click" '.(Configuration::get('MEGAMENU_EVENT') == "click" ? 'selected="selected"' : '').'>'.$this->l('click').'</option>
                </select>
			    <p style="padding:0;">This option is not avabile in Left Colum display type.</p>
            </div>
        </div>
        <div class="form-group">   
            <label class="control-label col-lg-3">'.$this->l('Full width :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_FULLWIDTH" style="width:170px;">              
                    <option value="1" '.((int)(Configuration::get('MEGAMENU_FULLWIDTH')) == 1 ? 'selected="selected"' : '').'>'.$this->l('true').'</option>
                    <option value="0" '.((int)(Configuration::get('MEGAMENU_FULLWIDTH')) == 0 ? 'selected="selected"' : '').'>'.$this->l('false').'</option>
                </select>
			    <p style="padding:0;">This option is not avabile in Left Colum display type.</p>
            </div>
		</div>
        <div class="form-group">	
	        <label class="control-label col-lg-3">'.$this->l('Display images :').'</label>
            <div class="margin-form col-lg-9">
                <select name="MEGAMENU_DISPLAYIMAGES" style="width:170px;">              
                    <option value="1" '.((int)(Configuration::get('MEGAMENU_DISPLAYIMAGES')) == 1 ? 'selected="selected"' : '').'>'.$this->l('true').'</option>
                    <option value="0" '.((int)(Configuration::get('MEGAMENU_DISPLAYIMAGES')) == 0 ? 'selected="selected"' : '').'>'.$this->l('false').'</option>
                </select>
                <p style="padding:0;">Image width 175px.</p>
            </div>
		</div>
        <div class="form-group">				  
            <label class="control-label col-lg-3">'.$this->l('Limit subs (third-level): ').'</label>
            <div class="margin-form col-lg-9">
                <input style="width:50px;" type="text" name="MEGAMENU_LIMITSUBS" id="MEGAMENU_LIMITSUBS" value="'.((int)(Configuration::get('MEGAMENU_LIMITSUBS'))  ? Configuration::get('MEGAMENU_LIMITSUBS') : 0 ).'" />
                <p style="">Leave 0 if you dont want limit subacategories.</p>
            </div>
        </div>
        <center><input type="submit" name="submitMegaMenu" value="'.$this->l('Save settings').'" class="button" /></center>
    </form>';
    
	return $output;
  }

  public function displayAddCmsForm()
  {    
      $output = null;
      $cmslinks = CMS::listCms(Context::getContext()->cookie->id_lang);
        
      $output = '   
      <div id="copycms" title="'.$this->l('Copy from cms form.').'">   
        <form id="copycms_form"  method="post"  > 
           <input id="parentid" name="parentid" type="hidden" />  
           <input id="copyinput_custom" name="custom" value="0" type="hidden" />  
		   <input id="copy_position" name="position" value="" type="hidden" />
		   
           <fieldset style="background:none;">         
              <label>'.$this->l('Cms title :').'</label>
               <div class="margin-form">
                <select name="id_cms" style="float:left;">'; 
				 $output .= '<option value="0" >'.$this->l('---Select---').'</option>'; 
				 if( is_array($cmslinks) == true )      
				 {
				 	foreach ($cmslinks as $cmslink) 
	                 {                                                  
	                   $output .= '<option value="'.$cmslink['id_cms'].'" >'.$cmslink['meta_title'].'</option>';                           
	                 }
				 }     
           $output .= '             
               </select>                               
              </div> 
              <p style="clear: both;font-size:0.8em;text-align:left;margin-left:40px;">'.$this->l('This is cms page title you want to copy.').'</p>
            </fieldset>    
         </form>
       </div>
       <style>
        .margin-form { padding: 0 0 1em 80px; }
       </style>'; 
        
     return $output;
  }

  public function displayAddForm()
  {     
       $editlink = null;
      
        /* Languages preliminaries */
        $defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
        $languages = Language::getLanguages(false);
        $iso = Language::getIsoById((int)(Context::getContext()->cookie->id_lang));
        
        $divLangName = "idname¤idurl¤idtitle";

        $editlink = new MegaMenuClass((int)(Tools::getValue('editlink')));
          
     
      $output = ' <script type="text/javascript">id_language = Number('.$defaultLanguage.');</script>

      <div id="add_form" title="Add new link">  
        <p class="validateTips">All form fields are required.</p>
          
        <form id="form">  
          <fieldset style="background:none;">  
            <input id="parent" name="parent" type="hidden" />  
            <input id="id_megamenu" name="id_megamenu" type="hidden" /> 
            <input id="input_custom" name="custom" value="0" type="hidden" />
			<input id="position" name="position" value="" type="hidden" />
			
            <label class="label-add">'.$this->l('Link name :').'</label>
              <div class="margin-form">';          
               foreach ($languages as $language) 
               {                                                  
                 $output .= '<div id="idname_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
                                <input class="name" name="name_'.$language['id_lang'].'" id="name_'.$language['id_lang'].'" size="44" value="'.(isset($editlink->name[$language['id_lang']]) ? $editlink->name[$language['id_lang']] : '').'" />
                              </div>';                           
               }  
                 $output .= $this->displayFlags($languages, $defaultLanguage, $divLangName, 'idname', true). '<sup> *</sup>   
               <p style="clear: both">'.$this->l('This is your linkname shows in front page.').'</p>       
               </div>               
                    
          <label class="label-add">'.$this->l('title :').'</label>
              <div class="margin-form">';          
               foreach ($languages as $language) 
               {                                                  
                 $output .= '<div id="idtitle_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
                                <input class="title" name="title_'.$language['id_lang'].'" id="title_'.$language['id_lang'].'" size="44" value="'.(isset($editlink->title[$language['id_lang']]) ? $editlink->title[$language['id_lang']] : '').'" />
                              </div>';                           
               }  
                 $output .= $this->displayFlags($languages, $defaultLanguage, $divLangName, 'idtitle', true). '<sup> *</sup> 
               <p style="clear: both">'.$this->l('This is text displayed on link.').'</p>         
               </div> 
          
           <label class="label-add">'.$this->l('Url :').'</label>
              <div class="margin-form">';          
               foreach ($languages as $language) 
               {                                                  
                 $output .= '<div id="idurl_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
                                <input class="url" name="url_'.$language['id_lang'].'" id="url_'.$language['id_lang'].'" size="44" value="'.(isset($editlink->url[$language['id_lang']]) ? $editlink->url[$language['id_lang']] : '').'" />
                              </div>';                           
               }  
                 $output .= $this->displayFlags($languages, $defaultLanguage, $divLangName, 'idurl', true). '<sup> *</sup> 
               <p style="clear: both">'.$this->l('This is redirect url after user click on link.').'</p>         
               </div> 

         </fieldset>
       </form>
      </div>
       <style>
        .margin-form { padding: 0 0 1em 80px; }
        .label-add { width:90px; float:left; }
        .uploadifyQueue{display:none;}
       </style>';
       
       return $output;
  }
 
  public function getTree($resultParents, $resultIds, $maxDepth, $id_category = 1, $currentDepth = 0)
  {   
    $children = array();
    if (isset($resultParents[$id_category]) AND sizeof($resultParents[$id_category]) AND ($maxDepth == 0 OR $currentDepth < $maxDepth)){
      
      foreach ($resultParents[$id_category] as $subcat)
      {
          $children[] = $this->getTree($resultParents, $resultIds, $maxDepth, $subcat['id_category'], $currentDepth + 1);
      }
      if( $currentDepth == 2 ){
        $limit = (int)(Configuration::get('MEGAMENU_LIMITSUBS'));
        
        if( $limit != 0 && count($children) > $limit) {
             $children = array_slice($children, 0, $limit);
             $children[] = array('id' => 'more', 'link' => Context::getContext()->link->getCategoryLink($id_category, $resultIds[$id_category]['link_rewrite']),
             'name' => $this->l('more'), 'desc'=> $this->l('display more'),
             'children' => null);
        }
     }
    }
    if (!isset($resultIds[$id_category])){
      return false;
      
    }elseif(isset($resultIds[$id_category]['type'])) {
      $imagesize = null;
	  if(file_exists(dirname(__FILE__).'/images/'.$id_category.'.png'))
	      $imagesize = getimagesize(dirname(__FILE__).'/images/'.$id_category.'.png' );
  
      return array('id' => $id_category, 'link'=> $resultIds[$id_category]['url'],
           'name' => $resultIds[$id_category]['name'], 'desc'=> $resultIds[$id_category]['title'],
           'children' => $children, 'currentDepth' => $currentDepth, 'image' => _MODULE_DIR_.'megamenu/images/'.$id_category.'.png',
		   'imageheight' => ($imagesize != null ? $imagesize[1] : 0));
  
    }else {
        
      $imagesize = null;
      if(file_exists(dirname(__FILE__).'/images/'.$id_category.'.png'))
          $imagesize = getimagesize(dirname(__FILE__).'/images/'.$id_category.'.png' );
      
      return array('id' => $id_category, 'link' => Context::getContext()->link->getCategoryLink($id_category, $resultIds[$id_category]['link_rewrite']),
             'name' => $resultIds[$id_category]['name'], 'desc'=> $resultIds[$id_category]['description'],
             'children' => $children, 'currentDepth' => $currentDepth, 'image' => _MODULE_DIR_.'megamenu/images/'.$id_category.'.png',
             'imageheight' => ($imagesize != null ? $imagesize[1] : 0));
             
    }
  
  }
  
  /*
  * get subcategories function for 1.5.X
  */
  public function getSubCategories($id, $id_lang, $active = true)
  {
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT c.*, cl.id_lang, cl.name,  cl.meta_title, category_shop.`position`
			FROM `'._DB_PREFIX_.'category` c
			'.Shop::addSqlAssociation('category', 'c').'
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
				ON (c.`id_category` = cl.`id_category`
				AND `id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
			LEFT JOIN `'._DB_PREFIX_.'category_group` cg
				ON (cg.`id_category` = c.`id_category`)
			WHERE `id_parent` = '.(int)$id.'
				'.($active ? 'AND `active` = 1' : '').'
			GROUP BY c.`id_category`
			ORDER BY `level_depth` ASC, category_shop.`position` ASC
		');
		
		return $result;
  }
  
  function hookTop($params)
  {  
    //get disable categories
    $CATS = Configuration::get('MEGAMENU_CATS');
    $UN_CATS = array();
    $UN_CATS = unserialize($CATS);
	$context = Context::getContext();
	if (version_compare(_PS_VERSION_, '1.5.0.0')  == +1) {
	
		$id_current_shop = $this->context->shop->id;

		$id_customer = (int)($params['cookie']->id_customer);
		// Get all groups for this customer and concatenate them as a string: "1,2,3..."
		// It is necessary to keep the group query separate from the main select query because it is used for the cache
		$groups = $id_customer ? implode(', ', Customer::getGroupsStatic($id_customer)) : Configuration::get('PS_UNIDENTIFIED_GROUP');
		$id_product = (int)(Tools::getValue('id_product', 0));
		$id_category = (int)(Tools::getValue('id_category', 0));
		$id_lang = (int)($params['cookie']->id_lang);
		$smartyCacheId = 'megamenu|'.$id_current_shop.'_'.$groups.'_'.$id_lang.'_'.$id_product.'_'.$id_category;

		Tools::enableCache();
		if (!$this->isCached('tmp/megamenu.tpl', $smartyCacheId))
		{
			$maxdepth = 4;
			if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite,  cs.position
				FROM `'._DB_PREFIX_.'category` c
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.$id_lang.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = c.`id_category`)
				LEFT JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.`id_category` = c.`id_category`)
				WHERE (c.`active` = 1 OR c.`id_category` = '.(int)Configuration::get('PS_HOME_CATEGORY').')
				AND c.`id_category` NOT IN ('.(count($UN_CATS) > 0 ? implode(",", $UN_CATS) : "-1" ).')
				'.((int)($maxdepth) != 0 ? ' AND `level_depth` <= '.(int)($maxdepth) : '').'
				AND cg.`id_group` IN ('.pSQL($groups).')
				AND cs.`id_shop` = '.(int)Context::getContext()->shop->id.'				
		        GROUP BY id_category
		        ORDER BY `level_depth` ASC, cs.`position` ASC'))

				return Tools::restoreCacheSettings();
				
		$customResult =   Db::getInstance()->ExecuteS('
        SELECT *  FROM `'._DB_PREFIX_.'megamenu` m
        LEFT JOIN `'._DB_PREFIX_.'megamenu_lang` ml ON (m.`id_megamenu` = ml.`id_megamenu` AND ml.`id_lang` = '.intval($params['cookie']->id_lang).')
        WHERE m.`active` = 1 AND m.`id_shop` = '.(int)Context::getContext()->shop->id.' ORDER BY m.`position` ASC');

        $total = count($customResult);          
        for ($i=0; $i < $total; $i++)
        {
          $customResult[$i]['id_category'] = 'c'.$customResult[$i]['id_megamenu'];
          $customResult[$i]['type'] = 'custom';
          if($customResult[$i]['parent_custom'] == 1) {
            $customResult[$i]['id_parent'] = 'c'.$customResult[$i]['parent'];
          }else {
            $customResult[$i]['id_parent'] = $customResult[$i]['parent'];
          }
        }
        
        $mergeresult = array_merge($result,$customResult);
        $this->aasort($mergeresult,"position");
		//echo print_r($mergeresult);
        $resultParents = array();
        $resultIds = array();
  
        foreach ($mergeresult as &$row)
        {
          $resultParents[$row['id_parent']][] = &$row;
          $resultIds[$row['id_category']] = &$row;
        }
        $blockCategTree = $this->getTree($resultParents, $resultIds, 3, (int)Configuration::get('PS_HOME_CATEGORY') );
        unset($resultParents);
        unset($resultIds);

			$isDhtml = (Configuration::get('BLOCK_CATEG_DHTML') == 1 ? true : false);
			if (Tools::isSubmit('id_category'))
			{
				$this->context->cookie->last_visited_category = $id_category;
				$this->smarty->assign('currentCategoryId', $this->context->cookie->last_visited_category);
			}
			if (Tools::isSubmit('id_product'))
			{
				if (!isset($this->context->cookie->last_visited_category) || !Product::idIsOnCategoryId($id_product, array('0' => array('id_category' => $this->context->cookie->last_visited_category))))
				{
					$product = new Product($id_product);
					if (isset($product) && Validate::isLoadedObject($product))
						$this->context->cookie->last_visited_category = (int)($product->id_category_default);
				}
				$this->smarty->assign('currentCategoryId', (int)$this->context->cookie->last_visited_category);
			}
		}
	
	} 
      $tmp = null;
      $context->smarty->assign('blockCategTree', $blockCategTree);
      $context->smarty->assign('this_path', $this->_path);   
      $context->smarty->assign('branche_tpl_path', _PS_MODULE_DIR_.'megamenu/tmp/category-tree-branch.tpl');
    
      $context->smarty->assign('MEGAMENU_COLOR', Configuration::get('MEGAMENU_COLOR'));
      $context->smarty->assign('MEGAMENU_ROWITEMS', Configuration::get('MEGAMENU_ROWITEMS'));
      $context->smarty->assign('MEGAMENU_SPEED', Configuration::get('MEGAMENU_SPEED'));
      $context->smarty->assign('MEGAMENU_EFFECT', Configuration::get('MEGAMENU_EFFECT'));
      $context->smarty->assign('MEGAMENU_EVENT', Configuration::get('MEGAMENU_EVENT'));
      $context->smarty->assign('MEGAMENU_FULLWIDTH', Configuration::get('MEGAMENU_FULLWIDTH'));
	  $context->smarty->assign('MEGAMENU_DISPLAYIMAGES', Configuration::get('MEGAMENU_DISPLAYIMAGES'));

      $context->smarty->assign('MEGAMENU_MARGINLEFT', Configuration::get('MEGAMENU_MARGINLEFT')); 
	  $context->smarty->assign('MEGAMENU_MARGINTOP', Configuration::get('MEGAMENU_MARGINTOP')); 
	  $context->smarty->assign('MEGAMENU_MARGINRIGHT', Configuration::get('MEGAMENU_MARGINRIGHT')); 
	  $context->smarty->assign('MEGAMENU_MARGINBOTTOM', Configuration::get('MEGAMENU_MARGINBOTTOM'));
	  

      $display = $this->display(__FILE__, 'tmp/megamenu.tpl', $smartyCacheId);
      Tools::restoreCacheSettings();
     
    return $display;
  }

  function _clearmegamenuCache()
  {
    $this->_clearCache('megamenu.tpl');
    Tools::restoreCacheSettings();
  }
  
  function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];


    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
  }
  
  function hookAjaxCall()  
  {
      $action = Tools::getValue('action');
      $errors = null;
      $id_shop = null;

	  if (Tools::getValue('id_shop') != ''  &&  Tools::getValue('id_shop') != 0)
	  {
			Shop::setContext(Shop::CONTEXT_SHOP, Tools::getValue('id_shop'));
			$id_shop = (int)Shop::getContextShopID();
	  } 
      else 
      {
	        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
	  }


  if($action == "delete")
  {
     $link = new MegaMenuClass((int)(Tools::getValue('id')));
             
     Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'megamenu` SET `position` = (`position` - 0.01) WHERE `position` > '.$link->position.' AND `parent` = '.$link->parent);
                  
     $link->delete();
                
     echo Tools::jsonEncode('Link deleted successfully'); 
     
  }
  elseif($action == "enable")
  {
    if(Tools::getValue('custom') == 'true') {
      
     $link = new MegaMenuClass((int)(Tools::getValue('id')));
     $link->active = ($link->active ? 0 : 1);
     $link->update();
             
     $response = array( 'msg' => 'Item status has changed.', 'active' => $link->active);
     // if categories element
    } else {
        $active = null;
        $CATS = Configuration::get('MEGAMENU_CATS');
        $UN_CATS = array();
        $UN_CATS = unserialize($CATS);
      // get serialzie date  
      if( empty( $UN_CATS ) ){
	  	  (is_array($UN_CATS)== false ? $UN_CATS = Array(): '' );
          // add first time if empty
          array_push($UN_CATS, (int)Tools::getValue('id'));
          $SE_CATS = serialize($UN_CATS);
          Configuration::updateValue('MEGAMENU_CATS', $SE_CATS);
          $active = 0; 
                
      } else {
        
        if(in_array(Tools::getValue('id'), $UN_CATS)) {
        
          foreach($UN_CATS as $key => $value){
  
           if($value == Tools::getValue('id') ){
  
              unset( $UN_CATS[$key] );
              $active = 1;           
            } 
          }
        } else {
           $active = 0;
           array_push($UN_CATS, (int)Tools::getValue('id'));
        }
          //corect keys
          $UN_CATS = array_values($UN_CATS);   
          //serialize and save configuration  
          $SE_CATS = serialize($UN_CATS);
          Configuration::updateValue('MEGAMENU_CATS', $SE_CATS);
      }
        $response = array( 'msg' => 'Item status has changed.', 'active' => $active);
      
    }      
     echo Tools::jsonEncode( $response ); 
  }
  elseif($action == "edit")
  {          
    $link = new MegaMenuClass((int)(Tools::getValue('id')), Context::getContext()->cookie->id_lang);
                
     echo Tools::jsonEncode($link); 
  }  
  elseif($action == "add")
  {
      $link = new MegaMenuClass();
     $link->active = 1;    
     $link->parent = (int)(Tools::getValue('parent')); 
     $link->parent_custom = (Tools::getValue('custom') == 'true' ? 1 : 0);
     $link->copyFromPost();
     $link->add();
    
     $response = array('id' => $link->id, 'parent' => $link->parent, 'name' => $link->name[Context::getContext()->cookie->id_lang], 'msg' => 'Menu item added.');
			
     echo Tools::jsonEncode($response);
  } 
  elseif($action == "saveedit")
  {     
     $link = new MegaMenuClass((int)(Tools::getValue('id_megamenu')));
     $link->copyFromPost();
     $link->update(); 
     
     $item =  new MegaMenuClass((int)(Tools::getValue('id_megamenu')), Context::getContext()->cookie->id_lang); 
  
     $response = array( 'msg' => 'Item modified.', 'item' => $item);
              
     echo Tools::jsonEncode($response); 
  }
  elseif($action == "copycms")
  {
     $id_cms = Array( (int)(Tools::getValue('id_cms')) );
     $cmslink[] = null;
     $languages = Language::getLanguages(false);
                 
       foreach ($languages as $language) 
       {
          $cms = CMS::getLinks($language['id_lang'], $id_cms );   
          $cmslink += array('name_'.$language['id_lang'] => $cms[0]['meta_title'], 
                                  'title_'.$language['id_lang'] => $cms[0]['meta_title'],
                                  'url_'.$language['id_lang'] => $cms[0]['link']) ;                                                                                                       
       }        
       if(!$errors){
                          
         $link = new MegaMenuClass();
         $link->active = 1;
         $link->parent = (int)(Tools::getValue('parentid')); 
         $link->parent_custom = (Tools::getValue('custom') == 'true' ? 1 : 0);
         $link->copyFromPost($cmslink);
         $link->add();
         
        $response = array('id' => $link->id, 'parent' => $link->parent, 'name' => $link->name[Context::getContext()->cookie->id_lang], 'msg' => 'Cms item added.');
      
        echo Tools::jsonEncode($response);
      
      } else {
        $error = array('error' => $errors);
        echo Tools::jsonEncode($error);
      }    
            
  } 
  elseif($action == "position")
  {
     $csvAll = explode(",", Tools::getValue('csv'));
     $total = count($csvAll) - 1;
	 
     for ($i=0; $i<$total; $i++){
        $csv = explode("/", $csvAll[$i]);
        Db::getInstance()->Execute('update `'._DB_PREFIX_.'megamenu` set position = '.((float)$csv[2]).', parent = '.$csv[1].', parent_custom = '.($csv[3] == 'true' ? 1 : 0).' where id_megamenu = '.$csv[0]);           
     }
     echo Tools::jsonEncode('Position update.');  
  } 
  elseif($action == "removeimage")
 {
  	$id = Tools::getValue('id');
	
	if (file_exists(dirname(__FILE__)."/images/".$id.'.jpg')) {
        unlink(dirname(__FILE__)."/images/".$id.'.jpg');
    }
  }
  elseif($action == "gettree")
  {    
     $response = null;
     $subs = null;
	 
     if(Tools::getValue('custom') == 'false')
	 {
	 	$cat = new Category( (int)Tools::getValue('id') );
		
		if(version_compare(_PS_VERSION_, '1.5.0.0')  == +1)
			$subs = $this->getSubCategories($cat->id, Context::getContext()->cookie->id_lang, true);
		else
	    	$subs = $cat->getSubCategories($cookie->id_lang, true);
		
	       //get disable categories
	    $CATS = Configuration::get('MEGAMENU_CATS');


	    $UN_CATS = array();
	    $UN_CATS = unserialize($CATS);
     }	     
	 
     $customLinks = MegaMenuClass::getCustomLinks(Tools::getValue('id'), Tools::getValue('custom'), Configuration::get('PS_LANG_DEFAULT'), $id_shop);

	 if(count($subs) > 0 ){
	 	$allLinks = array_merge($subs, $customLinks);
        $this->aasort($allLinks,"position");
	 }  else {
	 	$allLinks = $customLinks;
	 }
	 
	 //echo( print_r($allLinks) );
     if($allLinks)
     {
       foreach ($allLinks as $link)  {
	   
	      if( isset($link['id_category'] )){
	         
		  $children = MegaMenuClass::getNBchildren(false, $link['id_category']);                      
	         $response .= '<li class="'.($children > 0 ? 'parent collapsed' : '').'" data-id="'.$link['id_category'].'" pos="'.$link['position'].'">
	                            	<a class="caption ui-droppable '.(is_array($UN_CATS) && in_array($link['id_category'], $UN_CATS) == true ? 'disable' : 'enable').'" rel="'.$link['id_category'].'">'.$link['name'].'</a>
	                             </li>'; 
	      } else {
	         $children = MegaMenuClass::getNBchildren(true, $link['id_megamenu']); 
	         $response .= '<li class="custom '.($children > 0 ? 'parent collapsed' : '').'" data-id="'.$link['id_megamenu'].'" pos="'.$link['position'].'">
	                        		<span class="custom-img"></span>
	                        		<a class="caption ui-droppable  '.($link['active'] ? 'enable' : 'disable').'" rel="'.$link['id_megamenu'].'">'.$link['name'].'</a>
	                      	  </li>'; 
		  }
       }
     }
     
     echo $response;
  } 
  }
  
}
