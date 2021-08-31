# PHP SEPA XML Generator: Generate SEPA XML to define a payment instructions

![Latest Stable Version](https://img.shields.io/badge/release-v1.3.0-brightgreen.svg)
![License](https://img.shields.io/packagist/l/gomoob/php-pushwoosh.svg) 
[![Donate](https://img.shields.io/static/v1?label=Donate&message=PayPal&color=orange)](https://www.paypal.me/SKientzler/5.00EUR)
![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Stefanius67/Sepa/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Stefanius67/Sepa/?branch=master)
[![codecov](https://codecov.io/gh/Stefanius67/Sepa/branch/master/graph/badge.svg?token=3H5XGT08NC)](https://codecov.io/gh/Stefanius67/Sepa)
 
----------
## New Features

- **Support of Sepa Versions 2.9 and 3.0**
- **Support of ISO 20022 Purpose / CategoryPurpose - Codes**
- **A full documentation on [Github](https://github.com/Stefanius67/Sepa/wiki)**


## Overview

The SEPA (**S**ingle **E**uro **P**ayment **A**rea) is a system of transactions created by the EU (European Union) to harmonize the cashless payments within the EU countries.
This package supplies the two main transactions provided by the SEPA-System:
### Direct Debit Transaction
Get Payments from partners/customers/members issued an 'SEPA mandate'. The SEPA-mandate contains complete Bank Account Details:
- Name of financial institute
- IBAN (**I**nternational **B**ank **A**ccount **N**umber)
- BIC (**B**usiness **I**dentifier **C**ode)
- Mandate-ID (internal generated unique ID – have to be re assigned to a customer (…) in case he has changed bank account!)
- Date, the owner of the account has authorized and signed the Mandate.

### Credit Transfer Transaction
Dispose payments to any partner. To initiate a credit transfer transaction, no SEPA-Mandate is needed. Complete bank account information to the recipient is sufficient.
### Preconditions to participate on the SEPA-System
To invoke some SEPA transaction the participants needs:
- Valid bank account (Name of financial institute, IBAN, BIC)
- CI (**C**reditor Scheme **I**dentification)

### Participating countries
> A total of 32 European countries participate in SEPA. In addition to the 27 EU countries, the three countries of the rest of the European Economic Area (EEA) as well as Switzerland and Monaco also participate in SEPA.
**SEPA payments can only be processed in euros.** The SEPA procedure cannot be used for payments in other currencies. A foreign transfer is still required here.
This makes it very clear that SEPA is only made in EURO to the 32 countries. In the 32 countries, the seat of the house bank of the debtors / creditors is decisive.

## Installation   
You can download the  Latest [release version ](https://www.phpclasses.org/package/11537-PHP-Generate-SEPA-XML-to-define-a-payment-instructions.html) from PHPClasses.org

## Usage
*SepaTest* shows simple code to generate a valid SEPA XML-File.

A full documentation can be found on [Github](https://github.com/Stefanius67/Sepa/wiki)

### Specify additional country validation(s)
If the validation for a required country is not yet included in the package, it can be added as described below.
(It would be nice to send new validations to me. So I can integrate them into the package in order that other users can also benefit from - [S.Kientzler@online.de](mailto:S.Kientzler@online.de))

To define country specific validation for IBAN, BIC and CI create a class extending ***SepaCntryValidationBase***
and call ***Sepa::addValidation('CC', 'MyValidationClassName');***

For most of the participating countries, it is sufficient to specify in the constructor the respective length, the formatting rule (RegEx) and the information whether alphanumeric characters are allowed.

If more complicated rules apply in a country, the respective method for validation can be redefined in the extended class in order to map this rule.
(as an example, look at implementation of SepaCntryValidationBE class)
 

```php
class MyValidationClassName extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'CC';	// MUST contain the desired country code
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){11}?$/';
        
        parent::__construct(strtoupper($strCntry));
    }
}
```
**Information on country specific formats can be found on**
IBAN: [ECBS - European Banking Resources](https://www.ecbs.org/iban.htm)|
CI:   [European Payments Council - Creditor Identifier Overview](https://www.europeanpaymentscouncil.eu/sites/default/files/kb/file/2019-09/EPC262-08%20v7.0%20Creditor%20Identifier%20Overview_0.pdf)|

### Translate error messages
In order to receive the error messages of the various validation functions in the desired language, one of the files *sepa_errormsg_de.json* or *sepa_errormsg_en.json* can be used as a template and translated accordingly. The translated messages must then be loaded using the method ***Sepa :: loadErrorMsg ('sepa_errormsg_XX.json')*** 
