home = '/Users/pg/Sites/energy-use.org/public_html/gc/Phil/'

datafile= (f'{home}activities.json')
# Where to look for .fit files to import
downloadFolder = '/Users/pg/Downloads/'
garminFolder = '/Volumes/GARMIN/GARMIN/ACTIVITY/'

# store .json files
actFolder = (f'{home}activities/')

# store sparklines
sparkFolder = (f'{home}sparklines/')

# Where to archive .fit files
fitArchive = (f'{home}imports/')

# Sports to display in m:ss /500m
paceSports = ['Row','Erg']

locations = {
    'Isis':          {'LAT': 51.736699,'LON': -1.242147} ,
    'Home':          {'LAT': 51.759712,'LON': -1.205753} ,
    'Ring road':     {'LAT': 51.758590,'LON': -1.194824} ,
    'Marston Ferry': {'LAT': 51.775092,'LON': -1.256099} ,
    'Chedworth':     {'LAT': 51.797152,'LON': -1.905943} ,
    'Isle of Wight': {'LAT': 50.653023,'LON': -1.449352} ,
    'StMary':        {'LAT': 49.914564,'LON': -6.317350} ,
    'Henley':        {'LAT': 51.537625,'LON': -0.901522},
    'Dorney':        {'LAT': 51.489040,'LON': -0.651621},
    'Eider':         {'LAT': 54.305009,'LON':  9.654318},
    }
