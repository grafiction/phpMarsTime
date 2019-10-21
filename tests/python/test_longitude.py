import marstime
import datetime
import scipy
import scipy.optimize
import argparse

def simple_julian_offset(indate):
    """Simple conversion from date to J2000 offset"""
    datetime_epoch = datetime.datetime(2000,1,1,12,0,0)
    date = indate-datetime_epoch
    jdate = date.days + date.seconds/86400.
    return jdate

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
    jdate = simple_julian_offset(datetime.datetime.strptime(dt, "%Y/%m/%d %H:%M:%S"))
    print marstime.east_to_west(args.longitude)

