<?php
namespace SKien\Sepa;

/**
 * Package to create sepa-xml File.
 *
 * #### Providing
 * <ul>
 * <li> Credit Transfer Initiation (CCT; pain.001.002.03.xsd) </li>
 * <li> Direct Debit Initiation (CDD; pain.008.002.02.xsd) </li>
 * </ul>
 *
 * #### Main class of the package
 * This class containins some global constants, support for country specific
 * validation of IBAN / BIC / CI and language support for the generated
 * error messages.
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class Sepa
{
    use SepaHelper;

    /** Version 2.6 (2012: pain.001.002.03 / pain.008.002.02)*/
    const V26 = "2.6";
    /** Version 2.9 (2015: pain.001.003.03 / pain.008.003.02)*/
    const V29 = "2.9";
    /** Version 3.0 (2016: pain.001.001.03 / pain.008.001.02)*/
    const V30 = "3.0";

    /** Credit Transfer Transaction  */
    const CCT = "TRF";
    /** Direct Debit Transaction     */
    const CDD = "DD";

    /** ID1 validation
     * @see SepaHelper::validString()   */
    const ID1     = 1;
    /** ID2 validation
     * @see SepaHelper::validString()   */
    const ID2     = 2;
    /** MAX35 validation
     * @see SepaHelper::validString()   */
    const MAX35   = 3;
    /** MAX70 validation
     * @see SepaHelper::validString()   */
    const MAX70   = 4;
    /** MAX140 validation
     * @see SepaHelper::validString()   */
    const MAX140  = 5;
    /** MAX1025 validation
     * @see SepaHelper::validString()   */
    const MAX1025 = 6;

    /** sequence type first dd sequence     */
    const SEQ_FIRST     = "FRST";
    /** sequence type recurrent dd sequence */
    const SEQ_RECURRENT = "RCUR";
    /** sequence type one-off dd sequence   */
    const SEQ_ONE_OFF   = "OOFF";
    /** sequence type final dd sequence */
    const SEQ_FINAL     = "FNAL";

    /** full validation  */
    const V_FULL_VALIDATION         = 0;
    /** no validation at all   */
    const V_NO_VALIDATION           = 0x001F;
    /** no validation of IBAN   */
    const V_NO_IBAN_VALIDATION      = 0x0001;
    /** no validation of the BIC   */
    const V_NO_BIC_VALIDATION       = 0x0002;
    /** no validation of the CI   */
    const V_NO_CI_VALIDATION        = 0x0004;
    /** no validation if no class set for country  */
    const V_IGNORE_MISSING_CNTRY    = 0x0008;
    /** ignore missing mandatory value   */
    const V_IGNORE_MISSING_VALUE    = 0x0010;

    /** validation succeeded    */
    const OK                        = 0;
    // error codes for IBAN validation
    /** invalid country code   */
    const ERR_IBAN_INVALID_CNTRY    = 1;
    /** invalid length  */
    const ERR_IBAN_INVALID_LENGTH   = 2;
    /** iban contains invalid sign(s)   */
    const ERR_IBAN_INVALID_SIGN     = 3;
    /** wrong checksum  */
    const ERR_IBAN_CHECKSUM         = 4;

    // error codes for BIC validation
    /** invalid BIC */
    const ERR_BIC_INVALID           = 10;
    /** invalid country code   */
    const ERR_BIC_INVALID_CNTRY     = 11;

    // error codes for CI validation
    /** invalid country code  */
    const ERR_CI_INVALID_CNTRY      = 20;
    /** invalid length  */
    const ERR_CI_INVALID_LENGTH     = 21;
    /** iban contains invalid sign(s)   */
    const ERR_CI_INVALID_SIGN       = 22;
    /** wrong checksum  */
    const ERR_CI_CHECKSUM           = 23;

    // error codes for payment info validation
    const ERR_PMT_NAME_MISSING      = 0x0001;
    const ERR_PMT_IBAN_MISSING      = 0x0002;
    const ERR_PMT_BIC_MISSING       = 0x0004;
    const ERR_PMT_CI_MISSING        = 0x0008;
    const ERR_PMT_INVALID_IBAN      = 0x0010;
    const ERR_PMT_INVALID_BIC       = 0x0020;
    const ERR_PMT_INVALID_CI        = 0x0040;
    const ERR_PMT_SEQ_TYPE_MISSING  = 0x0080;
    const ERR_PMT_INVALID_SEQ_TYPE  = 0x0100;
    const ERR_PMT_MAX               = 0x0100;

    // error codes for transaction validation
    const ERR_TX_NAME_MISSING       = 0x0001;
    const ERR_TX_IBAN_MISSING       = 0x0002;
    const ERR_TX_BIC_MISSING        = 0x0004;
    const ERR_TX_INVALID_IBAN       = 0x0010;
    const ERR_TX_INVALID_BIC        = 0x0020;
    const ERR_TX_MAND_ID_MISSING    = 0x0200;
    const ERR_TX_MAND_DOS_MISSING   = 0x0400;
    const ERR_TX_DESCR_MISSING      = 0x0800;
    const ERR_TX_ZERO_VALUE         = 0x1000;
    const ERR_TX_INVALID_TYPE       = 0x2000;
    const ERR_TX_INVALID_MAND_DOS   = 0x4000;
    const ERR_TX_MAX                = 0x4000;

    /** @var array<string>  validation classes for different countries     */
    static protected array $aValidation = array();
    /** @var int set the validation level. Any combination of the self::V_... flags (default: V_FULL)    */
    static protected int $wValidation = 0;

    /** @var array<string> error messages for IBAN validation   */
    static protected array $aIBANError = array();
    /** @var array<string> error messages for BIC validation   */
    static protected array $aBICError = array();
    /** @var array<string> error messages for CI validation   */
    static protected array $aCIError = array();
    /** @var array<string> error messages for payment info validation   */
    static protected array $aPmtInfError = array();
    /** @var array<string> error messages for transaction info validation   */
    static protected array $aTxInfError = array();

    /**
     * Get the pain version for requested SEPA version and -type.
     * The pain version is needed to bind the SEPA XML document to the correct namespace and
     * can be used to validate any sepa document against the corrresponding XSD schema.
     * @param string $strType
     * @param string $strSepaVersion
     * @return string
     */
    public static function getPainVersion(string $strType, string $strSepaVersion = Sepa::V30) : string
    {
        $aPainVersion = [
            self::V26 => [
                self::CCT => 'pain.001.002.03',
                self::CDD => 'pain.008.002.02',
                'year' => '2012',
            ],
            self::V29 => [
                self::CCT => 'pain.001.003.03',
                self::CDD => 'pain.008.003.02',
                'year' => '2015',
            ],
            self::V30 => [
                self::CCT => 'pain.001.001.03',
                self::CDD => 'pain.008.001.02',
                'year' => '2016',
            ],
        ];
        if (!isset($aPainVersion[$strSepaVersion])) {
            trigger_error('Not supported SEPA Version: ' . $strSepaVersion . ' (Supported versions: ' . implode(', ', array_keys($aPainVersion)) . ')!', E_USER_ERROR);
        }
        return $aPainVersion[$strSepaVersion][$strType];
    }

    /**
     * Initializition of the package.
     * This static method must be called before using the package. <br/>
     * All available country validations are added and the errormessages are
     * initialized in english language.
     */
    public static function init() : void
    {
        if (count(self::$aValidation) > 0) {
            return;
        }
        self::addValidation('DE', 'SKien\Sepa\CntryValidation\SepaCntryValidationDE');
        self::addValidation('CH', 'SKien\Sepa\CntryValidation\SepaCntryValidationCH');
        self::addValidation('FR', 'SKien\Sepa\CntryValidation\SepaCntryValidationFR');
        self::addValidation('AT', 'SKien\Sepa\CntryValidation\SepaCntryValidationAT');
        self::addValidation('LU', 'SKien\Sepa\CntryValidation\SepaCntryValidationLU');
        self::addValidation('BE', 'SKien\Sepa\CntryValidation\SepaCntryValidationBE');
        self::addValidation('GB', 'SKien\Sepa\CntryValidation\SepaCntryValidationGB');
        self::addValidation('EE', 'SKien\Sepa\CntryValidation\SepaCntryValidationEE');
        self::addValidation('IT', 'SKien\Sepa\CntryValidation\SepaCntryValidationIT');
        self::addValidation('ES', 'SKien\Sepa\CntryValidation\SepaCntryValidationES');

        self::$aIBANError = array(
            Sepa::ERR_IBAN_INVALID_CNTRY   => 'The country code of the IBAN is not supported!',
            Sepa::ERR_IBAN_INVALID_LENGTH  => 'Invalid length of the IBAN!',
            Sepa::ERR_IBAN_INVALID_SIGN    => 'The IBAN contains invalid characters!',
            Sepa::ERR_IBAN_CHECKSUM        => 'Invalid IBAN checksum!',
        );

        self::$aBICError = array(
            Sepa::ERR_BIC_INVALID          => 'Invalid BIC!',
            Sepa::ERR_BIC_INVALID_CNTRY    => 'The country code of the BIC is not supported!',
        );

        self::$aCIError = array(
            Sepa::ERR_CI_INVALID_CNTRY     => 'The country code of the CI is not supported!',
            Sepa::ERR_CI_INVALID_LENGTH    => 'Invalid length of the CI!',
            Sepa::ERR_CI_INVALID_SIGN      => 'The CI contains invalid characters!',
            Sepa::ERR_CI_CHECKSUM          => 'Invalid CI checksum!',
        );

        self::$aPmtInfError = array(
            Sepa::ERR_PMT_NAME_MISSING      => 'Name missing',
            Sepa::ERR_PMT_IBAN_MISSING      => 'IBAN missing',
            Sepa::ERR_PMT_BIC_MISSING       => 'BIC missing',
            Sepa::ERR_PMT_CI_MISSING        => 'CI missing',
            Sepa::ERR_PMT_INVALID_IBAN      => 'Invalid IBAN',
            Sepa::ERR_PMT_INVALID_BIC       => 'Invalid BIC',
            Sepa::ERR_PMT_INVALID_CI        => 'Invalid CI',
            Sepa::ERR_PMT_SEQ_TYPE_MISSING  => 'Sequence type missing',
            Sepa::ERR_PMT_INVALID_SEQ_TYPE  => 'Invalid sequence type',
        );

        self::$aTxInfError = array(
            Sepa::ERR_TX_NAME_MISSING       => 'Name missing',
            Sepa::ERR_TX_IBAN_MISSING       => 'IBAN missing',
            Sepa::ERR_TX_BIC_MISSING        => 'BIC missing',
            Sepa::ERR_TX_INVALID_IBAN       => 'Invalid IBAN',
            Sepa::ERR_TX_INVALID_BIC        => 'Invalid BIC',
            Sepa::ERR_TX_MAND_ID_MISSING    => 'SEPA mandate missing',
            Sepa::ERR_TX_MAND_DOS_MISSING   => 'Invalid date of the SEPA mandate',
            Sepa::ERR_TX_DESCR_MISSING      => 'Usage text missing',
            Sepa::ERR_TX_ZERO_VALUE         => 'The value is 0.0 EUR',
            Sepa::ERR_TX_INVALID_TYPE       => 'Invalid transaction type',
            Sepa::ERR_TX_INVALID_MAND_DOS   => 'Invalid date value',
        );
    }

    /**
     * Destroing the static arrays.
     * This method mainly is provided for use in the PHPUnit TestCases to reset the static object!
     * @internal
     */
    public static function reset() : void
    {
        self::$aValidation = array();
        self::$wValidation = 0;
        self::$aIBANError = array();
        self::$aBICError = array();
        self::$aCIError = array();
        self::$aPmtInfError = array();
        self::$aTxInfError = array();
    }

    /**
     * Add validation to the package.
     * The PHP class `$strValidationClass` must implement the `SepaCntryValidation` interface.
     * @param string $strCntry  the 2 digit country code
     * @param string $strValidationClass    name of the PHP class for validation
     */
    public static function addValidation(string $strCntry, string $strValidationClass) : void
    {
        if (isset(self::$aValidation[$strCntry])) {
            trigger_error('validation for cntry ' . $strCntry . ' already defined!', E_USER_ERROR);
        }
        if (!is_subclass_of($strValidationClass, 'SKien\Sepa\CntryValidation\SepaCntryValidation', true)) {
            trigger_error('class ' . $strValidationClass . ' must implement SepaCntryValidation interface!', E_USER_ERROR);
        }
        self::$aValidation[$strCntry] = $strValidationClass;
    }

    /**
     * Set the validation level.
     * This method can be used to disable some or complete validation. <br/>
     * > It is recommended to deactivate the validations only for test purposes or if you can
     * guarantee the validity of all values based on previous checks. <br/>
     * A partial deactivation with `Sepa::V_IGNORE_MISSING_CNTRY` may be useful if you sometimes
     * have to process data from a country for which there is (still) no validation in the package.
     *
     * Supported is any combination of:
     *
     * | Flag                           | Description                               |
     * |--------------------------------|-------------------------------------------|
     * | `Sepa::V_NO_VALIDATION`        | no validation at all (not recommended!)   |
     * | `Sepa::V_NO_IBAN_VALIDATION`   | no validation of IBAN                     |
     * | `Sepa::V_NO_BIC_VALIDATION`    | no validation of the BIC                  |
     * | `Sepa::V_NO_CI_VALIDATION`     | no validation of the CI                   |
     * | `Sepa::V_IGNORE_MISSING_CNTRY` | no validation if no class set for country |
     * | `Sepa::V_IGNORE_MISSING_VALUE` | no error on missing mandatory value       |
     *
     * Default value is full validation:  `Sepa::V_FULL_VALIDATION`
     * @param int $wValidation  see the description
     */
    public static function setValidationLevel(int $wValidation) : void
    {
        self::$wValidation = $wValidation;
    }

    /**
     * Check, if validation level is set.
     * @param int $wValidation
     * @return bool
     * @internal
     */
    public static function checkValidation(int $wValidation) : bool
    {
        return (self::$wValidation & $wValidation) != 0;
    }

    /**
     * Load error messages from JSON file.
     * For different language support, all messages can be loaded from a JSON file.
     * > Use the `sepa_errormsg_en.json` or `sepa_errormsg_de.json` contained in the
     * package as a starting point for your own translation .
     * @param string $strFilename   relative or absolute JSON file
     */
    public static function loadErrorMsg(string $strFilename = 'sepa_error.json') : void
    {
        /*
        // ... testcode to create sample json file
        $aError = array( 'aIBAN' => self::$aIBANError, 'aCI' => self::$aCIError, 'aPmtInf' => self::$aPmtInfError, 'aTxInf' => self::$aTxInfError );
        $strJSON = json_encode($aError, JSON_PRETTY_PRINT);
        file_put_contents($strFilename, $strJSON);
        chmod($strFilename, 0666);
        */
        if (file_exists($strFilename)) {
            $strJson = file_get_contents($strFilename);
            $jsonData = json_decode((string)$strJson, true);
            if ($jsonData && is_array($jsonData)) {
                if (isset($jsonData['aIBAN']) && is_array($jsonData['aIBAN'])) {
                    self::$aIBANError = $jsonData['aIBAN'];
                }
                if (isset($jsonData['aCI']) && is_array($jsonData['aCI'])) {
                    self::$aCIError = $jsonData['aCI'];
                }
                if (isset($jsonData['aPmtInf']) && is_array($jsonData['aPmtInf'])) {
                    self::$aPmtInfError = $jsonData['aPmtInf'];
                }
                if (isset($jsonData['aTxInf']) && is_array($jsonData['aTxInf'])) {
                    self::$aTxInfError = $jsonData['aTxInf'];
                }
            } else {
                trigger_error('invalid error message file: ' . $strFilename, E_USER_ERROR);
            }
        } else {
            trigger_error('error message file ' . $strFilename . ' not exist!', E_USER_ERROR);
        }
    }

    /**
     * Validates given IBAN.
     * If the passed value contains any leading/trailing or formating spaces, they all
     * will be removed.
     * @param string $strIBAN   IBAN to validate
     * @return int Sepa::OK or errorcode
     */
    public static function validateIBAN(string &$strIBAN) : int
    {
        $strIBAN = str_replace(' ', '', trim(strtoupper($strIBAN)));
        if ((self::$wValidation & self::V_NO_IBAN_VALIDATION) != 0) {
            return self::OK;
        }

        if (count(self::$aValidation) == 0) {
            trigger_error('no country validation specified! (possibly forgotten to call Sepa::init()?)', E_USER_ERROR);
        }
        $strCntry = substr($strIBAN, 0, 2);
        if (!isset(self::$aValidation[$strCntry])) {
            if ((self::$wValidation & self::V_IGNORE_MISSING_CNTRY) != 0) {
                return Sepa::OK;
            } else {
                return Sepa::ERR_IBAN_INVALID_CNTRY;
            }
        }
        $strClass = self::$aValidation[$strCntry];
        $oValidate = new $strClass($strCntry);

        return $oValidate->validateIBAN($strIBAN); /** @phpstan-ignore-line */
    }

    /**
     * validates given BIC.
     * If the passed value contains any leading/trailing or formating spaces, they all
     * will be removed. If a 8-digit BIC is passed, it will be expanded to 11 digits using
     * 'XXX'.
     * @param string $strBIC    BIC to validate
     * @return int Sepa::OK or errorcode
     */
    public static function validateBIC(string &$strBIC) : int
    {
        $strBIC = str_replace(' ', '', trim(strtoupper($strBIC)));
        if (strlen($strBIC) == 8) {
            // expand an 8-digit code to 11 digits for validation...
            $strBIC .= 'XXX';
        }
        if ((self::$wValidation & self::V_NO_BIC_VALIDATION) != 0) {
            return self::OK;
        }

        if (count(self::$aValidation) == 0) {
            trigger_error('no country validation specified! (possibly forgotten to call Sepa::init()?)', E_USER_ERROR);
        }
        $strCntry = substr($strBIC, 4, 2);
        if (!isset(self::$aValidation[$strCntry])) {
            if ((self::$wValidation & self::V_IGNORE_MISSING_CNTRY) != 0) {
                return Sepa::OK;
            } else {
                return Sepa::ERR_BIC_INVALID_CNTRY;
            }
        }
        $strClass = self::$aValidation[$strCntry];
        $oValidate = new $strClass($strCntry);

        return $oValidate->validateBIC($strBIC); /** @phpstan-ignore-line */
    }

    /**
     * validates given CI (Creditor Scheme Identification).
     * If the passed value contains any leading/trailing or formating spaces, they all
     * will be removed.
     * @param string $strCI     CI to validate
     * @return int Sepa::OK or errorcode
     */
    public static function validateCI(string &$strCI) : int
    {
        $strCI = str_replace(' ', '', trim(strtoupper($strCI)));
        if ((self::$wValidation & self::V_NO_CI_VALIDATION) != 0) {
            return self::OK;
        }

        if (count(self::$aValidation) == 0) {
            trigger_error('no country validation specified! (possibly forgotten to call Sepa::init()?)', E_USER_ERROR);
        }
        $strCntry = substr($strCI, 0, 2);
        if (!isset(self::$aValidation[$strCntry])) {
            if ((self::$wValidation & self::V_IGNORE_MISSING_CNTRY) != 0) {
                return Sepa::OK;
            } else {
                return Sepa::ERR_CI_INVALID_CNTRY;
            }
        }
        $strClass = self::$aValidation[$strCntry];
        $oValidate = new $strClass($strCntry);

        return $oValidate->validateCI($strCI); /** @phpstan-ignore-line */
    }

    /**
     * Message to the given errorcode for IBAN / BIC / CI validation errors.
     * @param int $iError   the errorcode
     * @return string
     */
    public static function errorMsg(int $iError) : string
    {
        $aError = self::$aIBANError + self::$aBICError + self::$aCIError;
        $strMsg = 'unknown Error (' . $iError . ')!';
        if (isset($aError[$iError])) {
            $strMsg = $aError[$iError];
        }
        return $strMsg;
    }

    /**
     * Message to the given IBAN errorcode.
     * @param int $iError   the errorcode
     * @return string
     * @internal
     */
    public static function errorMsgIBAN(int $iError) : string
    {
        $strMsg = 'unknown Error (' . $iError . ')!';
        if (isset(self::$aIBANError[$iError])) {
            $strMsg = self::$aIBANError[$iError];
        }
        return $strMsg;
    }

    /**
     * Message to the given BIC errorcode.
     * @param int $iError   the errorcode
     * @return string
     * @internal
     */
    public static function errorMsgBIC(int $iError) : string
    {
        $strMsg = 'unknown Error (' . $iError . ')!';
        if (isset(self::$aBICError[$iError])) {
            $strMsg = self::$aBICError[$iError];
        }
        return $strMsg;
    }

    /**
     * Message to the given CI errorcode.
     * @param int $iError   the errorcode
     * @return string
     * @internal
     */
    public static function errorMsgCI(int $iError) : string
    {
        $strMsg = 'unknown Error (' . $iError . ')!';
        if (isset(self::$aCIError[$iError])) {
            $strMsg = self::$aCIError[$iError];
        }
        return $strMsg;
    }

    /**
     * Message to the given payment info errorcode.
     * Used by `SepaPmtInf::getError()` to get localized message.
     * @param int $iError
     * @param string $strLF
     * @return string
     * @internal
     */
    public static function errorMsgPmtInf(int $iError, string $strLF = PHP_EOL) : string
    {
        $strSep = '';
        $strMsg = '';
        for ($iCheck = 0x0001; $iCheck <= Sepa::ERR_PMT_MAX; $iCheck <<= 1) {
            if (($iError & $iCheck) != 0 && isset(self::$aPmtInfError[$iCheck])) {
                $strMsg .= $strSep . self::$aPmtInfError[$iCheck];
                $strSep = $strLF;
            }
        }
        return $strMsg;
    }

    /**
     * Message to the given transaction info errorcode.
     * Used by `SepaTxInf::getError()` to get localized message.
     * @param int $iError
     * @param string $strLF
     * @return string
     * @internal
     */
    public static function errorMsgTxInf(int $iError, string $strLF = PHP_EOL) : string
    {
        $strSep = '';
        $strMsg = '';
        for ($iCheck = 0x0001; $iCheck <= Sepa::ERR_TX_MAX; $iCheck <<= 1) {
            if (($iError & $iCheck) != 0 && isset(self::$aTxInfError[$iCheck])) {
                $strMsg .= $strSep . self::$aTxInfError[$iCheck];
                $strSep = $strLF;
            }
        }
        return $strMsg;
    }
}

