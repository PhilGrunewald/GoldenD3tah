#!/usr/bin/env python3
""" 
extract stats from GoldenChetah json files
output: bar graph and text file

BUG in graph.php
- opening 2nd graph makes speed disappear (if 2nd graph has power)

"""

import os
import math
import numpy as np
import pandas as pd
from pandas.plotting import register_matplotlib_converters 
import glob
import time
import json
import datetime as dt
from datetime import datetime, timedelta
import matplotlib.pyplot as plt
register_matplotlib_converters()
# import shelve
from yattag import Doc,indent

import shutil
import fit2json as fj

from fit_ini import *


def createSparkLine(samples,key,intervals,sport):
    """ produce a simple png of data list """
    width = max(int(float(len(samples.SECS.values))/600),1)
    fig, ax = plt.subplots(1,1,figsize=(width,1))
    plt.plot(samples['SECS'],samples['KPH'], color='lightgray',linewidth=0.5)
    minKPH = samples.KPH.mean()
    for interval in intervals:
        ss = samples[(samples.SECS > interval['START']) & (samples.SECS < interval['STOP'])]
        if 'HR' in ss:
            # fill colour by HR zone
            HRzones = [0, 90,120, 140, 150]
            HRcolors = ['lightgrey', 'lightblue', 'mediumseagreen', 'lightsalmon', 'salmon']
            for HR, colour in zip(HRzones, HRcolors):
                speeds = []
                for hrv,kph in zip(ss.HR,ss.KPH):
                    if hrv > HR:
                        speeds.append(kph)
                    else:
                        speeds.append(0)
                plt.fill_between(ss['SECS'],speeds, color=colour)
        v = ss.KPH.mean()
        if sport in paceSports:
            v = (f'{1800/v//60:.0f}:{1800/v%60:04.1f}')
        else:
            v = (f'{v:.1f}')
        minKPH = min(minKPH,ss.KPH.min())
        try:
            plt.text(ss.SECS.iloc[0],ss.KPH.max(),v, 
                rotation=90,
                fontsize=10)
        except:
            print(ss)
    # remove all the axes
    for k,v in ax.spines.items():
        v.set_visible(False)
    ax.set_xticks([])
    ax.set_yticks([])
    plt.ylim([minKPH,samples.KPH.max()])
    plt.savefig(f"{sparkFolder}{key}.png", transparent = True, bbox_inches = 'tight', pad_inches = 0)
    plt.close()

def mean(l):
    return sum(l)/len(l)

def runningMean(l,n,step):
    """ returns mean of groups on n values """
    n = int(n)
    return [mean(l[i:i+n]) for i in range(0,len(l)-n,step)]

def topIndex(l):
    if l:
        return l.index(max(l))
    else:
        return False

def getTopMetrics(df,name,start,stop):
    # df = df[(df.SECS >= start) & (df.SECS <= stop)]
    df = df[(df.index >= start) & (df.index < stop)]
    metrics = {"NAME": name,  "START": start,  "STOP": stop }
    metrics['duration'] = time.strftime('%H:%M:%S', time.gmtime(stop-start))
    if 'KPH' in df.keys():
        metrics['speed'] = round(df.KPH.mean(),2)
    if 'HR' in df.keys():
        if not np.isnan(df.HR.mean()):
            metrics['hr'] = int(df.HR.mean())
    if 'CAD' in df.keys():
        if not np.isnan(df.CAD.mean()):
            metrics['cad'] = int(df.CAD.mean())
    if 'RCAD' in df.keys():
        if not np.isnan(df.RCAD.mean()):
            metrics['cad'] = int(df.RCAD.mean())
    if 'KM' in df.keys():
        metrics['km'] = round(df.KM.max(),2)
    if 'WATTS' in df.keys():
        if not np.isnan(df.WATTS.mean()):
            metrics['watt'] = int(df.WATTS.mean())
    return metrics

def createTopTimes(df,column):
    # df = pd.DataFrame(df.RIDE.SAMPLES)
    samples = df[column].values.tolist()
    exerciseLength = len(samples)
    res = 1 # sec per sample
    secs = [1,5,10,20,30,60,2*60,5*60,10*60,20*60,30*60,60*60]
    ts = []
    for sec in secs:
        if sec < exerciseLength:
            if sec == 10*60:
                res = 6                      # best >= 600s scans in 6s blocks
                samples  = runningMean(samples,res,res)
            t = topIndex(runningMean(samples,  sec/res,1)) *  res
            name = (f"{sec}")
            ts.append(getTopMetrics(df,name,t,t+sec))
    intervals = {}
    for topTime in ts:
        name = topTime['NAME']
        intervals[name] = topTime
    return intervals

