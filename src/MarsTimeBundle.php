<?php
/**
 * MarsTimeBundle
 *
 * Port of essential mars date and time calculations from
 * https://github.com/eelsirhc/pyMarsTime
 * and
 * https://jtauber.github.io/mars-clock/
 * 
 * based all on:
 * Mars Calendar and orbit calculation
 * based on Allison and McEwan (2000), Allison (1997)
 * Allison, M., and M. McEwen 2000. A post-Pathfinder evaluation of aerocentric
 * solar coordinates with improved timing recipes for Mars seasonal/diurnal
 * climate studies. Planet. Space Sci. 48, 215-235
 * Allison, M. 1997. Accurate analytic representations of solar time and seasons
 * on Mars with applications to the Pathfinder/Surveyor missions.
 * Geophys. Res. Lett. 24, 1967-1970.
 * http://www.giss.nasa.gov/tools/mars24/
 * 
 * @copyright  2019 Michael Scheffler
 * @license    https://en.wikipedia.org/wiki/Beerware Beerware License Rev.42
 * @version    Release: 1.0
 * @link       http://www.grafiction.de
 * 
 * Thanks to Chris Lee: https://christopherlee.co.uk/
 */

namespace Grafiction\MarsTimeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarsTimeBundle extends Bundle {
    private static $_maxIterations = 100000; // for custom bisect
    
    private $_autoUpdate;   // update to actual earth time automatically  
    private $_datetime;     // earth time
    private $_longitudeE;   // longitude on mars in degrees east
    private $_latitudeN;    // latitude on mars in degrees north
    
    // refer to https://en.wikipedia.org/wiki/Leap_second
    private static $_arrayLeapSeconds = [
            '1972-01-01 00:00:00' => 10,
            '1972-07-01 00:00:00' => 11,
            '1973-01-01 00:00:00' => 12,
            '1974-01-01 00:00:00' => 13,
            '1975-01-01 00:00:00' => 14,
            '1976-01-01 00:00:00' => 15,
            '1977-01-01 00:00:00' => 16,
            '1978-01-01 00:00:00' => 17,
            '1979-01-01 00:00:00' => 18,
            '1980-01-01 00:00:00' => 19,
            '1981-07-01 00:00:00' => 20,
            '1982-07-01 00:00:00' => 21,
            '1983-07-01 00:00:00' => 22,
            '1985-07-01 00:00:00' => 23,
            '1988-01-01 00:00:00' => 24,
            '1990-01-01 00:00:00' => 25,
            '1991-01-01 00:00:00' => 26,
            '1992-07-01 00:00:00' => 27,
            '1993-07-01 00:00:00' => 28,
            '1994-07-01 00:00:00' => 29,
            '1996-01-01 00:00:00' => 30,
            '1997-07-01 00:00:00' => 31,
            '1999-01-01 00:00:00' => 32,
            '2006-01-01 00:00:00' => 33,
            '2009-01-01 00:00:00' => 34,
            '2012-07-01 00:00:00' => 35,
            '2015-07-01 00:00:00' => 36,
            '2017-01-01 00:00:00' => 37,
    ];    
    
    /**
     * Constructs MarsTime object
     * 
     * @param \DateTime $dt     corresponding earth time, null: actual time
     * @param bool $autoUpdate  keep sync with actual time
     * @param float $longitudeE longitude east for location on mars
     * @param float $latitudeN  latitude north for location on mars
     */
    function __construct(\DateTime $dt = null, bool $autoUpdate = false,
                         float $longitudeE = 0.0, float $latitudeN = 0.0 ) {
        $this->_datetime  = ( $dt ) ? $dt : new \DateTime( 'NOW' );
        $this->_longitudeE = $longitudeE;
        $this->_latitudeN  = $latitudeN;        
        $this->_autoUpdate = $autoUpdate;        
    }
    
    /**
     * Converts longitude east to west
     * 
     * @param float $east longitude east
     * @return float normalized (0.0 - 360.0) longitude west
     */
    public function EastToWest( float $east ) {
        $west = 360.0 - $east;
        
        return $this->normalizePeroid( $west );                
    }
    
