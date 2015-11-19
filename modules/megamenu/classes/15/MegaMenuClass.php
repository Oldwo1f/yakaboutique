<?php
/*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*/


class		MegaMenuClass extends ObjectModel
{
	/** @var integer megamenu id*/
	public		$id;
	
	public $id_shop;
  
    /** @var integer parent id*/
    public    $parent;
  
  
	/** @var string name*/
	public		$name;
  
  	/** @var string url*/
  	public    $url;
  
	/** @var string title*/
	public		$title;

	/** @var integer position */
	public		$position;
  
  	/** @var bool active */
  	public    $active;
  
  	/** @var bool parent_custom */
    public    $parent_custom;
	

	
	public static $definition = array(
		'table' => 'megamenu',
		'primary' => 'id_megamenu',
		'multilang' => true,
		'fields' => array(
			// Lang fields
			'name' =>		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName'),
			'title' =>		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName'),
			'url' =>		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl')
		)
	);
	
  public function getTranslationsFieldsChild()
  {
    parent::validateFieldsLang();

    $fieldsArray = array('name', 'url', 'title');
    $fields = array();
    $languages = Language::getLanguages(false);
    $defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
    foreach ($languages as $language)
    {
      $fields[$language['id_lang']]['id_lang'] = (int)($language['id_lang']);
      $fields[$language['id_lang']][$this->identifier] = (int)($this->id);
      foreach ($fieldsArray as $field)
      {
        if (!Validate::isTableOrIdentifier($field))
          die(Tools::displayError());
        if (isset($this->{$field}[$language['id_lang']]) AND !empty($this->{$field}[$language['id_lang']]))
          $fields[$language['id_lang']][$field] = pSQL($this->{$field}[$language['id_lang']], true);
        elseif (in_array($field, $this->fieldsRequiredLang))
          $fields[$language['id_lang']][$field] = pSQL($this->{$field}[$defaultLanguage], true);
        else
          $fields[$language['id_lang']][$field] = '';
      }
    }
    return $fields;
  }
  
  public function copyFromPost($cmslink = null)
  {
    /* Classical fields */
    foreach ($_GET AS $key => $value)
      if (key_exists($key, $this) AND $key != 'id_'.$this->table)
        $this->{$key} = $value;

    /* Multilingual fields */
    if (sizeof($this->fieldsValidateLang))
    {
      $languages = Language::getLanguages(false);
      foreach ($languages AS $language)
      {
        foreach ($this->fieldsValidateLang AS $field => $validation)
        {
          if($cmslink != null){
            $this->{$field}[(int)($language['id_lang'])] = $cmslink[$field.'_'.(int)($language['id_lang'])];
          }elseif ( Tools::getValue($field.'_'.(int)($language['id_lang'])) ){
            $this->{$field}[(int)($language['id_lang'])] = Tools::getValue($field.'_'.(int)($language['id_lang']));
          }
        }
      }
    }
  }
 
	
	public function getFields()
	{
		    parent::validateFields();
		    $fields['id_megamenu'] = (int)($this->id);
		    $fields['id_shop'] = (int)($this->id_shop);
		    $fields['parent'] = (int)($this->parent);
		    $fields['active'] = (int)($this->active);
		    $fields['position'] = (float)($this->position);
		    $fields['parent_custom'] = (int)($this->parent_custom);

		return $fields;
	}
  

  public function recursiveDelete(&$toDelete, $id_megamenu)
  {
    if (!is_array($toDelete) OR !$id_megamenu)
      die(Tools::displayError());

    $result = Db::getInstance()->ExecuteS('
    SELECT `id_megamenu`
    FROM `'._DB_PREFIX_.'megamenu`
    WHERE `parent` = '.(int)($id_megamenu).' AND parent_custom = 1');
    foreach ($result AS $row)
    {
      $toDelete[] = (int)($row['id_megamenu']);
      $this->recursiveDelete($toDelete, (int)($row['id_megamenu']), 1);
    }
  }
  
  public function delete()
  {
    if ((int)($this->id) === 0 ) return false;

    /* Get childs categories */
    $toDelete = array((int)($this->id));
    $this->recursiveDelete($toDelete, (int)($this->id), (int)($this->parent_custom));
    $toDelete = array_unique($toDelete);

    /* Delete category and its child from database */
    $list = sizeof($toDelete) > 1 ?  implode(',', array_map('intval',$toDelete)) : (int)($this->id);
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'megamenu` WHERE `id_megamenu` IN ('.$list.')');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'megamenu_lang` WHERE `id_megamenu` IN ('.$list.')');