def getHRTSS(df):
    df = df.fillna(df.mean())
    hrs = df.HR.values.tolist()
    k = 1.92
    HR_max = 180
    HR_rest = 50
    TRIMP = 0
    HRR = (160 - HR_rest) / (HR_max - HR_rest)
    TRIMP_treshold = 3600 * HRR * 0.64 * math.exp(k * HRR) 
    for hr in hrs:
        HRR = (hr - HR_rest) / (HR_max - HR_rest)
        TRIMP += HRR * 0.64 * math.exp(k * HRR)
    return int(TRIMP/TRIMP_treshold * 100)

def createIntervals(df):
    """ split when speed drops below 80% of median """
    # df = pd.DataFrame(df.RIDE.SAMPLES)
    intervals = []
    if 'KPH' in df:
        i = 0
        minSpeed = df['KPH'].median() *0.5
        idx_start = 0
        for idx,row in df.iterrows():
            if row['KPH'] < minSpeed:
                dp = df.iloc[idx_start:idx]
                dp = dp[dp['KPH'] > minSpeed]
                if len(dp.index) > 90:
                    intv = {"NAME": (f"auto_{i}"),
                            "START": dp['SECS'].iloc[0],
                            "STOP": dp['SECS'].iloc[-1],
                            }
                    intervals.append(intv)
                    i += 1
                    idx_start = idx
        # in case finish on a high speed - catch last stretch
        dp = df.iloc[idx_start:]
        if (dp['KPH'].median() >= minSpeed):
            intv = {"NAME": (f"auto_{i}"),
                    "START": dp['SECS'].iloc[0],
                    "STOP": dp['SECS'].iloc[-1],
                    }
            intervals.append(intv)
    return intervals


def getMetrics(samples,sport):
    """ returns dict with KPH,HR,KM,Watt """
    metrics = {}
    metrics['START'] = int(samples.SECS.min())
    metrics['STOP'] = int(samples.SECS.max())
    metrics['duration'] = time.strftime('%H:%M:%S', time.gmtime(len(samples.index)))
    if 'KPH' in samples.keys():
        v = round(samples.KPH[samples.KPH > 0.3*samples.KPH.mean()].mean(),2)
        if v != v:
            v = 0
        metrics['speed'] = v
        if sport in paceSports:
            if v == 0:
                metrics['pace'] = ('0:00')
            else:
                metrics['pace'] = (f'{1800/v//60:.0f}:{1800/v%60:04.1f}')
    if 'HR' in samples.keys():
        if not np.isnan(samples.HR.mean()):
            metrics['hr'] = int(samples.HR.mean())
            metrics['hrtss'] = getHRTSS(samples)
    if 'KM' in samples.keys():
        metrics['km'] = round(samples.KM.max(),1)
    if 'CAD' in samples.keys():
        metrics['cad'] = round(samples.CAD.mean(),1)
    if 'RCAD' in samples.keys():
        metrics['cad'] = round(samples.RCAD.mean(),1)
    if 'WATTS' in samples.keys():
        if not np.isnan(samples.WATTS.mean()):
            metrics['watt'] = int(samples.WATTS.mean())
    return metrics

def getFit():
    """ convert .fit to YYYY_MM_DD_HH_mm_SS.json """
    myActs = glob.glob(f'{actFolder}*.json')
    myActs = [act.split(actFolder)[1] for act in myActs]
    fitFiles = glob.glob(f'{downloadFolder}*.fit')
    fitFiles += glob.glob(f'{downloadFolder}*.FIT')
    fitFiles += glob.glob(f'{garminFolder}*.FIT')
    # only get the most recent Zwift file
    fitFiles += [max(glob.glob(f'{zwiftFolder}*.fit'), key=os.path.getmtime)]
    for fitFile in fitFiles:
        data = fj.getFit(fitFile)
        date = datetime.strptime(data['RIDE']['STARTTIME'], '%Y/%m/%d %H:%M:%S UTC ')
        filename = date.strftime('%Y_%m_%d_%H_%M_%S.json')
        if filename not in myActs:
            with open((f'{actFolder}{filename}'), 'w') as f:
                json.dump(data, f, indent=4, default=str)
            print(fitFile, ">> ", filename)
        try:
            shutil.move(fitFile, fitArchive)
        except:
            shutil.copy(fitFile, fitArchive)

