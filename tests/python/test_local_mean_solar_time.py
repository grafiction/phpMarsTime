import marstime
import datetime
import scipy
import scipy.optimize
import argparse

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
    print marstime.Local_Mean_Solar_Time(west_longitude, jdate)

