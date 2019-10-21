import marstime
import datetime
import scipy
import scipy.optimize as so
import argparse

import test_simple_julian_offset

def midnight(date, longitude, latitude):
    """Given a Mars Solar Date(MSD) and location, find the local midnight times.
    The input data is used to calculate the local True solar time (LTST)
    and the local midnight occurs LTST hours before and 24-LTST after"""
    lt = marstime.Local_True_Solar_Time(longitude, date)
    mid1 = date - lt/24.
    mid2 = date + (24-lt)/24.
    return (mid1, mid2)

def solelev(date, x,y, solar_angular_radius=0.0):
    """a wrapper for scipy.optimize to reverse the arguments for solar_elevation"""
    return marstime.solar_elevation(x,y,date)+solar_angular_radius

def sunrise_sunset(date, longitude, latitude, solar_angular_radius=0.0):
    """Interface to the scipy.optimize.
    Using the date (j2000 offset) and location, start by finding the local 
    midnights. the local mid-day is then (roughly) at the center of the two 
    midnights. Sunrise must occur between midnight and midday, sunset between 
    midday and midnight (except for polar night).
    
    This method uses Ian's method, which is less annoying than my method that required
    a conditional depending on whether 'date' was in the daytime or nighttime."""
    
    mid1,mid2=midnight(date, longitude, latitude)
    noon = 0.5*(mid1+mid2)
    sunrise = so.bisect(solelev, mid1, noon, args=(longitude, latitude, solar_angular_radius))
    sunset = so.bisect(solelev, noon, mid2, args=(longitude, latitude, solar_angular_radius))
    return sunrise, sunset

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Testsuite for marstime library")
    parser.add_argument("date",type=str, nargs="?",
            help="Earth date in ISO format YYYY/MM/DD")
    parser.add_argument("time",type=str, nargs="?",
            help="Earth time in ISO format HH:ii:ss")
    parser.add_argument("longitude",type=float,
            help="East Longitude")
    parser.add_argument("latitude",type=float,
            help="East Longitude")
    args = parser.parse_args()
    
    dt = args.date + " " + args.time
    jdate = test_simple_julian_offset.simple_julian_offset(datetime.datetime.strptime(dt, "%Y/%m/%d %H:%M:%S"))
    west_longitude = marstime.east_to_west(args.longitude)
    north_latitude = args.latitude
    #find the midnight times
    mdate = midnight(jdate, west_longitude, north_latitude)

    #calculate the angular radius of the Sun to offset the bissect calculation
    solar_angular_radius = 0.0
    
    sup, sdown = sunrise_sunset(jdate, west_longitude, north_latitude, solar_angular_radius = solar_angular_radius)
    print str(sup) + ',' + str(sdown)