def getSports(year=2020,month=8):
    """ add new activities from GC JSON """
    with open(datafile, "r") as f:
        data = json.load(f)
    acts = glob.glob(f'{actFolder}*.json')
    acts = [act.split(actFolder)[1] for act in acts]
    acts = [act.split('.json')[0] for act in acts]
    acts = [act for act in acts if ((int(act[0:4]) >= year) & (int(act[5:7]) >= month))]
    for act in acts:
        if act not in data:
            print("Processing ", act)
            data[act] = {}
            df = pd.read_json((f'{actFolder}{act}.json'), encoding='utf-8-sig')
            try:
                sport = df.RIDE.TAGS['Sport'].strip()
            except:
                sport = 'undefined'
            data[act]['sport'] = sport

            date = datetime.strptime(df.RIDE['STARTTIME'], '%Y/%m/%d %H:%M:%S UTC ')
            data[act]['date'] = date.strftime('%Y-%m-%d %H:%M:%S')
            # RESAMPLE to ensure 1s data
            samples = pd.DataFrame(df.RIDE.SAMPLES)
            samples['dt'] = [dt.datetime.fromtimestamp(s) for s in samples.SECS.values]
            samples = samples.set_index('dt')
            df = df[~df.index.duplicated(keep='first')] # drop duplicated seconds
            samples = samples.resample('1s').bfill()
            samples = samples.reset_index()
            data[act].update(getMetrics(samples,sport))

            if not 'ROUTE' in df.RIDE.keys():
                # find location
                if (('LAT' in samples.iloc[0]) and ('LON' in samples.iloc[0])):
                    lat0 = samples.iloc[0]['LAT']
                    lon0 = samples.iloc[0]['LON']
                    for l in locations:
                        lat1 = locations[l]['LAT']
                        lon1 = locations[l]['LON']
                        if (((lat0-lat1)**2+(lon0-lon1)**2)**0.5 < 0.01):
                            data[act]['route'] = l

            if 'INTERVALS' in df.RIDE.keys():
                intervals = [i for i in df.RIDE.INTERVALS if 'Lap' not in i['NAME']]
                if intervals == []:
                    intervals = createIntervals(samples)
            else:
                intervals = createIntervals(samples)
            data[act]['intervals'] = {}
            for interval in intervals:
                name = interval['NAME'].strip().replace(' ','_')
                if 'Lap' not in name:
                    subsamples = samples[(samples.SECS >= interval['START']) & (samples.SECS < interval['STOP'])]
                    data[act]['intervals'][name] = getMetrics(subsamples,sport)
            if 'KPH' in samples:
                data[act]['CV'] = createTopTimes(samples,'KPH')
                createSparkLine(samples,act,intervals,sport)
            if 'WATTS' in samples:
                data[act]['CP'] = createTopTimes(samples,'WATTS')

    data = dict(sorted(data.items(), reverse=True))
    with open(datafile, 'w') as f:
        json.dump(data, f, indent=4)
    return data