    return true;
  }
 
	public static function getNBchildren($isCustom, $id_parent, $id_shop=0)
	{
	       $children = 0;
		if (version_compare(_PS_VERSION_, '1.5.0.0')  == +1) 
			$id_shop = $id_shop ? $id_shop : Configuration::get('PS_SHOP_DEFAULT');
		
	       if($isCustom == false){
		
			 $children = Db::getInstance()->getValue('
			 				SELECT COUNT(*) FROM `'._DB_PREFIX_.'category` AS c 
			                           LEFT JOIN `'._DB_PREFIX_.'category_shop` AS cs ON cs.id_shop = '.(int)$id_shop.' 
							WHERE c.id_parent = '.(int)($id_parent)
						);
						
	              $children += Db::getInstance()->getValue('
							SELECT COUNT(*) FROM `'._DB_PREFIX_.'megamenu` AS m
		                         	       WHERE m.`parent` = '.(int)($id_parent).' 
	                                		AND m.`parent_custom` = 0 
							AND m.`id_shop` = '.$id_shop
						);
		} else {
					  	
	        	 $children = Db::getInstance()->getValue('
							SELECT COUNT(*) FROM `'._DB_PREFIX_.'megamenu` AS m
							WHERE m.`parent` ='.(int)($id_parent).' 
	                                		AND m.`parent_custom` = 1 
							AND m.`id_megamenu` != '.(int)($id_parent).' 
							AND m.`id_shop` = '.$id_shop  
						);
		}
		
					   
		return $children;
	}
	
	/*get children for 1.5 removed */
	public static function getSubCategories($id_parent, $id_lang, $active = true, $id_shop)
    {       
		
		$sql = '
			SELECT c.*, cl.id_lang, cl.name
			FROM `'._DB_PREFIX_.'category` c
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
				ON (c.`id_category` = cl.`id_category`
				AND `id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
			LEFT JOIN `'._DB_PREFIX_.'category_group` cg
				ON (cg.`id_category` = c.`id_category`)
			LEFT JOIN `'._DB_PREFIX_.'category_shop` cs
				ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$id_shop.')
			WHERE `id_parent` = '.(int)$id_parent.'
				'.($active ? 'AND `active` = 1' : '').'		
			  AND cs.`id_shop` = '.(int)$id_shop;
		
		$sql .= ' 
			GROUP BY c.`id_category`
			ORDER BY `level_depth` ASC, cs.`position` ASC ';
			
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

		return $result;
	}

	public static function getCustomLinks($parent, $isCustom, $id_lang,  $id_shop)
	{
		$id_shop = $id_shop ? $id_shop : Configuration::get('PS_SHOP_DEFAULT');
		
		$result = Db::getInstance()->ExecuteS('
		                       SELECT * FROM `'._DB_PREFIX_.'megamenu` as m
                       	          LEFT JOIN `'._DB_PREFIX_.'megamenu_lang` as ml ON (m.`id_megamenu` = ml.`id_megamenu`)
					   WHERE ml.`id_lang` = '.(int)($id_lang).'  
					   AND m.`id_shop` =  '.(int)$id_shop.'  
					   AND m.`parent_custom` = "'.($isCustom == 'true' ? 1 : 0).'" 
					   AND m.`parent` ='.(int)$parent.' 
					   ORDER BY m.`position`'
			       );
	
		return $result;
	}
}
