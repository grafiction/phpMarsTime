import marstime
import datetime
import scipy
import scipy.optimize
import argparse

def leap_seconds( indate ):
    array_Day = [
            '1972-01-01 00:00:00',
            '1972-07-01 00:00:00',
            '1973-01-01 00:00:00',
            '1974-01-01 00:00:00',
            '1975-01-01 00:00:00',
            '1976-01-01 00:00:00',
            '1977-01-01 00:00:00',
            '1978-01-01 00:00:00',
            '1979-01-01 00:00:00',
            '1980-01-01 00:00:00',
            '1981-07-01 00:00:00',
            '1982-07-01 00:00:00',
            '1983-07-01 00:00:00',
            '1985-07-01 00:00:00',
            '1988-01-01 00:00:00',
            '1990-01-01 00:00:00',
            '1991-01-01 00:00:00',
            '1992-07-01 00:00:00',
            '1993-07-01 00:00:00',
            '1994-07-01 00:00:00',
            '1996-01-01 00:00:00',
            '1997-07-01 00:00:00',
            '1999-01-01 00:00:00',
            '2006-01-01 00:00:00',
            '2009-01-01 00:00:00',
            '2012-07-01 00:00:00',
            '2015-07-01 00:00:00',
            '2017-01-01 00:00:00'
    ];   
    array_Leap = [
            10,
            11,
            12,
            13,
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            21,
            22,
            23,
            24,
            25,
            26,
            27,
            28,
            29,
            30,
            31,
            32,
            33,
            34,
            35,
            36,
            37
    ]; 

    leaps = 0
    for( day, leap ) in zip( array_Day, array_Leap ):
        if( indate > datetime.datetime.strptime(day, "%Y-%m-%d %H:%M:%S" ) ):
            leaps = leap
    return leaps

def simple_julian_offset( indate ):
    """Simple conversion from date to J2000 offset"""
    timestamp = (indate - datetime.datetime(1970,1,1)).total_seconds()
    jdUTC = timestamp / 86400 + 2440587.5
    jdTT  = jdUTC + ( leap_seconds( indate ) + 32.184 ) / 86400

    return jdTT - 2451545

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Testsuite for marstime library")
    parser.add_argument("date",type=str, nargs="?",
            help="Earth date in ISO format YYYY/MM/DD")
    parser.add_argument("time",type=str, nargs="?",
            help="Earth time in ISO format HH:ii:ss")
    args = parser.parse_args()
    
    dt = args.date + " " + args.time
    print simple_julian_offset(datetime.datetime.strptime(dt, "%Y/%m/%d %H:%M:%S"))
