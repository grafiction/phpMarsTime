<?php

/*
 * MarsTimeBundleTest
 *
 * Tests for Graficiton\MarsTimeBundle
 * 
 * @copyright  2019 Michael Scheffler
 * @license    https://de.wikipedia.org/wiki/Beerware Beerware License Rev.42
 * @version    Release: 1.0
 * @link       http://www.grafiction.de
 */

namespace Grafiction\MarsTimeBundle\Tests;

use Grafiction\MarsTimeBundle\MarsTimeBundle;
use PHPUnit\Framework\TestCase;

class MarsTimeBundleTest extends TestCase
{
    public function testAlphaPerturbs() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );
                
        $this->assertEqualsWithDelta( 0.001668, $mt->getAlphaPerturbs(0.0), 2e-5 );
        $this->assertEqualsWithDelta( -0.007903, $mt->getAlphaPerturbs(1000.0), 2e-5 );
                
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {  
            $output = exec( 'python '.__DIR__.
                            '/python/test_alpha_perturbs.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getAlphaPerturbs(), 5 ) );
        }
    }
    
    public function testAngleOfFictitiousMeanSun() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );

        $this->assertEqualsWithDelta( 270.3863, $mt->getAngleOfFictitiousMeanSun(0.0), 1e-4 );
        $this->assertEqualsWithDelta( 74.4247, $mt->getAngleOfFictitiousMeanSun(1000.0), 1e-4 );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_fms_alpha.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getAngleOfFictitiousMeanSun(), 5 ) );
        }
    }   
    
    public function testCoordinatedMarsTime() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );

        $this->assertEqualsWithDelta( 14.8665, $mt->getCoordinatedMarsTime(0.0), 2e-4 );
        //1 mars hour later
        $this->assertEqualsWithDelta( 15.8665, $mt->getCoordinatedMarsTime(3698.9685/86400), 2e-4 );
        //1 mars day later
        $this->assertEqualsWithDelta( 14.8665, $mt->getCoordinatedMarsTime(88775.244/86400), 2e-4 );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_coordinated_mars_time.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getCoordinatedMarsTime(), 5 ) );
        }
    }    
    
    public function testEastToWest() {
        $mt = new MarsTimeBundle();
        $this->assertEquals( $mt->WestToEast( 0.0 ), $mt->EastToWest( 0.0 ) );
        $this->assertEquals( $mt->WestToEast( 360.0 ), $mt->EastToWest( 0.0 ) );
        $this->assertEquals( $mt->WestToEast( 365.0 ), $mt->WestToEast( 5.0 ) );
    }
    
    public function testEquationOfCenter() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );
        
        $this->assertEqualsWithDelta( 3.98852, $mt->getEquationOfCenter(0.0), 2e-5 );
        $this->assertEqualsWithDelta( -0.57731, $mt->getEquationOfCenter(1000.0), 2e-5 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_equation_of_center.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getEquationOfCenter(), 5 ) );
        }
    } 
    
    public function testEquationOfTime() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );
        
        $this->assertEqualsWithDelta( -4.44596, $mt->getEquationOfTime(0.0), 1e-5 );
        $this->assertEqualsWithDelta( 2.17244, $mt->getEquationOfTime(1000.0), 1e-5 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_equation_of_time.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getEquationOfTime(), 5 ) );
        }
    }  

    public function testHourangle() {
        $dt = new \DateTime( 'NOW' );
        $longE = 137.44;
        $mt = new MarsTimeBundle( $dt, false, $longE );
        
        $this->assertEqualsWithDelta( -0.67287, $mt->getHourangle(0.0,0.0), 1e-4 );
        $this->assertEqualsWithDelta( 15, ($mt->getHourangle(0.0,15.0)-$mt->getHourangle(0.0,0.0))*180 / \M_PI, 1e-3 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_hourangle.py '.
                            $dt->format( "Y/m/d H:i:s" ).' '.$longE );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getHourangle(null,$mt->EastToWest($longE)), 5 ) );
        }
    } 
    
    public function testJulian() {
        $dt1 = new \DateTime( '2019-08-29 13:24:12' );
        $mt1 = new MarsTimeBundle( $dt1, false );
        $this->assertEquals( 7180.05927, \round( $mt1->getJulian2kOTT(),5 ) );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $dt2 = new \DateTime( 'NOW' );
            $mt2 = new MarsTimeBundle( $dt2, false );
            $output = exec( 'python '.__DIR__.
                            '/python/test_simple_julian_offset.py '.
                            $dt2->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt2->getJulian2kOTT(), 5 ) );
        }
    }    
    
    public function testLeapSeconds() {
        $dt1 = new \DateTime( '2019-08-29 13:24:12' );
        $mt = new MarsTimeBundle( $dt1, false );
        $output1 = $mt->getLeapSeconds( $dt1 );
        $this->assertEquals( $output1, 37 );
        
        $dt2 = new \DateTime( '1984-08-29 13:24:12' );
        $output2 = $mt->getLeapSeconds( $dt2 );
        $this->assertEquals( $output2, 22 );
        
        $dt3 = new \DateTime( '1970-08-29 13:24:12' );
        $output3 = $mt->getLeapSeconds( $dt3 );
        $this->assertEquals( $output3, 0 );
    }
    
    public function testLocalMeanSolarTime() {
        $dt = new \DateTime( 'NOW' );
        $longE = 137.44;
        $mt = new MarsTimeBundle( $dt, false, $longE );
        
        $this->assertEqualsWithDelta( 1.0, $mt->getLocalMeanSolarTime(0,0)-$mt->getLocalMeanSolarTime(0,15), 1e-2 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_local_mean_solar_time.py '.
                            $dt->format( "Y/m/d H:i:s" ).' '.$longE );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getLocalMeanSolarTime(null,$mt->EastToWest($longE)), 5 ) );
        }
    }  

    public function testLocalTrueSolarTime() {
        $dt = new \DateTime( 'NOW' );
        $longE = 137.44;
        $mt = new MarsTimeBundle( $dt, false, $longE );
        $this->assertEqualsWithDelta( 1.0, $mt->getLocalTrueSolarTime(0,0)-$mt->getLocalTrueSolarTime(0,15), 1e-2 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_local_true_solar_time.py '.
                            $dt->format( "Y/m/d H:i:s" ).' '.$longE );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getLocalTrueSolarTime(null,$mt->EastToWest($longE)), 5 ) );
        }
    } 

    public function testMarsLs() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );

        $this->assertEqualsWithDelta( 273, $mt->getMarsLs(4120), 0.5 );
        $this->assertEqualsWithDelta( 274.37, $mt->getMarsLs(0), 1e-2 );
        $this->assertEqualsWithDelta( 73.846, $mt->getMarsLs(1000), 1e-2 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_mars_ls.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getMarsLs(), 5 ) );
        }
    }   
    
    public function testMarsMeanAnomaly() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );
        
        $this->assertEqualsWithDelta( 19.387, $mt->getMarsMeanAnomaly(0), 1e-4 );
        $this->assertEqualsWithDelta( 183.4077, $mt->getMarsMeanAnomaly(1000), 1e-4 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_mars_mean_anomaly.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getMarsMeanAnomaly(), 5 ) );
        }
    }
    
    public function testMarsSolDate() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false );
        
        $this->assertEqualsWithDelta( 44791.61944, $mt->getMarsSolDate(0), 1e-4 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_mars_solar_date.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 4 ),
                                 \round( $mt->getMarsSolDate(), 4 ) );
        }
    }    
        
    public function testMidnights() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false, 137.44 );
        list( $mid1, $mid2 ) = $mt->getMidnights();
        $this->assertGreaterThan( $mid1, $mid2 );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_midnight.py '.
                            $dt->format( "Y/m/d H:i:s" ).' 137.44' );
            list( $omid1, $omid2 ) = \explode( ',', $output);
            
            $this->assertEquals( \round( $omid1, 5 ),
                                 \round( $mid1, 5 ) );
            $this->assertEquals( \round( $omid2, 5 ),
                                 \round( $mid2, 5 ) );
        }
    } 
        
    public function testSolarDeclination() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false, 137.44 );
        
        $this->assertEqualsWithDelta( 0, $mt->getSolarDeclination(0), 1e-3 );
        $this->assertEqualsWithDelta( 25.441, $mt->getSolarDeclination(90), 1e-3 );
        $this->assertEqualsWithDelta( 0, $mt->getSolarDeclination(180), 1e-3 );
        $this->assertEqualsWithDelta( -25.441, $mt->getSolarDeclination(270), 1e-3 );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_solar_declination.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getSolarDeclination(), 5 ) );
        }
    }  

    public function testSolarZenith() {
        $dt = new \DateTime( 'NOW' );
        $longE = 137.44;
        $latN = 5.7;
        $mt = new MarsTimeBundle( $dt, false, $longE, $latN );

        $j2day = 151.2737; // Ls=0
        $x = $mt->getSubsolarLongitude( $j2day );
        $this->assertEqualsWithDelta( 114.113, $x, 1e-3 );
        $this->assertEqualsWithDelta( 0, $mt->getSolarZenith($j2day,$x,0), 1e-4 );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_solar_zenith.py '.
                            $dt->format( "Y/m/d H:i:s" ).' '.$longE.' '.$latN );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getSolarZenith(null,$mt->EastToWest($longE),$latN), 5 ) );
        }
    } 
    
    public function testSubsolarLongitude() {
        $dt = new \DateTime( 'NOW' );
        $mt = new MarsTimeBundle( $dt, false, 137.44 );
        
        $this->assertEqualsWithDelta( -14.99, $mt->getSubsolarLongitude(0)-$mt->getSubsolarLongitude(3698.9685/86400), 1e-2 );
        
        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_subsolar_longitude.py '.
                            $dt->format( "Y/m/d H:i:s" ) );
            $this->assertEquals( \round( $output, 5 ),
                                 \round( $mt->getSubsolarLongitude(), 5 ) );
        }
    }  

    public function testSunriseSunset() {
        $dt = new \DateTime( 'NOW' );
        $longE = 137.44;
        $latN = 0;
        $mt = new MarsTimeBundle( $dt, false, $longE, $latN );
        list( $SR, $SS ) = $mt->getSunriseSunset();        
        $this->assertGreaterThan( $SR, $SS );

        // test against python library
        if( getenv( 'PYTHON' ) == true ) {            
            $output = exec( 'python '.__DIR__.
                            '/python/test_sunrise_sunset.py '.
                            $dt->format( "Y/m/d H:i:s" ).' '.$longE.' '.$latN );
            list( $oSR, $oSS ) = \explode( ',', $output);
            
            $this->assertEquals( \round( $oSR, 3 ),
                                 \round( $SR, 3 ) );
            $this->assertEquals( \round( $oSS, 3 ),
                                 \round( $SS, 3 ) );
        }
    } 
    
    public function testWestToEast() {
        $mt = new MarsTimeBundle();
        $this->assertEquals( 0.0, $mt->WestToEast( 360.0 ) );
        $this->assertEquals( $mt->WestToEast( 540.0 ), $mt->WestToEast( 180.0 ) );
    }
}
