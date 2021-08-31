<?php
namespace SKien\Sepa;

/**
 * Helper trait containing some methods used by multiple classes in package
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 * @internal
 */
trait SepaHelper
{
    /**
     * Check for valid type and trigger error in case of invalid type.
     * @param string $type
     * @return bool
     */
    protected function isValidType(string $type) : bool
    {
        if ($type != Sepa::CCT && $type != Sepa::CDD) {
            trigger_error('invalid type for ' . get_class($this), E_USER_ERROR);
        }
        return true;
    }

    /**
     * Create unique ID.
     * Format: 99999999-9999-9999-999999999999
     * @return string
     */
    public static function createUID() : string
    {
        mt_srand((int)microtime(true) * 10000);
        $charid = strtoupper(md5(uniqid((string)rand(), true)));
        $uuid = substr($charid, 0, 8) . chr(45) .
                substr($charid, 8, 4) . chr(45) .
                substr($charid, 12, 4) . chr(45) .
                substr($charid, 16, 12);

        return $uuid;
    }

    /**
     * Convert input to valid SEPA string.
     * <ol>
     *      <li>replacement of special chars</li>
     *      <li>limitation to supported chars dependend on validation type</li>
     *      <li>restriction to max length dependend on validation type</li>
     * </ol>
     *
     * Validation Types: <ul>
     *      <li>SepaHelper::MAX35:</li>
     *      <li>SepaHelper::MAX70:</li>
     *      <li>SepaHelper::MAX140:</li>
     *      <li>SepaHelper::MAX1025:
     *          <ul>
     *              <li>max length = MAX[xxx]</li>
     *              <li>supported chars: A...Z, a...z, 0...9, blank, dot, comma, plus, minus, slash, questionmark, colon, open/closing bracket</li>
     *          </ul></li>
     *      <li>SepaHelper::ID1:
     *          <ul>
     *              <li>max length = 35</li>
     *              <li>supported chars: A...Z, a...z, 0...9, blank, dot, comma, plus, minus, slash</li>
     *          </ul></li>
     *      <li>SepaHelper::ID2:
     *          <ul>
     *              <li>max length = 35</li>
     *              <li>supported chars: ID1 without blank</li>
     *          </ul></li>
     * </ul>
     *
     * @param string $str   string to validate
     * @param int $iType    type of validation: one of SepaHelper::MAX35, SepaHelper::MAX70, SepaHelper::MAX140, SepaHelper::MAX1025, SepaHelper::ID1, SepaHelper::ID2
     * @return string
     */
    public static function validString(string $str, int $iType) : string
    {
        // replace specialchars...
        $strValid = self::replaceSpecialChars($str);

        // regular expresion for 'standard' types MAXxxx
        $strRegEx = '/[^A-Za-z0-9 \.,\-\/\+():?]/'; // A...Z, a...z, 0...9, blank, dot, comma plus, minus, slash, questionmark, colon, open/closing bracket
        $strReplace = ' ';
        $iMaxLen = 1025;
        switch ($iType) {
            case Sepa::ID1:
                $iMaxLen = 35;
                $strRegEx = '/[^A-Za-z0-9 \.,\+\-\/]/'; // A...Z, a...z, 0...9, blank, dot, comma plus, minus, slash
                $strReplace = '';
                break;
            case Sepa::ID2:
                $iMaxLen = 35;
                $strRegEx = '/[^A-Za-z0-9\.,\+\-\/]/'; // same as ID1 except blank...
                $strReplace = '';
                break;
            case Sepa::MAX35:
                $iMaxLen = 35;
                break;
            case Sepa::MAX70:
                $iMaxLen = 70;
                break;
            case Sepa::MAX140:
                $iMaxLen = 140;
                break;
            case Sepa::MAX1025:
            default:
                break;
        }

        $strValid = preg_replace($strRegEx, $strReplace, $strValid);
        return substr($strValid ?? '', 0, $iMaxLen);
    }

