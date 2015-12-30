<?php

	/**

	 * Shopping cart brief info

	 */



	$customerEntry = Customer::getAuthedInstance();



	$k=0;

	$cnt = 0;

	$variants = array();

	if(!is_null($customerEntry)){ //taking products from database



		$q = db_phquery("SELECT itemID, Quantity, sample FROM ?#SHOPPING_CARTS_TABLE WHERE customerID=?", $customerEntry->customerID);

		while ($row = db_fetch_assoc($q)){

			

			$q1=db_query("select productID from ".SHOPPING_CART_ITEMS_TABLE." where itemID='".$row["itemID"]."'");

			$r1=db_fetch_row($q1);

			$variants=GetConfigurationByItemId( $row["itemID"] );

			if ($row["sample"]) {
				$quantity = 1;

				$q_sample_price = db_phquery('SELECT sample_price FROM SC_categories WHERE categoryID=(SELECT categoryID FROM SC_products WHERE productID=?)', $r1["productID"]);
				$sample_price = db_fetch_assoc( $q_sample_price );
				$price = $sample_price["sample_price"];
			} else {
				$quantity = $row["Quantity"];
				$price = GetPriceProductWithOption($variants, $r1["productID"])*$quantity;
			}
			
			$k += $price;

			$cnt+=$quantity;

		}

	}elseif(isset($_SESSION["gids"])){ //...session vars

		$dbq_price = 'SELECT Price FROM ?#PRODUCTS_TABLE WHERE productID=?';

		$dbq_custom = 'SELECT price_surplus FROM ?#PRODUCTS_OPTIONS_SET_TABLE WHERE variantID=? AND productID=?';

		//TODO: optimize query

		for ($i=0; $i<count($_SESSION["gids"]); $i++){

			if(!$_SESSION["gids"][$i])continue;

			

			$sum = db_phquery_fetch(DBRFETCH_FIRST,$dbq_price , $_SESSION["gids"][$i]);

			

			foreach( $_SESSION["configurations"][$i] as $var ){

				$sum += db_phquery_fetch(DBRFETCH_FIRST, $dbq_custom, $var, $_SESSION["gids"][$i]);

			}

			if ($_SESSION["sample"][$i]) {
				$quantity = 1;

				$q_sample_price = db_phquery('SELECT sample_price FROM SC_categories WHERE categoryID=(SELECT categoryID FROM SC_products WHERE productID=?)', $_SESSION["gids"][$i]);
				$sample_price = db_fetch_assoc( $q_sample_price );
				$sum = $sample_price["sample_price"];
			} else {
				$quantity = $_SESSION["counts"][$i];
			}
			
			$k += $quantity*$sum;
			
			$cnt += $quantity;

		}

	

	}

	$d = oaGetDiscountValue( cartGetCartContent(), is_null($customerEntry)?null:$customerEntry->Login );

	$k = $k - $d;

	

	$smarty->assign("shopping_cart_value", $k);

	$smarty->assign("shopping_cart_value_shown", show_price($k));

	$smarty->assign("shopping_cart_items", $cnt);

?>