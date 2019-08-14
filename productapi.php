<?php 
    //set_time_limit(1600);
	ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');
    include_once 'app/Mage.php';
    umask(0);
    Mage::app();


    //get store disabled products
    if($_GET['status'] == 'disabled'){

            $output = array();
            $data = array();
            $attributes = array();

            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

            $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*') // select all attributes
            ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED)); //select only disabled products

            //loop through the list of products to get attribute values
            foreach ($collection as $product) {

                $attributes['name']   = $product->getName();
                $attributes['id']     = $product->getId();
                $attributes['status'] = $product->getStatus();
                
                //build product data array
                $data['product'] = array(
                                    $attributes
                                );

                //push product data array into output
                array_push($output, $output["products"] = $data);

            }
            //header to indicate content type
            header('Content-Type: application/json; charset=utf-8');

            //print disabled products
            print json_encode($output);
            exit();

    }

    if($_GET['attributes'] == 'true'){
    	//get store attributes
	    $attribute = Mage::getModel('catalog/product')->getAttributes();
	    $attributes = array();
	    $data = array();
	    $output = array();

	    foreach($attribute as $a){

	        foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {

	            $attributes[$attributeName] = $attributeName;
	        }
	    }

	    $data['attributes'] = array(
                                   $attributes
                                );

	    //push product data array into output
        array_push($output, $data);

        //header to indicate content type
        header('Content-Type: application/json; charset=utf-8');

	    //print store attributes
        print json_encode($output);
        exit();

    }

    if($_GET['grouped'] == 'true'){

    	$product = Mage::getModel('catalog/product')
				   ->getCollection()
				   ->addAttributeToFilter('type_id', array('eq' => 'grouped'))
				   ->load();

		$parentProductId = array();

		foreach($product as $simpleProducts) {
		  //$simpleProducts->loadParentProductIds();
		  //array_push($parentProductId,$simpleProducts->getParentProductIds());
		  //var_dump($simpleProducts->getParentProductIds());
          $values = $simpleProducts->getValues();
          var_dump($values);
		}
		//var_dump($parentProductId);
		exit();

    }


    if($_GET['bundled'] == 'true'){

    	$product = Mage::getModel('catalog/product')
				   ->getCollection()
				   ->addAttributeToFilter('type_id', array('eq' => 'bundle'))
				   ->load();

		$parentProductId = array();

		foreach($product as $bundleProduct) {
		  //$bundleProduct->loadParentProductIds();
		  //array_push($parentProductId,$bundleProduct->getParentProductIds());
            $values = $bundleProduct->getValues();
            var_dump($values);
		  
		}
		//var_dump($parentProductId);
		exit();

    }


    //get store products
    $products = Mage::getModel('catalog/product')->getCollection();


	$products->addAttributeToSelect('*');

    if(!empty($_GET['lastupdated'])){
        
        //Start Date filter


        //Set start date 
        $fromDate = $_GET['lastupdated'];
        
        //Set end date
        //$toDate = '2040-12-06 11:06:00';

        // Format our dates
        $fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
        //$toDate = date('Y-m-d H:i:s', strtotime($toDate));
         
        //Filter products using date ranges
        //$products->addAttributeToFilter('updated_at', array('from'=>$fromDate, 'to'=>$toDate, 'datetime'=>true));
        $products->addAttributeToFilter('updated_at', array('from'=>$fromDate, 'datetime'=>true));

        //End Date filter
    }

   
    $products->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
 
	
	$visibility = array(
        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
        );

    $products->addAttributeToFilter('visibility', $visibility);
       
    $collection = Mage::getModel('catalog/product')->getCollection();

    $collection->addAttributeToSelect('manufacturer');

    $collection->addFieldToFilter(array(
        array('attribute' => 'manufacturer', 'eq' =>$designer_id),
       ));
		
	$products->load(); 

	$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

    $collection = Mage::getResourceModel('catalog/product_collection');


    if (count($collection)){ 

        $output  = array();

        foreach ($products as $product){

            $attributes = array();

            foreach ($product->getdata() as $key => $value) {

             	if ($key!=='stock_item') {
                 	
                 	$url = $product->getProductUrl();
                 	 if (($key == 'url_path') || ($key =='url_key')){ 
                     	 $value = $url;
                     	 $value = str_replace('/productapi.php','',$value);
                         $value = trim ($value);
                 	 } 
                 	
                 	if ($key == 'image'){ 
                     	 $value = $baseUrl."media/catalog/product".$value;
                 	 }
                 	 
                 	 if ($key == 'thumbnail'){ 
                     	 $value = $baseUrl."media/catalog/product".$value;
                 	 }
                 	
                 	 if ($key == 'manufacturer'){ 
                     	 $value = $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product);
                 	 }
                 	 if ($key == 'brand'){ 
                     	 $value = $product->getResource()->getAttribute('brand')->getFrontend()->getValue($product);
                 	 }
                
                 	 
                    $attributes[$key] = $value;
             		
             	}
             	
             	
            }

          
			$product_options = array();
			$opts = array();
            //get product options
			$options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);

            if($options){

                foreach ($options as $option) {

                    //get product option values
                    $values = $option->getValues();

                    if($values){
                         foreach ($values as $v) {
                            foreach ($v->getData() as $key => $value) {
                                $product_options[$key] = $value;
                                $product_options['name'] = $option->getDefaultTitle();
                                $product_options['optiontype'] = $option->getType();
                            }
                            array_push($opts, $product_options);
                        }
                    }
                
                }

            }


            $categories = $product->getCategoryIds();
            $product    = array();

            	foreach($categories as $k => $_category_id){ 

                        $_category = Mage::getModel('catalog/category')->load($_category_id);
                        $cat_name  = $_category->getName();
                        $cat_url   =  $_category->getUrl();
                        
                        //build product data array
                        $product['product'] = array(
                                            $attributes,
                                            array("options" => $opts),
                                            array("category" => array('name' => $cat_name, 'url' => $cat_url))
                                        );

                        //push product data array into output
                        array_push($output, $output["products"] = $product);
                        

                } 
         

         }//endforeach;
  
}//endif;

//header to indicate content type
header('Content-Type: application/json; charset=utf-8');

//print products
print json_encode($output);




?> 