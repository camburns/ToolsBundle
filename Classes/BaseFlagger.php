<?php
/*
* created on: 16/06/2020 at 7:41 PM
* by: cameronrobertburns
*/

namespace VisageFour\Bundle\ToolsBundle\Classes;

use App\Classes\Flaggers\BadgeWorkflowFlagger;
use App\VisageFour\Bundle\ToolsBundle\Classes\FlaggerOptions;
use VisageFour\Bundle\ToolsBundle\Exceptions\FlagOptionDoesNotExistException;

/**
 *
 * A flagger that indicates the "status" of a badge or "what part" of the
 * workflow the badge is currently in (curing construction or handout to attendee)
 *
 * This is a “Flagger” CRC (Custom Reusable Component). Read more here: https://bit.ly/30Iq2ip
 *
 * Class BadgeWorkflowFlagger
 * @package App\Classes\Flaggers
 */
abstract class BaseFlagger extends FlaggerOptions
{

//    abstract public static function populate();
//    {
//       throw new \Exception(
//           'the BaseFlagger::PopulateOptionsAsText() method has not been overridden (it must return'.
//           ' an array with a string of array elements.'
//       );
//    }

//    public static function populate ()
//    {
//        throw new \Exception('the ::populate() method must be overridden in your child class that extends BaseFlagger. Please fix this.');
//    }

    private static function getClassNameIsSet () {
        if (empty(self::getClassName())) {
            throw new \Exception(
                'you must set $className in the populate() method of your '. self::getFlaggerName() .' flagger. Flagger classname: '. __CLASS__
            );
        }
    }

    /**
     * @throws \Exception
     *
     * Check that a text version of the flag is available,
     * if not, throw an exception.
     */
    private static function getFlagAsAString (string $flagValue) {
        self::checkValueIsValid($flagValue);

        return self::getFlagOptions()[$flagValue];
    }

    /**
     * enter in the flag value and return it's string equivalent
     * use $format to configure the appearance of the returned string.
     */
    const RETURN_TEXT_ONLY = 103;                   // just return the flag's text. e.g. "to disassemble"
    const RETURN_TEXT_AND_VALUE_ONLY_STYLE_1 = 105; // just return the flag's text. e.g. "to disassemble (300)"
    public static function getFlagAsString ($flagValue, $format = self::RETURN_TEXT_ONLY) {
        self::checkValueIsValid($flagValue);
        switch ($format) {
            case self::RETURN_TEXT_ONLY:
                $string1 = self::getFlagAsAString($flagValue);
                break;
            case self::RETURN_TEXT_AND_VALUE_ONLY_STYLE_1:
                $string1 = '"'. self::getFlagAsAString($flagValue) .'" ('. $flagValue .')';
                break;
            default:
                throw new \Exception(
                    'you must set $className in the populate() method of your '. self::getFlaggerName() .' flagger. Flagger classname: '. __CLASS__
                );
        }

        return $string1;
    }

    /**
     * Output a string of the flag options, used (for example) in presenting flag options
     * (and the corresponding flag value) when throwing an exception.
     *
     * example return values below:
     * [$format =self::FLAG_NAME_AND_VALUE]:
     * - ???
     * @param int $format
     */
    const FLAG_NAME_AND_VALUE = 106;
    public static function getFlagOptionsAsString ($format = self::FLAG_NAME_AND_VALUE)
    {
        $flagOptions = self::getFlagOptions();
        $string1 = '';
        switch ($format) {
            case self::FLAG_NAME_AND_VALUE:
                $firstLoop = false;
                foreach ($flagOptions as $curValue => $curString) {
                    if (!$firstLoop) {
                        $string1 .= ', ';
                    }
                    $string1 .= $curString .' ('. $curValue .')';
                    $firstLoop = false;
                }
                break;
            default:
                throw new \Exception ('$format: '. $format.' not recognised');
        }

        return $string1;
    }

    protected function stringifyAllFlagOptions ($format = self::FLAG_NAME_AND_VALUE) {
        return self::getFlagOptionsAsString($format);
    }

    /**
     * add a 'flag option'. Using a method (instead of direct array access) prevents flags
     * being loaded with the same value. This isn't normally a problem until you have
     * flagger classes that inherit from other flagger classes and that build on the flagOptions
     * array in two different places (such as happens with the formFlagger where there's generic flags
     * and then more specific flags.)
     *
     * @param int $flagValue
     * @param string $flagString
     */
    protected static function addFlagOption (int $flagValue, string $flagString) {
        if (!empty(self::getFlagOptions()[$flagValue])) {
            throw new \Exception (
                'a flag with the value: '. $flagValue .' ("'. self::getFlagAsString($flagValue) .')'.
                ' already exists, the new flag cannot be added.'
            );
        }

        self::getFlagOptions()[$flagValue] = $flagString;

        return true;
    }

    /**
     * Check the argument against valid flag values
     * if it doesn't exist, return false (or throw exception)
     *
     * @param $flagVal
     */
    public static function checkValueIsValid($flagVal, $throwExceptionOnFail = true)
    {
        if (empty(self::getFlagOptions()[$flagVal])) {
            if ($throwExceptionOnFail) {
                throw new FlagOptionDoesNotExistException(
                    BadgeWorkflowFlagger::class,
                    $flagVal
                );
            } else {
                return false;
            }
        }

        return true;
    }
}