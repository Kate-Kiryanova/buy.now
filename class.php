<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */

/** @global CMain $APPLICATION */

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale,
	Bitrix\Sale\Order,
	Bitrix\Sale\Basket,
	Bitrix\Sale\DiscountCouponsManager,
	Bitrix\Main\Mail\Event;


class BuyProductNow extends CBitrixComponent {
	public $request = array();

	public $nUserID; // id Current USER

	public $objUser; // object new CUser

	public function executeComponent() {
		$this->request = Application::getInstance()->getContext()->getRequest();
		$this->arMessage = array(
			"STATUS" => "N",
			"MESSAGE" => ""
		);
		$this->oneItem = 2;
		$this->controller();
	}

	public function controller() {
		if (
			$this->request->isAjaxRequest() &&
			$this->request->getPost('ajax_buy_now') == 'Y' &&
			$this->request->getPost('PRODUCT_ID') != '' &&
			$this->request->getPost('PRODUCT_QUANTITY') != ''
		) {
			$this->oneItem = 1;
			$this->canBuyItem();
		} else if (
			$this->request->isAjaxRequest() &&
			$this->request->getPost('ajax_buy_now') == 'Y' &&
			$this->request->getPost('PRODUCT_ID') == '' &&
			$this->request->getPost('PRODUCT_QUANTITY') == ''
		) {
			$this->isAuthUser();
		} else {
			$this->IncludeComponentTemplate();
		}
	}

	public function canBuyItem() {
		global $APPLICATION;

		$this->productId = htmlspecialchars($this->request->getPost('PRODUCT_ID'));
		$this->productCanBy = Bitrix\Catalog\ProductTable::isExistProduct($this->productId);

		if ($this->productCanBy) {
			$this->isAuthUser();
		} else {
			$APPLICATION->RestartBuffer();
			$this->arMessage['MESSAGE'] = Loc::getMessage('NOT_CAN_BY');
			$this->returnResult();
		}
	}

	public function isAuthUser() {
		global $USER;
		$this->objUser = $USER;
		$this->nUserID = $USER->GetID();

		if ($this->nUserID) {
			if ($this->oneItem === 1) {
				$this->setOneOrder();
			} else {
				$this->setAllOrder();
			}
		} else {
			$this->isRegisterUser();
		}
	}