def sport2html(d):
    doc, tag, text = Doc().tagtext()
    with tag('html', ('lang','en')):
        with tag('head'):
            doc.stag('meta', ('charset', 'utf-8'))
            doc.stag('meta', ('name','viewport'), ('content',"width=device-width, initial-scale=1"))
            doc.stag('link', 
                    ('rel','stylesheet'),
                    ('href', 'graph.css')
                    )
            doc.stag('link', 
                    ('rel','stylesheet'),
                    ('href', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css'),
                    ('integrity', "sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"),
                    ('crossorigin',"anonymous")
                    )
            with tag('title'):
                text('Training activities')
        with tag('body'):
            with tag('a', ('href','https://github.com/PhilGrunewald/GoldenD3tah'), ('klass','giithub-corner'), ('aria-label','View source on GitHub')):
                with tag('svg', ('width','80'), ('height','80'), ('viewbox','0 0 250 250'), ('style','fill:#5a5; color:#ccc; position: absolute; top: 0; border: 0; right: 0;'), ('aria-hidden','true')):
                    doc.stag('path', ('d','M0,0 L115,115 L130,115 L142,142 L250,250 L250,0 Z'))
                    doc.stag('path', ('d','M128.3,109.0 C113.8,99.7 119.0,89.6 119.0,89.6 C122.0,82.7 120.5,78.6 120.5,78.6 C119.2,72.0 123.4,76.3 123.4,76.3 C127.3,80.9 125.5,87.3 125.5,87.3 C122.9,97.6 130.6,101.9 134.4,103.2'), ('fill',"currentColor"), ('style',"transform-origin: 130px 106px;"), ('klass',"octo-arm"))
                    doc.stag('path', ('d','M115.0,115.0 C114.9,115.1 118.7,116.5 119.8,115.4 L133.7,101.6 C136.9,99.2 139.9,98.4 142.2,98.6 C133.8,88.0 127.5,74.4 143.8,58.0 C148.5,53.4 154.0,51.2 159.7,51.0 C160.3,49.4 163.2,43.6 171.4,40.1 C171.4,40.1 176.1,42.5 178.8,56.2 C183.1,58.6 187.2,61.8 190.9,65.4 C194.5,69.0 197.7,73.2 200.1,77.6 C213.8,80.2 216.3,84.9 216.3,84.9 C212.7,93.1 206.9,96.0 205.4,96.6 C205.1,102.4 203.0,107.8 198.3,112.5 C181.9,128.9 168.3,122.5 157.7,114.1 C157.9,116.9 156.7,120.9 152.7,124.9 L141.0,136.5 C139.8,137.7 141.6,141.9 141.8,141.8 Z'), ('fill',"currentColor"), ('klass',"octo-body"))
            with tag('style'):
                text(""" body { background-color:#ccc; } """)
            with tag('div', klass="container"):
                with tag('div', klass='row'):
                    currentYear = 0
                    currentMonth = 0
                    for key in d:
                        dt =  datetime.strptime(d[key]['date'], '%Y-%m-%d %H:%M:%S')
                        if dt.year != currentYear:
                            with tag('div', klass="col-lg-12"):
                                with tag('h1'):
                                    text(dt.year)
                            currentYear = dt.year
                        if dt.month != currentMonth:
                            with tag('div', klass="col-lg-12"):
                                with tag('h1'):
                                    text(dt.strftime("%B"))
                            currentMonth = dt.month

                        klass = (f"border icon-{d[key]['sport']}")
                        with tag('div', klass=klass, id=(f'act_{key}')):
                            with tag('a', ('href',(f'graph.php?act="{key}"'))):
                                with tag('h2'):
                                    if 'km' in d[key]:
                                        text(d[key]['km'],'km')
                                with tag('p'):
                                    text(dt.strftime("%a, %-d %b"),'. ')
                                    if 'duration' in d[key]:
                                        dur =  datetime.strptime(d[key]['duration'], '%H:%M:%S')
                                        if dur.hour > 0:
                                            text(dur.hour,':')
                                        if dur.minute < 10:
                                            text('0',dur.minute,' min')
                                        else:
                                            text(dur.minute,' min')
                                    if 'route' in d[key]:
                                        text(', ',d[key]['route'])
                                with tag('p'):
                                    if 'pace' in d[key]:
                                        text(d[key]['pace'])
                                        with tag('small'):
                                            text(" /500m ")
                                    elif 'speed' in d[key]:
                                        text(d[key]['speed'])
                                        with tag('small'):
                                            text("km/h ")
                                    if 'cad' in d[key]:
                                        doc.stag('img', src='icon/cad.png')
                                        text(d[key]['cad'])
                                        with tag('small'):
                                            text("/min ")
                                    if 'hr' in d[key]:
                                        with tag('span',style='color:red'):
                                            text(f" \u2665")
                                        text(d[key]['hr'])
                                    if 'watt' in d[key]:
                                        text(f" \u26A1{d[key]['watt']}W")
                                    if 'hrtss' in d[key]:
                                        with tag('span',style='color:blue'):
                                            text(u"\U0001F4A7")
                                        text(d[key]['hrtss'])
                                with tag('div'):
                                    doc.stag('img', ('src',(f'sparklines/{key}.png')), klass='img-fluid')

    result = indent(doc.getvalue())
    with open(f'{home}index.html', "w") as f:
        f.write(result)

def commit():
    # os.system(f'cd {home}')
    # this needs to be run from gc folder for web push
    # the Phil folder has the github repo
    os.system(f'git add {actFolder}')
    os.system(f'git add {sparkFolder}')
    os.system('git add -u')
    os.system('git commit -m "gc update"')
    os.system('git push')

getFit()
d = getSports(2020,1)
sport2html(d)
commit()
