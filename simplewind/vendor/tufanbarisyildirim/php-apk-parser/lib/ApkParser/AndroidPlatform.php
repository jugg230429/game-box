<?php

namespace ApkParser;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Android Api Version Codes
define('ANDROID_API_BASE', 1);
define('ANDROID_API_BASE_1_1', 2);
define('ANDROID_API_CUPCAKE', 3);
define('ANDROID_API_DONUT', 4);
define('ANDROID_API_ECLAIR', 5);
define('ANDROID_API_ECLAIR_0_1', 6);
define('ANDROID_API_ECLAIR_MR1', 7);
define('ANDROID_API_FROYO', 8);
define('ANDROID_API_GINGERBREAD', 9);
define('ANDROID_API_GINGERBREAD_MR1', 10);
define('ANDROID_API_HONEYCOMB', 11);
define('ANDROID_API_HONEYCOMB_MR1', 12);
define('ANDROID_API_HONEYCOMB_MR2', 13);
define('ANDROID_API_ICE_CREAM_SANDWICH', 14);
define('ANDROID_API_ICE_CREAM_SANDWICH_MR1', 15);
define('ANDROID_API_ICE_JELLY_BEAN', 16);
define('ANDROID_API_ICE_JELLY_BEAN_MR1', 17);
define('ANDROID_API_ICE_JELLY_BEAN_MR2', 18);
define('ANDROID_API_KITKAT', 19);
define('ANDROID_API_KITKAT_WATCH', 20);
define('ANDROID_API_LOLLIPOP', 21);
define('ANDROID_API_LOLLIPOP_MR1', 22);
define('ANDROID_API_M', 23);
define('ANDROID_API_NOUGAT', 24);
define('ANDROID_API_NOUGAT_MR1', 25);
define('ANDROID_API_OREO', 26);
define('ANDROID_API_OREO_MR1', 27);
define('ANDROID_API_PIE', 28);
define('ANDROID_API_Q', 29);
define('ANDROID_API_R', 30);

/**
 *
 * @property $level
 * @property $versions array
 * @property $url string
 * @property $platform
 */
