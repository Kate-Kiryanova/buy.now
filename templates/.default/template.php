<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

	use Bitrix\Main\Localization\Loc;
	global $USER;
?>

<div class="modal-layout js-popup-container" data-modal="oneclick">
	<div class="modal-container">
		<div class="app-close-menu closePopup">
			<svg class="close">
				<use xlink:href="#close"/>
			</svg>
		</div>
		<div class="modal-container-header">
			<div class="title h2"><?=Loc::getMessage('BUY_IN_ONE_CLICK')?></div>
		</div>
		<div class="modal-container-content">
			<form class="js-validate form__buy-now" method="post">
				<input type="hidden" name="PRODUCT_ID" value="">
				<input type="hidden" name="PRODUCT_QUANTITY" value="">
				<input type="hidden" name="PRODUCT_PROPERTY" value="">
				<input type="hidden" name="ajax_buy_now" value="Y">

				<p class="annotation small" id="buy-item-now"></p>

				<div class="input-row">
					<div class="input-item w100">
						<div class="input-wrapper">
							<input class="input-main" id="oneclickName" type="text" name="NAME" data-validation="length" data-validation-error-msg="<?=Loc::getMessage('REQUIRED_FIELD')?>" data-validation-length="min1">
							<label class="input-label req" for="#oneclickName"><?=Loc::getMessage('FIO')?></label>
						</div>
					</div>
					<div class="input-item w100">
						<div class="input-wrapper">
							<input class="input-main" id="oneclickPhone" placeholder="+7 495 000-00-00" type="tel" name="PHONE" data-validation="custom" data-validation-regexp="^[-0-9()+ ]+$" data-validation-error-msg="<?=Loc::getMessage('ENTER_CORRECT_PHONE_NUMBER')?>">
							<label class="input-label req" for="#oneclickPhone"><?=Loc::getMessage('TEL')?></label>
						</div>
					</div>
					<div class="input-item w100">
						<div class="input-wrapper">
							<input class="input-main" id="oneclickEmail" type="email" name="EMAIL" data-validation="email" data-validation-error-msg="<?=Loc::getMessage('INCORRECT_PHONE_NUMBER')?>">
							<label class="input-label req" for="#oneclickEmail"><?=Loc::getMessage('EMAIL')?></label>
						</div>
					</div>
					<div class="input-item w100">
						<div class="input-wrapper">
							<textarea class="input-main" type="text" name="MESSAGE" id="oneclickMoreInfo"></textarea>
							<label class="input-label req" for="#oneclickMoreInfo"><?=Loc::getMessage('MESSAGE')?></label>
						</div>
					</div>
					<button class="btn accent" type="submit">
						<span><?=Loc::getMessage('SEND')?></span>
					</button>
				</div>
			</form>
		</div>
	</div>
	<div class="modal-container response" style="display: none;">
		<div class="app-close-menu closePopup">
			<svg class="close">
				<use xlink:href="#close"/>
			</svg>
		</div>
		<div class="modal-container-header">
			<div class="title h2"><?=Loc::getMessage('SPASIBO')?></div>
		</div>
		<div class="modal-container-content">
			<div class="text"><?=Loc::getMessage('THANKS_FOR_ORDER')?></div>
		</div>
	</div>
</div>