    /**
     * Converts longitude west to east
     * 
     * @param float $west longitude west
     * @return float normalized (0.0 - 360.0) longitude east
     */
    public function WestToEast( float $west ) {
        return $this->EastToWest( $west );
    }    
    
    /**
     * Returns alpha perturbs (PBS)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float alpha perturbs (PBS) in degrees
     */
    public function getAlphaPerturbs( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $pbs =  0.0071 * \cos( ((0.985626 * $j2k_ott /  2.2353) +  49.409) *
                M_PI / 180.0 ) +
                0.0057 * \cos( ((0.985626 * $j2k_ott /  2.7543) + 168.173) * 
                M_PI / 180.0 ) +
                0.0039 * \cos( ((0.985626 * $j2k_ott /  1.1177) + 191.837) *
                M_PI / 180.0 ) +
                0.0037 * \cos( ((0.985626 * $j2k_ott / 15.7866) +  21.736) *
                M_PI / 180.0 ) +
                0.0021 * \cos( ((0.985626 * $j2k_ott /  2.1354) +  15.704) *
                M_PI / 180.0 ) +
                0.0020 * \cos( ((0.985626 * $j2k_ott /  2.4694) +  95.528) *
                M_PI / 180.0 ) +
                0.0018 * \cos( ((0.985626 * $j2k_ott / 32.8493) +  49.095) *
                M_PI / 180.0 );        
        
        return $pbs;
    }
    
    /**
     * Returns angle of fictitious mean sun (alphaFMS)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float angle of fictitious mean sun (alphaFMS) in degrees
     */
    public function getAngleOfFictitiousMeanSun( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $alphaFMS = 270.3863 + 0.52403840 * $j2k_ott;
        
        return $this->normalizePeroid( $alphaFMS );
    }
    
    /**
     * Returns areocentric solar longitude (Ls)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float areocentric solar longitude in degrees (Ls)
     */
    public function getAreocentricSolarLongitude( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        
        $aFMS = $this->getAngleOfFictitiousMeanSun( $j2k_ott );
        $eoc  = $this->getEquationOfCenter( $j2k_ott );
        
        $Ls = ( $aFMS + $eoc );
        
        return $this->normalizePeroid( $Ls );
    }
    
    /**
     * Returns coordinated mars time (MTC)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float coordinated mars time (MTC)
     */    
    public function getCoordinatedMarsTime( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $MTC = 24 * $this->getMarsSolDate( $j2k_ott );
        
        return $this->normalizePeroid( $MTC, 24 );
    }
    
    /**
     * Returns the corresponding earth time
     * 
     * @return \DateTime corresponding earth time
     */
    public function getDateTime() {
        if( $this->_autoUpdate ) {
            $this->_datetime = new \DateTime( 'NOW' );
        }

        return $this->_datetime;        
    }

    /**
     * Returns eccentricity (e)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float eccentricity (e)
     */
    public function getEccentricity( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $e = 0.09340 + 2.477e-9 * $j2k_ott;
        
        return $e;        
    }
    
    /**
     * Returns equation of center (v-M)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float equation of center (v-M) in degrees
     */
    public function getEquationOfCenter( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $M = $this->getMarsMeanAnomaly( $j2k_ott ) * \M_PI / 180.0;
        $PBS = $this->getAlphaPerturbs( $j2k_ott );
        $eoc = ( 10.691 + 3.0e-7 * $j2k_ott ) * \sin( $M )
               + 0.6230 * \sin( 2 * $M )
               + 0.0500 * \sin( 3 * $M )
               + 0.0050 * \sin( 4 * $M )
               + 0.0005 * \sin( 5 * $M )
               + $PBS;

        return $eoc;
    }
    