    /**
     * Replace some special chars with nearest equivalent.
     * - umlauts, acute, circumflex, ...
     * - square/curly brackets
     * - underscore, at
     * @param string $str text to process
     * @return string
     */
    public static function replaceSpecialChars(string $str) : string
    {
        $strReplaced = '';
        if (strlen($str) > 0) {
            // replace known special chars
            $aSpecialChars = array(
                'á' => 'a', 'à' => 'a', 'ä' => 'ae', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae',
                'Á' => 'A', 'À' => 'A', 'Ä' => 'Ae', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Æ' => 'AE',
                'ç' => 'c', 'Ç' => 'C',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'ñ' => 'n', 'Ñ' => 'N',
                'ó' => 'o', 'ò' => 'o', 'ö' => 'oe', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'œ' => 'oe',
                'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'Oe', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O', 'Œ' => 'OE',
                'ß' => 'ss', 'š' => 's', 'Š' => 'S',
                'ú' => 'u', 'ù' => 'u', 'ü' => 'ue', 'û' => 'u',
                'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'Ue', 'Û' => 'U',
                'ý' => 'y', 'ÿ' => 'y', 'Ý' => 'Y', 'Ÿ' => 'Y',
                'ž' => 'z', 'Ž' => 'Z',
                '[' => '(', ']' => ')', '{' => '(', '}' => ')',
                '_' => '-', '@' => '(at)', '€' => 'EUR'
            );

            $strReplaced = strtr($str, $aSpecialChars);
        }
        return $strReplaced;
    }

    /**
     * Calculates valid delayed date from given date considering SEPA businessdays.
     * @param int $iDaysDelay   min count of days the payment delays
     * @param int $dtRequested  requested date (unix timestamp)
     * @return int unix timestamp
     */
    public static function calcDelayedDate(int $iDaysDelay, int $dtRequested = 0) : int
    {
        $dtEarliest = time();

        // FORWARD: should daytime ( < 08:30 / < 18:30 ) bear in mind ?
        $iBDays = 0;
        while ($iBDays < $iDaysDelay) {
            $dtEarliest += 86400; // add day ( 24 * 60 * 60 );
            if (self::isTarget2Day($dtEarliest)) {
                $iBDays++;
            }
        }
        return $dtEarliest > $dtRequested ? $dtEarliest : $dtRequested;
    }

    /**
     * Check for target2-Day (Sepa-Businessday).
     * Mo...Fr and NOT TARGET1-Day
     * TARGET1 Days:
     *  - New Year
     *  - Good Day
     *  - Easter Monday
     *  - 1'st May
     *  - 1.Christmas
     *  - 2.Christmas
     * @todo change to dynamic calculation of eastern and remove $aTarget2 - array
     * @param int $dt  unix timestamp to check
     * @return bool
     */
    public static function isTarget2Day(int $dt) : bool
    {
        $iWeekDay = date('N', $dt);

        //  New Year        Good Day     Easter Monday  1'stMay      1.Christmas   2.Christmas
        $aTarget2 = array(
            '2019-01-01', '2019-04-18', '2019-04-21', '2019-05-01', '2019-12-25', '2019-12-26',
            '2020-01-01', '2020-04-10', '2020-04-13', '2020-05-01', '2020-12-25', '2020-12-26',
            '2021-01-01', '2021-04-02', '2021-04-05', '2021-05-01', '2021-12-25', '2021-12-26',
            '2022-01-01', '2022-04-15', '2022-04-18', '2022-05-01', '2022-12-25', '2022-12-26',
            '2023-01-01', '2023-04-07', '2023-04-10', '2023-05-01', '2023-12-25', '2023-12-26',
            '2024-01-01', '2024-03-29', '2024-04-01', '2024-05-01', '2024-12-25', '2024-12-26',
            '2025-01-01', '2025-04-18', '2025-04-21', '2025-05-01', '2025-12-25', '2025-12-26',
        );
        return !($iWeekDay > 5 || in_array(date('Y-m-d', $dt), $aTarget2));
    }
}