	public function isRegisterUser() {
		$this->userEmail = htmlspecialchars($this->request->getPost('EMAIL'));

		$this->arSearchUser = \Bitrix\Main\UserTable::GetList(array(
			'select' => array('ID', 'NAME', 'LOGIN', 'PASSWORD', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHONE'),
			'filter' => array('EMAIL' => $this->userEmail)
		));

		$this->isRegisterUser = false;

		if ( $this->arUser = $this->arSearchUser->fetch() ) {
			$this->isRegisterUser = true;
		}

		if ($this->isRegisterUser) {
			// $this->authUser();
			$this->nUserID = \CSaleUser::GetAnonymousUserID();
			if ($this->oneItem === 1) {
				$this->setOneOrder();
			} else {
				$this->setAllOrder();
			}
		} else {
			$this->registerUser();
		}

	}

	public function registerUser() {
		$fio = htmlspecialchars($this->request->getPost('NAME'));
		$login = htmlspecialchars($this->request->getPost('EMAIL'));
		$password = 'AtR_'.randString(7);

		global $USER;

		if ( $newUser = $USER->Register($login, $fio, '', $password, $password, $login)) {
			$this->nUserID = $newUser['ID'];

			$arUserInfo = CUser::GetByID($this->nUserID)->Fetch();
			$arUserField = array(
				'NAME' => $fio,
				'LOGIN' => $login,
				'PASSWORD' => $password,
				'EMAIL' => $login,
				'CHECKWORD' => $arUserInfo['CHECKWORD'],
				'URL_LOGIN' => urlencode($login)
			);
			Event::send(array(
				"EVENT_NAME" => "USER_INFO",
				"LID" => "s1",
				"C_FIELDS" => $arUserField
			));

			if ($this->oneItem === 1) {
				$this->setOneOrder();
			} else {
				$this->setAllOrder();
			}

		} else {
			global $APPLICATION;
			$APPLICATION->RestartBuffer();
			$this->arMessage['MESSAGE'] = Loc::getMessage('REGISTER_USER_ERROR');
			$this->returnResult();
		}
	}

	// public function authUser() {
	// 	global $USER;
	//
	// 	// $arAuthResult = $USER->Authorize($this->arUser['ID'], true);
	//
	// 	// if ($arAuthResult) {
	// 		// $this->nUserID = $this->arUser['ID'];
	//
	// 		$this->nUserID = \CSaleUser::GetAnonymousUserID();
	// 		AddMessage2Log($this->nUserID);
	//
	// 		if ($this->oneItem === 1) {
	// 			$this->setOneOrder();
	// 		} else {
	// 			$this->setAllOrder();
	// 		}
	// 	// } else {
	// 	// 	global $APPLICATION;
	// 	// 	$APPLICATION->RestartBuffer();
	// 	// 	$this->arMessage['MESSAGE'] = Loc::getMessage('AUTH_USER_ERROR');
	// 	// 	$this->returnResult();
	// 	// }
	// }

	public function setOneOrder() {
		//$this->arProductInfo = CCatalogProduct::GetByIDEx($this->productId);
		// $this->currencyCode = Option::get('sale', 'default_currency', 'BYN');

		$this->basket = Basket::create(\Bitrix\Main\Context::getCurrent()->getSite());
		$this->item = $this->basket->createItem('catalog', $this->productId);
		$this->item->setFields([
			'QUANTITY' => htmlspecialchars($this->request->getPost('PRODUCT_QUANTITY')),
			'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
			'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
			'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
		]);
		$this->order = Order::create(\Bitrix\Main\Context::getCurrent()->getSite(), $this->nUserID, 'BYN');

		$this->order->setPersonTypeId(1);
		$resPrice = $this->item->getField('PRICE') * htmlspecialchars($this->request->getPost('PRODUCT_QUANTITY'));
		$this->order->setField('PRICE', $resPrice);
		$this->order->setField('USER_DESCRIPTION', htmlspecialchars($this->request->getPost('MESSAGE')));

		$this->basket->setOrder($this->order);

		$this->order->doFinalAction(true);
		$this->propertyCollection = $this->order->getPropertyCollection();
		$this->emailProperty = $this->propertyCollection->getItemByOrderPropertyId(2);
		$this->emailProperty->setValue(htmlspecialchars($this->request->getPost('EMAIL')));
		$this->phoneProperty = $this->propertyCollection->getItemByOrderPropertyId(3);
		$this->phoneProperty->setValue(htmlspecialchars($this->request->getPost('PHONE')));
		$this->fioProperty = $this->propertyCollection->getItemByOrderPropertyId(1);
		$this->fioProperty->setValue(htmlspecialchars($this->request->getPost('NAME')));

		$this->order->save();
		$this->basket->save();

		if ($this->order->getId()) {
			$this->doMessage();

			global $APPLICATION;
			$APPLICATION->RestartBuffer();

			$this->arMessage = array(
				"STATUS" => "Y",
				"MESSAGE" => Loc::getMessage('ORDER_TRUE')
			);
		} else {
			$this->arMessage['MESSAGE'] = Loc::getMessage('ORDER_FALSE');
		}

		$this->returnResult();
	}

	public function setAllOrder() {

		$this->basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
		$this->basketItems = $this->basket->getBasketItems();
		$this->order = Order::create(\Bitrix\Main\Context::getCurrent()->getSite(), $this->nUserID, 'BYN');
		$this->order->setPersonTypeId(1);
		$this->order->setField('USER_DESCRIPTION', htmlspecialchars($this->request->getPost('MESSAGE')));
		$this->order->setBasket($this->basket);

		$this->order->save();
		$this->basket->save();

		$this->doMessage();

		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$this->arMessage = array(
			"STATUS" => "Y",
			"MESSAGE" => Loc::getMessage('ORDER_TRUE')
		);
		$this->returnResult();
	}

	public function doMessage() {
		$db = CSaleBasket::GetList(
			array(),
			array(
				'FUSER_ID' => CSaleBasket::GetBasketUserID(),
				"ORDER_ID" => $this->order->getId(),
			),
			false,
			false,
			array('NAME', 'PRICE', 'QUANTITY', 'DETAIL_PAGE_URL', 'MEASURE_NAME', 'PRODUCT_ID', 'ID', 'CURRENCY', 'ORDER_ID')
		);

		$resultTable = '<table>';
		$resultTable .= '<tr><td>'.Loc::getMessage('ORDER_NAME').'</td><td>'.Loc::getMessage('ORDER_COUNT').'</td><td>'.Loc::getMessage('ORDER_RESULT_PRICE').'</td></tr>';

		while ($dbResult = $db->Fetch()) {
			$resultTable .= '<tr><td><a href="'.$dbResult['DETAIL_PAGE_URL'].'">'.$dbResult['NAME'].'</a></td><td>'.$dbResult['QUANTITY'].' '.$dbResult['MEASURE_NAME'].'</td><td>'.$dbResult['QUANTITY'] * $dbResult['PRICE'].' '.$dbResult['CURRENCY'].'</td></tr>';
		}
		$resultTable .= '</table>';

		$arOrder = CSaleOrder::GetByID($this->order->getId());

		$arField = array(
			'ORDER_ID' => $this->order->getId(),
			'ORDER_ACCOUNT_NUMBER_ENCODE' => '',
			'ORDER_REAL_ID' => '',
			'ORDER_DATE' => $arOrder['DATE_INSERT_FORMAT'],
			'ORDER_USER' => $arOrder['USER_NAME'].' '.$arOrder['USER_LAST_NAME'],
			'PRICE' => $arOrder['PRICE'].' '.$arOrder['CURRENCY'],
			'EMAIL' => htmlspecialchars($this->request->getPost('EMAIL')),
			'ORDER_LIST' => '',
			'ORDER_PUBLIC_URL' => '',
			'SALE_EMAIL' => 'e.kiryanova@flex.media',
			'ORDER_TABLE_ITEMS' => $resultTable
		);

		Event::send(array(
			'EVENT_NAME' => 'ATRIUM_SALE_NEW_ORDER',
			'LID' => 's1',
			'C_FIELDS' => $arField
		));
	}

	public function returnResult() {
		echo json_encode($this->arMessage);
		die();
	}
}