    /**
     * Return equation of time (EOT)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float equation of time (EOT) in degrees
     */
    public function getEquationOfTime( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $Ls = $this->getMarsLs( $j2k_ott ) * \M_PI / 180.0;
        $EoT = 2.861 * \sin( 2 * $Ls )
             - 0.071 * \sin( 4 * $Ls )
             + 0.002 * \sin( 6 * $Ls )
             - $this->getEquationOfCenter( $j2k_ott );

        return $EoT;        
    }
    
    /**
     * Return hourangle
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @return float hourangle 
     */
    public function getHourangle( float $j2kOTT = null,
                                  float $longitudeW = null ) {
        $j2k_ott  = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        
        $subsol = $this->getSubsolarLongitude( $j2k_ott ) * \M_PI / 180.0;
        $hourangle = $longW * \M_PI / 180.0 - $subsol;
        
        return $hourangle;
    }
    
    /**
     * Returns julian date tt at J2000 epoch
     * 
     * @return float julian date tt at J2000 epoch
     */
    public function getJulian2kEpoch() {
        return 2451545.0;
    }
    
    /**
     * Returns offset from J2000 epoch in terretrial time (j2kOTT)
     * 
     * @param \DateTime $d earth time
     * @return float offset from J2000 epoch in terretrial time (j2kOTT)
     */
    public function getJulian2kOTT( \DateTime $d = null ) {        
        $dt = ( $d ) ? $d : $this->getDateTime(); 
        return $this->getJulianTT( $dt ) - $this->getJulian2kEpoch();  
    }
    
    /**
     * Return julian date in terrestrial time (JDtt)
     * 
     * @param \DateTime $d earth time
     * @return float julian date in terrestrial time (JDtt)
     */
    public function getJulianTT( \DateTime $d = null ) {
        $dt = ( $d ) ? $d : $this->getDateTime(); 
        $jdUTC = $this->getJulianUTC( $dt ); 
        $jdTT  = $jdUTC + ( $this->getLeapSeconds( $dt ) + 32.184 ) / 86400;                        
        return $jdTT;
    }
    
    /**
     * Returns julian date of UTC (JDut)
     * 
     * @param \DateTime $d earth time
     * @return float julian date of UTC (JDut)
     */
    public function getJulianUTC( \DateTime $d = null ) {
        $dt = ( $d ) ? $d : $this->getDateTime(); 
        return $dt->getTimestamp() / 86400 + 2440587.5;
    }
    
    /**
     * Returns leap seconds
     * 
     * @param \DateTime $d earth time
     * @return integer leap seconds at this time
     */
    public function getLeapSeconds( \DateTime $d = null ) {
        $dt = ( $d ) ? $d : $this->getDateTime();
        $leap_seconds = 0;
        
        foreach( self::$_arrayLeapSeconds as $date => $leap ) {
            $dtL = new \DateTime( $date );
            if( $dt >= $dtL ) {
                $leap_seconds = $leap;
            }
        }
        
        return $leap_seconds;
    }
    
    /**
     * Returns local mean solar time (LMST)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @return float local mean solar time (LMST)
     */
    public function getLocalMeanSolarTime( float $j2kOTT = null,
                                           float $longitudeW = null ) {
        $j2k_ott  = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        
        $MTC = $this->getCoordinatedMarsTime( $j2k_ott );
        $LMST = $MTC - $longW * 24 / 360.0;
        
        return $this->normalizePeroid( $LMST, 24 );
    }
    
    /**
     * Returns local true solar time (LTST)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @return float local true solar time (LTST)
     */
    public function getLocalTrueSolarTime( float $j2kOTT = null,
                                           float $longitudeW = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ? 
                   $longitudeW : $this->getLongitudeWest();
        
        $EoT = $this->getEquationOfTime( $j2k_ott );
        $LMST = $this->getLocalMeanSolarTime( $j2k_ott, $longW );
        $LTST = $LMST + $EoT * 24 / 360.0;
        
        return $this->normalizePeroid( $LTST, 24 );
    }
    
    /**
     * Returns latitude north
     * 
     * @return float latitude north
     */
    public function getLatitudeNorth() {
        return $this->_latitudeN;
    }
    
