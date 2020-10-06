import fitparse
import datetime as dt
import pandas as pd

def getFit(filename):
    sports = {
        'cycling'              : 'Bike',
        'rowing'               : 'Row',
        'running'              : 'Run',
        'fitness_equipment'    : 'Erg',
        'cross_country_skiing' : 'Langlauf',
        'skate_skiing'         : 'Rollerski'
        }
    subsports = {
        'skate_skiing'         : 'Rollerski'
        }
    sampleFields = {
        'enhanced_speed'    : 'KPH',
        'power'             : 'WATTS',
        'Power2'            : 'WATTS',
        'enhanced_altitude' : 'ALT',
        'cadence'           : 'CAD',
        'Cadence2'          : 'CAD',
        'distance'          : 'KM',
        'heart_rate'        : 'HR',
        'position_lat'      : 'LAT',
        'position_long'     : 'LON',
        'timestamp'         : 'SECS'
        }

    data = {}
    ride = {}
    ride['TAGS'] = {}
    ride['TAGS']['Source Filename'] = filename
    samples = []

    fitfile = fitparse.FitFile( filename, data_processor=fitparse.StandardUnitsDataProcessor())
    messages = fitfile.get_messages()

    # from pprint import pprint
    # pprint(obj)
    t0 = False
    for obj in messages:
        # other obj.names: activity, lap, device_info
        if (obj.name == 'file_id'):
            ride['DEVICETYPE'] = obj.get_value('manufacturer')
            ride['TAGS']['Device'] = obj.get_value('manufacturer')
        if (obj.name == 'session'):
            ride['TAGS']['Date'] = obj.get_value('timestamp')
            if obj.get_value('sport') in sports:
                ride['TAGS']['Sport'] = sports[obj.get_value('sport')]
            else:
                ride['TAGS']['Sport'] = obj.get_value('sport')
            if obj.get_value('sub_sport') in subsports:
                ride['TAGS']['SubSport'] = subsports[obj.get_value('sub_sport')]
            else:
                ride['TAGS']['SubSport'] = obj.get_value('sub_sport')

        if obj.name == 'record':
            sample = {}
            for d in obj:
                if d.name in sampleFields:
                    if d.name == 'timestamp':
                        if t0:
                            d.value = (d.value-t0).seconds
                        else:
                            t0 = d.value
                            d.value = False
                    if d.value:
                        sample[sampleFields[d.name]] = d.value
            for field in sampleFields:      # fill all missing with 0
                if sampleFields[field] not in sample:
                    sample[sampleFields[field]] = 0
            if sample['WATTS'] > 1800:
                sample['WATTS'] = 0
            samples.append(sample)

    watts = False
    alt = False
    cad = False
    cad = False
    km = False
    hr = False
    lat = False
    lon = False

    for sample in samples:
        if sample['ALT'] > 0:
            alt = True
        if sample['CAD'] > 0:
            cad = True
        if sample['KM'] > 0:
            km = True
        if sample['HR'] > 0:
            hr = True
        if sample['LAT'] != 0:
            lat = True
        if sample['LON'] != 0:
            lon = True
        if sample['WATTS'] > 0:
            watts = True

    for sample in samples:
        if not alt:
            sample.pop('ALT')
        if not cad:
            sample.pop('CAD')
        if not km:
            sample.pop('KM')
        if not hr:
            sample.pop('HR')
        if not lat:
            sample.pop('LAT')
        if not lon:
            sample.pop('LON')
        if not watts:
            sample.pop('WATTS')


    # resample to 1s
    df = pd.DataFrame(samples)
    df['dt'] = [dt.datetime.fromtimestamp(s) for s in df.SECS.values]
    # df = df[~df.index.duplicated(keep='first')] # drop duplicated seconds
    df = df.set_index('dt')
    df = df.resample('1s').bfill()
    df = df.reset_index()
    df['SECS'] = list(range(len(df.index)))
    samples = df.to_dict('records')
    # print(samples)

    ride['STARTTIME'] = t0.strftime('%Y/%m/%d %H:%M:%S UTC ')
    ride['RECINTSECS'] = 1
    ride['IDENTIFIER'] = ""
    ride['SAMPLES'] = samples
    data['RIDE'] = ride
    return data

