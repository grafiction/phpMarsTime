import marstime
import datetime
import scipy
import scipy.optimize
import argparse

def midnight(date, longitude, latitude):
    """Given a Mars Solar Date(MSD) and location, find the local midnight times.
    The input data is used to calculate the local True solar time (LTST)
    and the local midnight occurs LTST hours before and 24-LTST after"""
    lt = marstime.Local_True_Solar_Time(longitude, date)
    mid1 = date - lt/24.
    mid2 = date + (24-lt)/24.
    return (mid1, mid2)

import test_simple_julian_offset

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Testsuite for marstime library")
    parser.add_argument("date",type=str, nargs="?",
            help="Earth date in ISO format YYYY/MM/DD")
    parser.add_argument("time",type=str, nargs="?",
            help="Earth time in ISO format HH:ii:ss")
    parser.add_argument("longitude",type=float,
            help="East Longitude")
    args = parser.parse_args()
    
    dt = args.date + " " + args.time
    jdate = test_simple_julian_offset.simple_julian_offset(datetime.datetime.strptime(dt, "%Y/%m/%d %H:%M:%S"))
    west_longitude = marstime.east_to_west(args.longitude)
    mid1,mid2=midnight(jdate, west_longitude, 0)
    print str(mid1) + ',' + str(mid2)