    /**
     * Returns longitude east
     * 
     * @return float longitude east
     */
    public function getLongitudeEast() {
        return $this->_longitudeE;
    }
    
    /**
     * Returns longitude west
     * 
     * @return float longitude west
     */
    public function getLongitudeWest() {
        return $this->EastToWest( $this->_longitudeE );
    }
    
    /**
     * Returns areocentric solar longitude (Ls)
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return float areocentric solar longitude in degrees (Ls)
     */
    public function getMarsLs( float $j2kOTT = null ) {
        return $this->getAreocentricSolarLongitude( $j2kOTT );
    }
    
    /**
     * Returns mars mean anomaly in degrees (M)
     * 
     * @param type $j2kOTT julian date offset tt or null for actual datetime
     * @return float mars mean anomaly in degrees (M)
     */
    public function getMarsMeanAnomaly( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $M = 19.3870 + 0.52402075 * $j2k_ott;
        
        return $this->normalizePeroid( $M );
    }
    
    /**
     * Returns mars sol date (MSD)
     * 
     * @param type $j2kOTT julian date offset tt or null for actual datetime
     * @return float mars sol date (MSD)
     */
    public function getMarsSolDate( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        
        return ( ( ( $j2k_ott - 4.5 ) / 1.027491252 ) +
                     44796.0 - 0.00096 );
    }
    
    /**
     * Returns mars true anomaly (M+e)
     * 
     * @param type $j2kOTT julian date offset tt or null for actual datetime
     * @return float mars true anomaly (M+e)
     */
    public function getMarsTrueAnomaly( float $j2kOTT = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        
        return $this->getMarsMeanAnomaly( $j2k_ott ) + 
               $this->getEquationOfCenter( $j2k_ott );
    }
    
    /**
     * Returns previous and upcoming midnights in LTST
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @return array midnights of the given day [previous, next]
     */
    public function getMidnights( float $j2kOTT = null,
                                  float $longitudeW = null ) {
        $j2k_ott  = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        $LTST = $this->getLocalTrueSolarTime( $j2k_ott, $longW );
        $mid1 = $j2k_ott - ( $LTST / 24.0 );
        $mid2 = $j2k_ott + ( ( 24 - $LTST ) / 24.0 );
        return [ $mid1, $mid2 ];
    }
    
    /**
     * Returns season at airy-0
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @return string season at airy-0 [spring, summer, autumn, winter]
     */
    public function getSeason( float $j2kOTT = null ) {
        $Ls = $this->getMarsLs( $j2kOTT );
        
        // from http://www-mars.lmd.jussieu.fr/mars/time/solar_longitude.html
        $season = 'spring';
        if( $Ls > 270 ) {
            $season = 'winter';
        } else if( $Ls > 180 ) {
            $season = 'autumn';
        }  else if( $Ls > 90 ) {
            $season = 'summer';
        }
        
        return $season;
    }
    
    /**
     * Return solar declination
     * 
     * @param float $ls areocentric solar longitude in degrees (Ls)
     * @return float solar declination
     */
    public function getSolarDeclination( float $ls = null ) {
        $Ls  = ( $ls !== null ) ? $ls : $this->getMarsLs();
        $Ls *= \M_PI / 180.0;

        $dec = \asin( 0.42565 * sin( $Ls ) )
               + 0.25 * ( \M_PI / 180 ) * sin( $Ls );
        $dec *= 180.0 / \M_PI;
        
        return $dec;
    }
    
    /**
     * Returns solar elevation
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @param float $latitudeN latitude on mars in degrees north
     * @return float solar elevation
     */
    public function getSolarElevation( float $j2kOTT = null,
                                       float $longitudeW = null,
                                       float $latitudeN = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        $latN = ( $latitudeN !== null ) ?
                  $latitudeN : $this->getLatitudeNorth();
        
        $Z = $this->getSolarZenith( $j2k_ott, $longW, $latN );
        
        return 90 - $Z;
    }
    