class AndroidPlatform
{
    private static $platforms = array(
        /**
         * @link http://developer.android.com/guide/topics/manifest/uses-sdk-element.html#ApiLevels
         * @link  http://developer.android.com/about/dashboards/index.html
         */
        0 => array('versions' => array('Undefined'), 'url' => 'Undefined'),
        ANDROID_API_BASE => array('versions' => array('1.0'), 'url' => 'http://developer.android.com/reference/android/os/Build.VERSION_CODES.html#BASE'),
        ANDROID_API_BASE_1_1 => array('versions' => array('1.1'), 'url' => 'http://developer.android.com/about/versions/android-1.1.html'),
        ANDROID_API_CUPCAKE => array('versions' => array('1.5'), 'url' => 'http://developer.android.com/about/versions/android-1.5.html'),
        ANDROID_API_DONUT => array('versions' => array('1.6'), 'url' => 'http://developer.android.com/about/versions/android-1.6.html'),
        ANDROID_API_ECLAIR => array('versions' => array('2.0'), 'url' => 'http://developer.android.com/about/versions/android-2.0.html'),
        ANDROID_API_ECLAIR_0_1 => array('versions' => array('2.0.1'), 'url' => 'http://developer.android.com/about/versions/android-2.0.1.html'),
        ANDROID_API_ECLAIR_MR1 => array('versions' => array('2.1.x'), 'url' => 'http://developer.android.com/about/versions/android-2.1.html'),
        ANDROID_API_FROYO => array('versions' => array('2.2.x'), 'url' => 'http://developer.android.com/about/versions/android-2.2.html'),
        ANDROID_API_GINGERBREAD => array('versions' => array('2.3', '2.3.1', '2.3.2'), 'url' => 'http://developer.android.com/about/versions/android-2.3.html'),
        ANDROID_API_GINGERBREAD_MR1 => array('versions' => array('2.3.3', '2.3.4'), 'url' => 'http://developer.android.com/about/versions/android-2.3.3.html'),
        ANDROID_API_HONEYCOMB => array('versions' => array('3.0.x'), 'url' => 'http://developer.android.com/about/versions/android-3.0.html'),
        ANDROID_API_HONEYCOMB_MR1 => array('versions' => array('3.1.x'), 'url' => 'http://developer.android.com/about/versions/android-3.1.html'),
        ANDROID_API_HONEYCOMB_MR2 => array('versions' => array('3.2'), 'url' => 'http://developer.android.com/about/versions/android-3.2.html'),
        ANDROID_API_ICE_CREAM_SANDWICH => array('versions' => array('4.0', '4.0.1', '4.0.2'), 'url' => 'http://developer.android.com/about/versions/android-4.0.html'),
        ANDROID_API_ICE_CREAM_SANDWICH_MR1 => array('versions' => array('4.0.3', '4.0.4'), 'url' => 'http://developer.android.com/about/versions/android-4.0.3.html'),
        ANDROID_API_ICE_JELLY_BEAN => array('versions' => array('4.1', '4.1.1'), 'url' => 'http://developer.android.com/about/versions/android-4.1.html'),
        ANDROID_API_ICE_JELLY_BEAN_MR1 => array('versions' => array('4.2', '4.2.2'), 'url' => 'http://developer.android.com/about/versions/android-4.2.html'),
        ANDROID_API_ICE_JELLY_BEAN_MR2 => array('versions' => array('4.3'), 'url' => 'http://developer.android.com/about/versions/android-4.3.html'),
        ANDROID_API_KITKAT => array('versions' => array('4.4'), 'url' => 'http://developer.android.com/about/versions/android-4.4.html'),
        ANDROID_API_KITKAT_WATCH => array('versions' => array('4.4W'), 'url' => 'http://developer.android.com/training/building-wearables.html'),
        ANDROID_API_LOLLIPOP => array('versions' => array('5.0'), 'url' => 'http://developer.android.com/about/versions/android-5.0.html'),
        ANDROID_API_LOLLIPOP_MR1 => array('versions' => array('5.1'), 'url' => 'http://developer.android.com/about/versions/android-5.1.html'),
        ANDROID_API_M => array('versions' => array('6.0'), 'url' => 'http://developer.android.com/sdk/api_diff/23/changes.html'),
        ANDROID_API_NOUGAT => array('versions' => array('7.0'), 'url' => 'https://developer.android.com/about/versions/nougat/android-7.0'),
        ANDROID_API_NOUGAT_MR1 => array('versions' => array('7.1'), 'url' => 'https://developer.android.com/about/versions/nougat/android-7.1'),
        ANDROID_API_OREO => array('versions' => array('8.0'), 'url' => 'https://developer.android.com/about/versions/oreo/android-8.0'),
        ANDROID_API_OREO_MR1 => array('versions' => array('8.1'), 'url' => 'https://developer.android.com/about/versions/oreo/android-8.1'),
        ANDROID_API_PIE => array('versions' => array('9.0'), 'url' => 'https://developer.android.com/about/versions/pie/android-9.0'),
        ANDROID_API_Q => array('versions' => array('10.0'), 'url' => 'https://developer.android.com/about/versions/10/features'),
        ANDROID_API_R => array('versions' => array('11.0'), 'url' => 'https://developer.android.com/about/versions/11/features')
    );

    public $level = null;

    /**
     * use a constant with ANDROID_API prefix like ANDROID_API_JELLY_BEAN
     *
     * @param mixed $apiLevel
     * @throws \Exception
     */

    public function __construct($apiLevel)
    {
        if (!isset(self::$platforms[$apiLevel])) {
            throw new \Exception("Unknown Api Level: " . $apiLevel);
        }

        $this->setLevel($apiLevel);
    }

    public static function fromVersion($v)
    {
        foreach (self::$platforms as $apiLevel => $p) {
            if (in_array($v, $p['versions'])) {
                return new self($apiLevel);
            }
        }

        return null;
    }

    public function __get($var)
    {
        switch ($var) {
            case 'platform':
                return 'Android ' . implode(',', self::$platforms[$this->level]['versions']);
                break;
            default:
                return self::$platforms[$this->level][$var];
                break;
        }
    }

    /**
     * @return mixed|null
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed|null $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
