<?php
/**
 * Default Country to Currency Mapping
 * Based on ISO 3166-1 alpha-2 country codes and ISO 4217 currency codes
 * 
 * @package WC_Multi_Currency_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return array(
    // A
    'AD' => array('EUR'), // Andorra
    'AE' => array('AED'), // United Arab Emirates
    'AF' => array('AFN'), // Afghanistan
    'AG' => array('XCD'), // Antigua and Barbuda
    'AI' => array('XCD'), // Anguilla
    'AL' => array('ALL'), // Albania
    'AM' => array('AMD'), // Armenia
    'AO' => array('AOA'), // Angola
    'AQ' => array('USD'), // Antarctica
    'AR' => array('ARS'), // Argentina
    'AS' => array('USD'), // American Samoa
    'AT' => array('EUR'), // Austria
    'AU' => array('AUD'), // Australia
    'AW' => array('AWG'), // Aruba
    'AX' => array('EUR'), // Åland Islands
    'AZ' => array('AZN'), // Azerbaijan
    
    // B
    'BA' => array('BAM'), // Bosnia and Herzegovina
    'BB' => array('BBD'), // Barbados
    'BD' => array('BDT'), // Bangladesh
    'BE' => array('EUR'), // Belgium
    'BF' => array('XOF'), // Burkina Faso
    'BG' => array('BGN'), // Bulgaria
    'BH' => array('BHD'), // Bahrain
    'BI' => array('BIF'), // Burundi
    'BJ' => array('XOF'), // Benin
    'BL' => array('EUR'), // Saint Barthélemy
    'BM' => array('BMD'), // Bermuda
    'BN' => array('BND'), // Brunei
    'BO' => array('BOB'), // Bolivia
    'BQ' => array('USD'), // Caribbean Netherlands
    'BR' => array('BRL'), // Brazil
    'BS' => array('BSD'), // Bahamas
    'BT' => array('BTN', 'INR'), // Bhutan (both currencies accepted)
    'BV' => array('NOK'), // Bouvet Island
    'BW' => array('BWP'), // Botswana
    'BY' => array('BYN'), // Belarus
    'BZ' => array('BZD'), // Belize
    
    // C
    'CA' => array('CAD'), // Canada
    'CC' => array('AUD'), // Cocos Islands
    'CD' => array('CDF'), // Democratic Republic of Congo
    'CF' => array('XAF'), // Central African Republic
    'CG' => array('XAF'), // Republic of Congo
    'CH' => array('CHF'), // Switzerland
    'CI' => array('XOF'), // Côte d'Ivoire
    'CK' => array('NZD'), // Cook Islands
    'CL' => array('CLP'), // Chile
    'CM' => array('XAF'), // Cameroon
    'CN' => array('CNY'), // China
    'CO' => array('COP'), // Colombia
    'CR' => array('CRC'), // Costa Rica
    'CU' => array('CUP'), // Cuba
    'CV' => array('CVE'), // Cape Verde
    'CW' => array('ANG'), // Curaçao
    'CX' => array('AUD'), // Christmas Island
    'CY' => array('EUR'), // Cyprus
    'CZ' => array('CZK'), // Czech Republic
    
    // D
    'DE' => array('EUR'), // Germany
    'DJ' => array('DJF'), // Djibouti
    'DK' => array('DKK'), // Denmark
    'DM' => array('XCD'), // Dominica
    'DO' => array('DOP'), // Dominican Republic
    'DZ' => array('DZD'), // Algeria
    
    // E
    'EC' => array('USD'), // Ecuador
    'EE' => array('EUR'), // Estonia
    'EG' => array('EGP'), // Egypt
    'EH' => array('MAD'), // Western Sahara
    'ER' => array('ERN'), // Eritrea
    'ES' => array('EUR'), // Spain
    'ET' => array('ETB'), // Ethiopia
    
    // F
    'FI' => array('EUR'), // Finland
    'FJ' => array('FJD'), // Fiji
    'FK' => array('FKP'), // Falkland Islands
    'FM' => array('USD'), // Federated States of Micronesia
    'FO' => array('DKK'), // Faroe Islands
    'FR' => array('EUR'), // France
    
    // G
    'GA' => array('XAF'), // Gabon
    'GB' => array('GBP'), // United Kingdom
    'GD' => array('XCD'), // Grenada
    'GE' => array('GEL'), // Georgia
    'GF' => array('EUR'), // French Guiana
    'GG' => array('GBP'), // Guernsey
    'GH' => array('GHS'), // Ghana
    'GI' => array('GIP'), // Gibraltar
    'GL' => array('DKK'), // Greenland
    'GM' => array('GMD'), // Gambia
    'GN' => array('GNF'), // Guinea
    'GP' => array('EUR'), // Guadeloupe
    'GQ' => array('XAF'), // Equatorial Guinea
    'GR' => array('EUR'), // Greece
    'GS' => array('GBP'), // South Georgia and South Sandwich Islands
    'GT' => array('GTQ'), // Guatemala
    'GU' => array('USD'), // Guam
    'GW' => array('XOF'), // Guinea-Bissau
    'GY' => array('GYD'), // Guyana
    
    // H
    'HK' => array('HKD'), // Hong Kong
    'HM' => array('AUD'), // Heard Island and McDonald Islands
    'HN' => array('HNL'), // Honduras
    'HR' => array('EUR'), // Croatia
    'HT' => array('HTG'), // Haiti
    'HU' => array('HUF'), // Hungary
    
    // I
    'ID' => array('IDR'), // Indonesia
    'IE' => array('EUR'), // Ireland
    'IL' => array('ILS'), // Israel
    'IM' => array('GBP'), // Isle of Man
    'IN' => array('INR'), // India
    'IO' => array('USD'), // British Indian Ocean Territory
    'IQ' => array('IQD'), // Iraq
    'IR' => array('IRR'), // Iran
    'IS' => array('ISK'), // Iceland
    'IT' => array('EUR'), // Italy
    
    // J
    'JE' => array('GBP'), // Jersey
    'JM' => array('JMD'), // Jamaica
    'JO' => array('JOD'), // Jordan
    'JP' => array('JPY'), // Japan
    
    // K
    'KE' => array('KES'), // Kenya
    'KG' => array('KGS'), // Kyrgyzstan
    'KH' => array('KHR', 'USD'), // Cambodia (both currencies widely used)
    'KI' => array('AUD'), // Kiribati
    'KM' => array('KMF'), // Comoros
    'KN' => array('XCD'), // Saint Kitts and Nevis
    'KP' => array('KPW'), // North Korea
    'KR' => array('KRW'), // South Korea
    'KW' => array('KWD'), // Kuwait
    'KY' => array('KYD'), // Cayman Islands
    'KZ' => array('KZT'), // Kazakhstan
    
    // L
    'LA' => array('LAK'), // Laos
    'LB' => array('LBP'), // Lebanon
    'LC' => array('XCD'), // Saint Lucia
    'LI' => array('CHF'), // Liechtenstein
    'LK' => array('LKR'), // Sri Lanka
    'LR' => array('LRD'), // Liberia
    'LS' => array('LSL', 'ZAR'), // Lesotho (both currencies accepted)
    'LT' => array('EUR'), // Lithuania
    'LU' => array('EUR'), // Luxembourg
    'LV' => array('EUR'), // Latvia
    'LY' => array('LYD'), // Libya
    
    // M
    'MA' => array('MAD'), // Morocco
    'MC' => array('EUR'), // Monaco
    'MD' => array('MDL'), // Moldova
    'ME' => array('EUR'), // Montenegro
    'MF' => array('EUR'), // Saint Martin
    'MG' => array('MGA'), // Madagascar
    'MH' => array('USD'), // Marshall Islands
    'MK' => array('MKD'), // North Macedonia
    'ML' => array('XOF'), // Mali
    'MM' => array('MMK'), // Myanmar
    'MN' => array('MNT'), // Mongolia
    'MO' => array('MOP'), // Macao
    'MP' => array('USD'), // Northern Mariana Islands
    'MQ' => array('EUR'), // Martinique
    'MR' => array('MRU'), // Mauritania
    'MS' => array('XCD'), // Montserrat
    'MT' => array('EUR'), // Malta
    'MU' => array('MUR'), // Mauritius
    'MV' => array('MVR'), // Maldives
    'MW' => array('MWK'), // Malawi
    'MX' => array('MXN'), // Mexico
    'MY' => array('MYR'), // Malaysia
    'MZ' => array('MZN'), // Mozambique
    
    // N
    'NA' => array('NAD', 'ZAR'), // Namibia (both currencies accepted)
    'NC' => array('XPF'), // New Caledonia
    'NE' => array('XOF'), // Niger
    'NF' => array('AUD'), // Norfolk Island
    'NG' => array('NGN'), // Nigeria
    'NI' => array('NIO'), // Nicaragua
    'NL' => array('EUR'), // Netherlands
    'NO' => array('NOK'), // Norway
    'NP' => array('NPR'), // Nepal
    'NR' => array('AUD'), // Nauru
    'NU' => array('NZD'), // Niue
    'NZ' => array('NZD'), // New Zealand
    
    // O
    'OM' => array('OMR'), // Oman
    
    // P
    'PA' => array('PAB', 'USD'), // Panama (both currencies accepted)
    'PE' => array('PEN'), // Peru
    'PF' => array('XPF'), // French Polynesia
    'PG' => array('PGK'), // Papua New Guinea
    'PH' => array('PHP'), // Philippines
    'PK' => array('PKR'), // Pakistan
    'PL' => array('PLN'), // Poland
    'PM' => array('EUR'), // Saint Pierre and Miquelon
    'PN' => array('NZD'), // Pitcairn Islands
    'PR' => array('USD'), // Puerto Rico
    'PS' => array('ILS', 'JOD'), // Palestine (multiple currencies used)
    'PT' => array('EUR'), // Portugal
    'PW' => array('USD'), // Palau
    'PY' => array('PYG'), // Paraguay
    
    // Q
    'QA' => array('QAR'), // Qatar
    
    // R
    'RE' => array('EUR'), // Réunion
    'RO' => array('RON'), // Romania
    'RS' => array('RSD'), // Serbia
    'RU' => array('RUB'), // Russia
    'RW' => array('RWF'), // Rwanda
    
    // S
    'SA' => array('SAR'), // Saudi Arabia
    'SB' => array('SBD'), // Solomon Islands
    'SC' => array('SCR'), // Seychelles
    'SD' => array('SDG'), // Sudan
    'SE' => array('SEK'), // Sweden
    'SG' => array('SGD'), // Singapore
    'SH' => array('SHP'), // Saint Helena
    'SI' => array('EUR'), // Slovenia
    'SJ' => array('NOK'), // Svalbard and Jan Mayen
    'SK' => array('EUR'), // Slovakia
    'SL' => array('SLE'), // Sierra Leone
    'SM' => array('EUR'), // San Marino
    'SN' => array('XOF'), // Senegal
    'SO' => array('SOS'), // Somalia
    'SR' => array('SRD'), // Suriname
    'SS' => array('SSP'), // South Sudan
    'ST' => array('STN'), // São Tomé and Príncipe
    'SV' => array('USD'), // El Salvador
    'SX' => array('ANG'), // Sint Maarten
    'SY' => array('SYP'), // Syria
    'SZ' => array('SZL', 'ZAR'), // Eswatini (both currencies accepted)
    
    // T
    'TC' => array('USD'), // Turks and Caicos Islands
    'TD' => array('XAF'), // Chad
    'TF' => array('EUR'), // French Southern Territories
    'TG' => array('XOF'), // Togo
    'TH' => array('THB'), // Thailand
    'TJ' => array('TJS'), // Tajikistan
    'TK' => array('NZD'), // Tokelau
    'TL' => array('USD'), // Timor-Leste
    'TM' => array('TMT'), // Turkmenistan
    'TN' => array('TND'), // Tunisia
    'TO' => array('TOP'), // Tonga
    'TR' => array('TRY'), // Turkey
    'TT' => array('TTD'), // Trinidad and Tobago
    'TV' => array('AUD'), // Tuvalu
    'TW' => array('TWD'), // Taiwan
    'TZ' => array('TZS'), // Tanzania
    
    // U
    'UA' => array('UAH'), // Ukraine
    'UG' => array('UGX'), // Uganda
    'UM' => array('USD'), // U.S. Minor Outlying Islands
    'US' => array('USD'), // United States
    'UY' => array('UYU'), // Uruguay
    'UZ' => array('UZS'), // Uzbekistan
    
    // V
    'VA' => array('EUR'), // Vatican City
    'VC' => array('XCD'), // Saint Vincent and the Grenadines
    'VE' => array('VES'), // Venezuela
    'VG' => array('USD'), // British Virgin Islands
    'VI' => array('USD'), // U.S. Virgin Islands
    'VN' => array('VND'), // Vietnam
    'VU' => array('VUV'), // Vanuatu
    
    // W
    'WF' => array('XPF'), // Wallis and Futuna
    'WS' => array('WST'), // Samoa
    
    // Y
    'YE' => array('YER'), // Yemen
    'YT' => array('EUR'), // Mayotte
    
    // Z
    'ZA' => array('ZAR'), // South Africa
    'ZM' => array('ZMW'), // Zambia
    'ZW' => array('ZWL'), // Zimbabwe
);
