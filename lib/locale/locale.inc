<?php
// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Maintain in language name sort order

$OC_languageAR = array(
	'de' => array(
		'language' => 'Deutsch — German',
		'encoding' => array('de-DE.UTF-8', 'de_DE.UTF-8', 'de.UTF-8', 'de_DE.utf8', 'de_DE', 'de', 'German', 'German_Germany.1252'),
		'direction' => 'ltr',
		'mysql' => 'de_DE'
	),
	'en' => array(
		'language' => 'English',
		'encoding' => array('en-US.UTF-8', 'en_US.UTF-8', 'en.UTF-8', 'en-US', 'en_US', 'en', 'English'),
		'direction' => 'ltr',
		'mysql' => 'en_US'
	),
	'en-GB' => array(
		'language' => 'English (UK)',
		'encoding' => array('en-GB.UTF-8', 'en_GB.UTF-8', 'en_GB.utf8', 'en.UTF-8', 'en-GB', 'en_GB', 'en', 'English'),
		'direction' => 'ltr',
		'mysql' => 'en_GB'
	),
	'es' => array(
		'language' => 'Español — Spanish',
		'encoding' => array('es.UTF-8', 'es_ES.UTF-8', 'es_ES.utf8', 'es.utf8', 'es', 'Spanish', 'Spanish_Spain.1252'),
		'direction' => 'ltr',
		'mysql' => 'es_ES'
	),
	'fr' => array(
		'language' => 'Français — French',
		'encoding' => array('fr-FR.UTF-8', 'fr_FR.UTF-8', 'fr_FR.utf8', 'fr.UTF-8', 'fr_FR', 'fr', 'French', 'French_France.1252'),
		'direction' => 'ltr',
		'mysql' => 'fr_FR'
	),
	'id' => array(
		'language' => 'Indonesian — bahasa Indonesia',
		'encoding' => array('id-ID.UTF-8', 'id_ID.UTF-8', 'id_ID.utf8', 'id.UTF-8', 'id_ID', 'id', 'Indonesian', 'Indonesian_indonesia.1252'),
		'direction' => 'ltr',
		'mysql' => 'id_ID'
	),
	'it' => array(
		'language' => 'Italiano — Italian',
		'encoding' => array('it-IT.UTF-8', 'it_IT.UTF-8', 'it_IT.utf8', 'it.UTF-8', 'it_IT', 'it', 'Italian', 'Italian_Italy.1252'),
		'direction' => 'ltr',
		'mysql' => 'it_IT'
	),
	'hu' => array(
		'language' => 'Magyar - Hungarian',
		'encoding' => array('hu.UTF-8', 'hu_HU.UTF-8', 'hu_HU.utf8', 'hu.utf8', 'hu_HU', 'hu', 'Hungarian', 'Hungarian_Hungary.1250'),
		'direction' => 'ltr',
		'mysql' => 'hu_HU'
	),
	'nl' => array(
		'language' => 'Nederlands — Dutch',
		'encoding' => array('nl-NL.UTF-8', 'nl_NL.UTF-8', 'nl_NL.utf8', 'nl.UTF-8', 'nl_NL', 'nl', 'Dutch', 'Dutch_Netherlands.1252'),
		'direction' => 'ltr',
		'mysql' => 'nl_NL'
	),
	'pt-BR' => array(
		'language' => 'Português (Brasil) — Portuguese (Brazil)',
		'encoding' => array('pt-BR.UTF-8', 'pt_BR.UTF-8', 'pt_BR.utf8', 'pt.UTF-8', 'pt_BR', 'pt', 'portuguese-brazil', 'Portuguese', 'Portuguese_Brazil.1252'),
		'direction' => 'ltr',
		'mysql' => 'pt_BR'
	),
	'tr' => array(
		'language' => 'Türkçe — Turkish',
		'encoding' => array('tr_TR.UTF-8', 'tr_TR.utf8', 'tr.UTF-8', 'tr.utf8', 'tr', 'Turkish', 'Turkish_Turkey.1254'),
		'direction' => 'ltr',
		'mysql' => 'tr_TR'
	),
	'bs' => array(
		'language' => 'босански - Bosnian',
		'encoding' => array('bs-BA.UTF-8', 'bs_BA.UTF-8', 'bs_BA.utf8', 'bs.UTF-8', 'bs_BA', 'bs', 'Bosnian', 'Bosnian_Bosnia and Herzegovina.1250'),
		'direction' => 'ltr',
		'mysql' => 'sr_RS' // MySQL does not define a bs locale
	),
	'ru' => array(
		'language' => 'ру́сский язы́к — Russian',
		'encoding' => array('ru.UTF-8', 'ru_RU.UTF-8', 'ru_RU.utf8', 'ru.utf8', 'ru', 'Russian', 'Russian_Russia.1251'),
		'direction' => 'ltr',
		'mysql' => 'ru_RU'
	),
	'sr-RS' => array(
		'language' => 'српски srpski — Serbian (Cyrillic)',
		'encoding' => array('sr-RS.UTF-8', 'sr_RS.UTF-8', 'sr_RS.utf8', 'sr.UTF-8', 'sr_RS', 'sr', 'sr_Cyrl', 'sr_Cyrl_RS', 'sr_CS', 'serbian-cyrillic', 'Serbian', 'Serbian_Cyrillic', 'Serbian (Cyrillic)_Serbia and Montenegro.1251'),
		'direction' => 'ltr',
		'mysql' => 'sr_RS'
	),
	'ar' => array(
		'language' => 'العربية — Arabic',
		'encoding' => array('ar.UTF-8', 'ar_SA.UTF-8', 'ar-SA.UTF-8', 'ar_SA.utf8', 'ar.utf8', 'ar', 'ar_SA', 'ar-SA', 'Arabic', 'Arabic_Saudi Arabia.1256'),
		'direction' => 'rtl',
		'mysql' => 'ar_SA'
	),
	'fa' => array(
		'language' => 'فارسی — Persian',
		'encoding' => array('fa-IR.UTF-8', 'fa_IR.UTF-8', 'fa_IR.utf8', 'fa.UTF-8', 'fa_IR', 'fa', 'persian-iran', 'Persian', 'Farsi_Iran.1256'),
		'direction' => 'rtl',
		'mysql' => 'fa_IR'
	),
	'zh-Hant' => array(
		'language' => '中文 （台灣）— Chinese (Traditional)',
		'encoding' => array('zh-Hant.UTF-8', 'zh_TW.UTF-8', 'zh-TW.UTF-8', 'zh_TW.utf8', 'zh-Hant', 'zh-TW', 'zh_TW', 'Chinese_Taiwan', 'Chinese_Taiwan.950', 'Chinese-Traditional'),
		'direction' => 'ltr',
		'mysql' => 'zh_TW'
	),
	'zh-Hans' => array(
		'language' => '中文（简体）— Chinese (Simplified)',
		'encoding' => array('zh-Hans.UTF-8', 'zh_CN.UTF-8', 'zh-CN.UTF-8', 'zh_CN.utf8', 'zh-Hans', 'zh-CN', 'zh_CN', 'Chinese_China', 'Chinese_China.936', 'Chinese-Simplified'),
		'direction' => 'ltr',
		'mysql' => 'zh_CN'
	),
	'ja' => array(
		'language' => '日本語 — Japanese',
		'encoding' => array('ja.UTF-8', 'ja_JP.UTF-8', 'ja-JP.UTF-8', 'ja_JP.utf8', 'ja.utf8', 'ja', 'ja_JP', 'Japanese', 'Japanese_Japan.932'),
		'direction' => 'ltr',
		'mysql' => 'ja_JP'
	),
);

