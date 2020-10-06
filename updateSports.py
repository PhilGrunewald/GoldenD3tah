import glob
import json

sports = {
    'cycling'              : 'Bike',
    'rowing'               : 'Row',
    'Rowing'               : 'Row',
    'running'              : 'Run',
    'fitness_equipment'    : 'Erg',
    'Cross country skiing' : 'Rollerski',
    'skate_skiing'         : 'Rollerski',
    'cross_country_skiing' : 'Langlauf'
    }
acts = glob.glob(f'activities/*.json')
for act in acts:
    with open(act, encoding='utf-8-sig') as f:
        data = json.load(f)
        sport = data['RIDE']['TAGS']['Sport'].strip()
        if sport in sports:
            data['RIDE']['TAGS']['Sport'] = sports[sport]
            with open(act, 'w') as f:
                json.dump(data, f, indent=4, default=str)
            print("File ",act)
            print(sport, ">", sports[sport])
