<?php
/**
 * Default Country-Currency Mapping
 * Based on ISO 4217 currency codes and ISO 3166-1 alpha-2 country codes
 * 
 * This file provides default mappings that can be customized by admins.
 * When multiple currencies exist for a country, the first one is the primary.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return array(
    // North America
    'US' => array('USD'), // United States
    'CA' => array('CAD'), // Canada
    'MX' => array('MXN'), // Mexico
    'GT' => array('GTQ'), // Guatemala
    'BZ' => array('BZD'), // Belize
    'SV' => array('USD'), // El Salvador
    'HN' => array('HNL'), // Honduras
    'NI' => array('NIO'), // Nicaragua
    'CR' => array('CRC'), // Costa Rica
    'PA' => array('PAB', 'USD'), // Panama (both PAB and USD)
    
    // Europe
    'GB' => array('GBP'), // United Kingdom
    'DE' => array('EUR'), // Germany
    'FR' => array('EUR'), // France
    'IT' => array('EUR'), // Italy
    'ES' => array('EUR'), // Spain
    'NL' => array('EUR'), // Netherlands
    'BE' => array('EUR'), // Belgium
    'AT' => array('EUR'), // Austria
    'FI' => array('EUR'), // Finland
    'IE' => array('EUR'), // Ireland
    'PT' => array('EUR'), // Portugal
    'GR' => array('EUR'), // Greece
    'LU' => array('EUR'), // Luxembourg
    'MT' => array('EUR'), // Malta
    'CY' => array('EUR'), // Cyprus
    'EE' => array('EUR'), // Estonia
    'LV' => array('EUR'), // Latvia
    'LT' => array('EUR'), // Lithuania
    'SK' => array('EUR'), // Slovakia
    'SI' => array('EUR'), // Slovenia
    'CH' => array('CHF'), // Switzerland
    'NO' => array('NOK'), // Norway
    'SE' => array('SEK'), // Sweden
    'DK' => array('DKK'), // Denmark
    'IS' => array('ISK'), // Iceland
    'PL' => array('PLN'), // Poland
    'CZ' => array('CZK'), // Czech Republic
    'HU' => array('HUF'), // Hungary
    'RO' => array('RON'), // Romania
    'BG' => array('BGN'), // Bulgaria
    'HR' => array('EUR'), // Croatia
    'RS' => array('RSD'), // Serbia
    'BA' => array('BAM'), // Bosnia and Herzegovina
    'ME' => array('EUR'), // Montenegro
    'MK' => array('MKD'), // North Macedonia
    'AL' => array('ALL'), // Albania
    'XK' => array('EUR'), // Kosovo
    'MD' => array('MDL'), // Moldova
    'UA' => array('UAH'), // Ukraine
    'BY' => array('BYN'), // Belarus
    'RU' => array('RUB'), // Russia
    'TR' => array('TRY'), // Turkey
    
    // Asia-Pacific
    'CN' => array('CNY'), // China
    'JP' => array('JPY'), // Japan
    'KR' => array('KRW'), // South Korea
    'IN' => array('INR'), // India
    'PK' => array('PKR'), // Pakistan
    'BD' => array('BDT'), // Bangladesh
    'LK' => array('LKR'), // Sri Lanka
    'NP' => array('NPR'), // Nepal
    'BT' => array('BTN', 'INR'), // Bhutan (both BTN and INR)
    'MV' => array('MVR'), // Maldives
    'MM' => array('MMK'), // Myanmar
    'TH' => array('THB'), // Thailand
    'VN' => array('VND'), // Vietnam
    'LA' => array('LAK'), // Laos
    'KH' => array('KHR', 'USD'), // Cambodia (both KHR and USD)
    'MY' => array('MYR'), // Malaysia
    'SG' => array('SGD'), // Singapore
    'ID' => array('IDR'), // Indonesia
    'BN' => array('BND', 'SGD'), // Brunei (both BND and SGD)
    'PH' => array('PHP'), // Philippines
    'TW' => array('TWD'), // Taiwan
    'HK' => array('HKD'), // Hong Kong
    'MO' => array('MOP'), // Macau
    'MN' => array('MNT'), // Mongolia
    'KZ' => array('KZT'), // Kazakhstan
    'KG' => array('KGS'), // Kyrgyzstan
    'TJ' => array('TJS'), // Tajikistan
    'UZ' => array('UZS'), // Uzbekistan
    'TM' => array('TMT'), // Turkmenistan
    'AF' => array('AFN'), // Afghanistan
    'AM' => array('AMD'), // Armenia
    'AZ' => array('AZN'), // Azerbaijan
    'GE' => array('GEL'), // Georgia
    'AU' => array('AUD'), // Australia
    'NZ' => array('NZD'), // New Zealand
    'FJ' => array('FJD'), // Fiji
    'PG' => array('PGK'), // Papua New Guinea
    'SB' => array('SBD'), // Solomon Islands
    'VU' => array('VUV'), // Vanuatu
    'NC' => array('XPF'), // New Caledonia
    'PF' => array('XPF'), // French Polynesia
    'WF' => array('XPF'), // Wallis and Futuna
    'CK' => array('NZD'), // Cook Islands
    'NU' => array('NZD'), // Niue
    'TK' => array('NZD'), // Tokelau
    'TV' => array('AUD'), // Tuvalu
    'NR' => array('AUD'), // Nauru
    'KI' => array('AUD'), // Kiribati
    
    // Middle East
    'SA' => array('SAR'), // Saudi Arabia
    'AE' => array('AED'), // United Arab Emirates
    'QA' => array('QAR'), // Qatar
    'KW' => array('KWD'), // Kuwait
    'BH' => array('BHD'), // Bahrain
    'OM' => array('OMR'), // Oman
    'YE' => array('YER'), // Yemen
    'JO' => array('JOD'), // Jordan
    'LB' => array('LBP'), // Lebanon
    'SY' => array('SYP'), // Syria
    'IQ' => array('IQD'), // Iraq
    'IR' => array('IRR'), // Iran
    'IL' => array('ILS'), // Israel
    'PS' => array('ILS'), // Palestine
    
    // Africa
    'EG' => array('EGP'), // Egypt
    'LY' => array('LYD'), // Libya
    'TN' => array('TND'), // Tunisia
    'DZ' => array('DZD'), // Algeria
    'MA' => array('MAD'), // Morocco
    'SD' => array('SDG'), // Sudan
    'SS' => array('SSP'), // South Sudan
    'ET' => array('ETB'), // Ethiopia
    'ER' => array('ERN'), // Eritrea
    'DJ' => array('DJF'), // Djibouti
    'SO' => array('SOS'), // Somalia
    'KE' => array('KES'), // Kenya
    'UG' => array('UGX'), // Uganda
    'TZ' => array('TZS'), // Tanzania
    'RW' => array('RWF'), // Rwanda
    'BI' => array('BIF'), // Burundi
    'MG' => array('MGA'), // Madagascar
    'MU' => array('MUR'), // Mauritius
    'SC' => array('SCR'), // Seychelles
    'KM' => array('KMF'), // Comoros
    'YT' => array('EUR'), // Mayotte
    'RE' => array('EUR'), // Réunion
    'ZA' => array('ZAR'), // South Africa
    'SZ' => array('SZL', 'ZAR'), // Eswatini (both SZL and ZAR)
    'LS' => array('LSL', 'ZAR'), // Lesotho (both LSL and ZAR)
    'BW' => array('BWP'), // Botswana
    'NA' => array('NAD', 'ZAR'), // Namibia (both NAD and ZAR)
    'ZM' => array('ZMW'), // Zambia
    'ZW' => array('ZWL', 'USD'), // Zimbabwe (both ZWL and USD)
    'MW' => array('MWK'), // Malawi
    'MZ' => array('MZN'), // Mozambique
    'AO' => array('AOA'), // Angola
    'CD' => array('CDF'), // Democratic Republic of the Congo
    'CG' => array('XAF'), // Republic of the Congo
    'CF' => array('XAF'), // Central African Republic
    'TD' => array('XAF'), // Chad
    'CM' => array('XAF'), // Cameroon
    'GQ' => array('XAF'), // Equatorial Guinea
    'GA' => array('XAF'), // Gabon
    'ST' => array('STN'), // São Tomé and Príncipe
    'GH' => array('GHS'), // Ghana
    'TG' => array('XOF'), // Togo
    'BJ' => array('XOF'), // Benin
    'NE' => array('XOF'), // Niger
    'BF' => array('XOF'), // Burkina Faso
    'ML' => array('XOF'), // Mali
    'SN' => array('XOF'), // Senegal
    'MR' => array('MRU'), // Mauritania
    'GM' => array('GMD'), // Gambia
    'GW' => array('XOF'), // Guinea-Bissau
    'GN' => array('GNF'), // Guinea
    'SL' => array('SLL'), // Sierra Leone
    'LR' => array('LRD'), // Liberia
    'CI' => array('XOF'), // Côte d'Ivoire
    'NG' => array('NGN'), // Nigeria
    'CV' => array('CVE'), // Cape Verde
    
    // South America
    'BR' => array('BRL'), // Brazil
    'AR' => array('ARS'), // Argentina
    'CL' => array('CLP'), // Chile
    'PE' => array('PEN'), // Peru
    'BO' => array('BOB'), // Bolivia
    'PY' => array('PYG'), // Paraguay
    'UY' => array('UYU'), // Uruguay
    'CO' => array('COP'), // Colombia
    'VE' => array('VED'), // Venezuela
    'GY' => array('GYD'), // Guyana
    'SR' => array('SRD'), // Suriname
    'GF' => array('EUR'), // French Guiana
    'FK' => array('FKP'), // Falkland Islands
    'GS' => array('GBP'), // South Georgia and the South Sandwich Islands
    
    // Caribbean
    'CU' => array('CUP'), // Cuba
    'JM' => array('JMD'), // Jamaica
    'HT' => array('HTG'), // Haiti
    'DO' => array('DOP'), // Dominican Republic
    'PR' => array('USD'), // Puerto Rico
    'VG' => array('USD'), // British Virgin Islands
    'VI' => array('USD'), // U.S. Virgin Islands
    'AI' => array('XCD'), // Anguilla
    'AG' => array('XCD'), // Antigua and Barbuda
    'DM' => array('XCD'), // Dominica
    'GD' => array('XCD'), // Grenada
    'LC' => array('XCD'), // Saint Lucia
    'VC' => array('XCD'), // Saint Vincent and the Grenadines
    'KN' => array('XCD'), // Saint Kitts and Nevis
    'MS' => array('XCD'), // Montserrat
    'BB' => array('BBD'), // Barbados
    'TT' => array('TTD'), // Trinidad and Tobago
    'KY' => array('KYD'), // Cayman Islands
    'TC' => array('USD'), // Turks and Caicos Islands
    'BS' => array('BSD'), // Bahamas
    'BM' => array('BMD'), // Bermuda
    'CW' => array('ANG'), // Curaçao
    'AW' => array('AWG'), // Aruba
    'SX' => array('ANG'), // Sint Maarten
    'BQ' => array('USD'), // Caribbean Netherlands
    'MF' => array('EUR'), // Saint Martin
    'BL' => array('EUR'), // Saint Barthélemy
    'GP' => array('EUR'), // Guadeloupe
    'MQ' => array('EUR'), // Martinique
    
    // Other Territories and Dependencies
    'GL' => array('DKK'), // Greenland
    'FO' => array('DKK'), // Faroe Islands
    'AX' => array('EUR'), // Åland Islands
    'SJ' => array('NOK'), // Svalbard and Jan Mayen
    'IM' => array('GBP'), // Isle of Man
    'JE' => array('GBP'), // Jersey
    'GG' => array('GBP'), // Guernsey
    'GI' => array('GIP'), // Gibraltar
    'AD' => array('EUR'), // Andorra
    'MC' => array('EUR'), // Monaco
    'SM' => array('EUR'), // San Marino
    'VA' => array('EUR'), // Vatican City
    'LI' => array('CHF'), // Liechtenstein
    'AS' => array('USD'), // American Samoa
    'GU' => array('USD'), // Guam
    'MP' => array('USD'), // Northern Mariana Islands
    'UM' => array('USD'), // United States Minor Outlying Islands
    'IO' => array('USD'), // British Indian Ocean Territory
    'CC' => array('AUD'), // Cocos (Keeling) Islands
    'CX' => array('AUD'), // Christmas Island
    'NF' => array('AUD'), // Norfolk Island
    'HM' => array('AUD'), // Heard Island and McDonald Islands
    'AQ' => array('USD'), // Antarctica
    'TF' => array('EUR'), // French Southern Territories
    'PN' => array('NZD'), // Pitcairn Islands
    'SH' => array('SHP'), // Saint Helena, Ascension and Tristan da Cunha
    'TA' => array('GBP'), // Tristan da Cunha
    'AC' => array('SHP'), // Ascension Island
);