    /**
     * Returns solar zenith
     * 
     * @param float $j2kOTT julian date offset tt or null for actual datetime
     * @param float $longitudeW longitude on mars in degrees west
     * @param float $latitudeN latitude on mars in degrees north
     * @return float solar zenith
     */
    public function getSolarZenith( float $j2kOTT = null,
                                    float $longitudeW = null,
                                    float $latitudeN = null ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        $latN = ( $latitudeN !== null ) ?
                  $latitudeN : $this->getLatitudeNorth();
        
        $ha = $this->getHourangle( $j2k_ott, $longW );
        $Ls = $this->getMarsLs( $j2k_ott );
        $dec = $this->getSolarDeclination( $Ls ) * \M_PI / 180.0;

        $cosZ = \sin( $dec ) * \sin( $latN * \M_PI / 180.0 ) + 
                \cos( $dec ) * \cos( $latN * \M_PI / 180.0 )
                * \cos( $ha );
        $Z = \acos( $cosZ ) * 180.0 / \M_PI;
        
        return $Z;    
    }
    
    /**
     * Returns subsolar longitude
     * 
     * @param float $j2kOTT
     * @return float subsolar longitude
     */
    public function getSubsolarLongitude( float $j2kOTT = null ) {
        $j2k_ott  = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $EoT = $this->getEquationOfTime( $j2k_ott ) * 24 / 360.0;
        $MTC = $this->getCoordinatedMarsTime( $j2k_ott );
        $subsol = ( $MTC + $EoT ) * ( 360.0 / 24.0 ) + 180.0;
        return $this->normalizePeroid( $subsol );
    }
    
    /**
     * Returns sunrise and sunset on a specific day and location
     * 
     * @param float $j2kOTT
     * @param float $longitudeW
     * @param float $latitudeN
     * @param float $solar_angular_radius
     * @return array [sunrise, sunset]
     */
    public function getSunriseSunset( float $j2kOTT = null,
                                      float $longitudeW = null,
                                      float $latitudeN = null,
                                      float $solar_angular_radius = 0.0 ) {
        $j2k_ott = ( $j2kOTT !== null ) ? $j2kOTT : $this->getJulian2kOTT();
        $longW = ( $longitudeW !== null ) ?
                   $longitudeW : $this->getLongitudeWest();
        $latN = ( $latitudeN !== null ) ?
                  $latitudeN : $this->getLatitudeNorth();

        list( $mid1, $mid2 ) = $this->getMidnights( $j2k_ott, $longW );
        $noon = 0.5 * ( $mid1 + $mid2 );
        
        // "bisect"        
        $iterSR = ( $noon - $mid1 ) / self::$_maxIterations;
        $sunrise = $mid1;
        $z1 = $this->getSolarElevation( $sunrise, $longW, $latN ) + 
              $solar_angular_radius;
        
        while( ( $z1 < 0 ) && ( $sunrise < $noon ) ) {
            $sunrise += $iterSR;
            $z1 = $this->getSolarElevation( $sunrise, $longW, $latN ) +
                  $solar_angular_radius;
        }
        
        $iterSS = ( $mid2 - $noon ) / self::$_maxIterations;
        $sunset = $noon;
        $z2 = $this->getSolarElevation( $sunset, $longW, $latN );
        while( ( $z2 > 0 ) && ( $sunset <= $mid2 ) ) {
            $sunset += $iterSS;
            $z2 = $this->getSolarElevation( $sunset, $longW, $latN );
        }
 
        return [ $sunrise, $sunset ];         
    }
    
    /**
     * Helper for MODULO for getting normalized floating points results
     * 
     * @param float $value
     * @param float $base
     * @return float normalized MODULO with floating points
     */
    public function normalizePeroid( float $value, float $base = 360.0 ) {
        $mod = ( $value % $base );
        $valuem = $mod < 0 ? $mod + $base : $mod;
        return $valuem + fmod( $value, 1 );        
    }
}