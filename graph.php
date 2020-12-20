<!DOCTYPE html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="graph.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous" />
<style>
body { background-color: #ccc; }
</style>
</head>
<body>
    <script src="d3.js"></script>
<a href="https://github.com/PhilGrunewald/GoldenD3tah" class="github-corner" aria-label="View source on GitHub">
    <svg width="80" height="80" viewBox="0 0 250 250" style="fill:#5a5; color:#ccc; position: absolute; top: 0; border: 0; right: 0;" aria-hidden="true">
    <path d="M0,0 L115,115 L130,115 L142,142 L250,250 L250,0 Z"></path>
    <path d="M128.3,109.0 C113.8,99.7 119.0,89.6 119.0,89.6 C122.0,82.7 120.5,78.6 120.5,78.6 C119.2,72.0 123.4,76.3 123.4,76.3 C127.3,80.9 125.5,87.3 125.5,87.3 C122.9,97.6 130.6,101.9 134.4,103.2" fill="currentColor" style="transform-origin: 130px 106px;" class="octo-arm"></path>
    <path d="M115.0,115.0 C114.9,115.1 118.7,116.5 119.8,115.4 L133.7,101.6 C136.9,99.2 139.9,98.4 142.2,98.6 C133.8,88.0 127.5,74.4 143.8,58.0 C148.5,53.4 154.0,51.2 159.7,51.0 C160.3,49.4 163.2,43.6 171.4,40.1 C171.4,40.1 176.1,42.5 178.8,56.2 C183.1,58.6 187.2,61.8 190.9,65.4 C194.5,69.0 197.7,73.2 200.1,77.6 C213.8,80.2 216.3,84.9 216.3,84.9 C212.7,93.1 206.9,96.0 205.4,96.6 C205.1,102.4 203.0,107.8 198.3,112.5 C181.9,128.9 168.3,122.5 157.7,114.1 C157.9,116.9 156.7,120.9 152.7,124.9 L141.0,136.5 C139.8,137.7 141.6,141.9 141.8,141.8 Z" fill="currentColor" class="octo-body"></path>
    </svg>
</a>


    <div class="container">
        <br/> 
        <div id="Header" class="row"></div>
            <!-- Buttons of sports --> 

        <div id="Overview" class="overview col-12"></div>
            <!-- Bar graph of activities --> 

        <div id="Graph" class="container"></div>
            <!-- Line, area graph, Map and interval table -->
    </div>
</body>

<script>

//////       Global Functions       \\\\\\\\\\

var graphsOpen = 0;
var graphID    = 1;

var paceSports = ["Row", "Erg"];

var HR_fills = [ {"min":0, "max":120, "colour": 'lightgreen','zone': "z1"},
                {"min":120, "max":140, "colour": 'lightblue','zone': "z2"},
                {"min":140, "max":160, "colour": 'orange','zone': "z3"},
                {"min":160, "max":210, "colour": 'red','zone': "z4"}];

// time formating
var dt_file = d3.timeParse("%Y_%m_%d_%H_%M_%S");
var dt = d3.timeParse("%Y-%m-%d %H:%M:%S");
var dt_dmy  = d3.timeFormat("%d %b %y");
var dt_dm  = d3.timeFormat("%d %b");
var dt_date = d3.timeFormat("%a %d %b %y");
var dt_time = d3.timeFormat("%H:%M");


function trimHours(duration) {
    // 0:15 > 15 min
    durations = duration.split(":");
    if (durations[0] == "00") {
        return parseInt(durations[1]) +":"+durations[2]
    } else {
        return parseInt(durations[0]) +":"+durations[1]+":"+durations[2]
    }
}

function sec2time(time) {
    // 210 sec > 3:30
    var pad = function(num, size) { return ('000' + num).slice(size * -1); }
    hours = Math.floor(time / 60 / 60);
    minutes = Math.floor(time / 60) % 60;
    seconds = Math.floor(time - minutes * 60);
    if (hours > 0) {
        return hours + ':' + pad(minutes, 2) + ':' + pad(seconds, 2)
    } else {
        return pad(minutes, 2) + ':' + pad(seconds, 2)
    }

}

function pace(v) {
    // 15km/h > 2:00 /500m
    let mins = Math.floor(1800/v/60);
    let secs = (1800/v%60).toFixed(1);
    if (secs < 10) {
        secs = "0"+secs;
    }
    return mins+":"+secs
}

function getCAD(cad) {
    if (cad !== undefined) {
        return "<p><img src='icon/cad.png'>"+cad+" /min</p>"
    } else {
        return ""
    }
}

function getSpeed(v,sport) {
    if (v !== undefined) {
        if (paceSports.includes(sport)) {
            // if ((sport == "Row")) || (sport == "Erg"))) {
            return pace(v) + "<small> /500m</small><br/>";
        } else {
            return v.toFixed(1) +" <small>km/h</small><br/>"
        }
    } else {
        return ""
    }
}

function getHRmax(hr,hrmax) {
    if (hr !== undefined) {
        return "<span style='color:red'>\u2665 </span>"+hr+" ("+hrmax+")<br/>"
    } else {
        return ""
    }
}

function getHR(hr) {
    if (hr !== undefined) {
        return "<span style='color:red'>\u2665 </span>"+hr+"<br/>"
    } else {
        return ""
    }
}

function getWatt(watt) {
    if (watt !== undefined) {
        return "<span style='color:red'>\u26A1</span>"+watt+"<br/>"
    } else {
        return ""
    }
}

function getTSSweek(tss,tssWeek) {
    if (tss !== undefined) {
        return "&#128167; "+tss+" ("+tssWeek+"&#8855;)<br/>"
    } else {
        return ""
    }
}

function getTSS(tss) {
    if (tss !== undefined) {
        return "&#128167; "+tss+"<br/>"
    } else {
        return ""
    }
}

function getNextMondays(start) {
    // return the Monday following a date (for bar labels)
    var Monday = 1;
    var current = new Date(start);
    current.setDate(current.getDate() + (Monday - current.getDay() + 7) % 7);
    return new Date(+current);
}

// Define the div for the tooltip
var tooltip = d3.select("body").append("div")
    .attr("class", "tooltip")
    .style("opacity", 0);


function removeGraph(key) {
    d3.select('#bar_'+key)
        .attr("opacity", 0.5);
        //style("stroke","none");
    d3.select('#row_'+key).remove();
    d3.select('#ref_'+key).remove();
    d3.select('#num_'+key).remove();
    graphsOpen -= 1;
    if (graphsOpen == 0) {graphID=1}
}

function filterSport(data,filter) {
    // remove any unwanted sports from data
    if (filter != "") {
        for (key in data) {
            var sport = data[key]['sport'];
            if (sport != filter) {
                delete data[key];
            }
        }
    }
    return d3.entries(data);
}

//////////             Overview         \\\\\\\\\\\



d3.json("activities.json").then(function(json){
    var data = filterSport(json,"<?php if (isset($_GET['sport'])) { echo $_GET['sport']; } ?>");
    var xShift = 0;     // for sliding Overview sideways

    // organise dates
    var dates = [];
    var sports = [];
    var sportCounts = [];
    var sportKMs = [];
    var cpData = {}
    var cvData = {}
    var tssData = {}
    var tssDay = []
    var tssDayDate = []

    var index = 0;      // re-index activities
    var dateBuffer = "";
    var tickgap = 0;    // clocks that at least three bars between labels

    for (key in data) {
        // gather information by sport (count,km)
        var sport = data[key]['value']['sport'];
            if (sports.includes(sport)) {
                sportCounts[sports.indexOf(sport)] ++;
                sportKMs[sports.indexOf(sport)] += data[key]['value']['km'];
            } else {
                sports.push(sport);
                sportCounts.push(1);
                sportKMs.push(data[key]['value']['km']);
            }

        // get CV, CP, TSS data
        if (data[key].value["CP"]) { cpData[data[key].key] = data[key].value["CP"]; }
        if (data[key].value["CV"]) { cvData[data[key].key] = data[key].value["CV"]; }
        if (data[key].value["hrtss"]) { 
            tssData[data[key].key] = data[key].value["hrtss"]; 
            tssDay.unshift(data[key].value["hrtss"])
            tssDayDate.unshift(dt(data[key].value.date))
        }
        dates.push(dt(data[key]['value'].date));
        data[key]['value']['index'] = index;

        // get date labels for each Monday (with at least three activities)
        var mon = getNextMondays(dt(data[key]['value'].date));
        if ((dt_dmy(mon) != dt_dmy(dateBuffer)) & (tickgap > 3)) {
            data[key]['value']['label'] = dt_dm(mon);
            dateBuffer = mon;
            tickgap = 0;
        } else {
            data[key]['value']['label'] = "";
            tickgap += 1;
        }
        index += 1

    }

    // Rolling weekly TSS values
    // Note: list must be in chronological order
    for (key in data) {
        var tssWeek = 0;
        var i = 0;
        var weekPast = new Date(dt(data[key].value.date).getTime() - 7 * 24 * 60 * 60 * 1000)
        while (tssDayDate[i] < weekPast) { i++ }
        while (tssDayDate[i] <= dt(data[key].value.date)) { 
            tssWeek += tssDay[i]
            i++;
            }
        data[key]['value']['tssWeek'] = parseInt(tssWeek/7);
    }

    var header = d3.select("#Header")

    // back button
    header.append("div")
        .append("a").attr("link:href","index.html<?php if (isset($_GET['act'])) { echo "#act_".str_replace('"','',$_GET['act']); } ?>")
        .style("margin","2px").attr("class","btn btn-light")
        .html("&#8678;");

    // sport buttons
    var listedSports = 0;
    for (i in sports) {
        if (sportCounts[i] > 1) {
            header.append("div")
            .append("a").attr("link:href","graph.php?sport="+sports[i])
            .style("margin","2px").attr("class", "btn btn-light")
            .html(parseInt(sportKMs[i]) +"km " + sports[i] +" ("+sportCounts[i]+")");
            listedSports++;
        }
    }
    // button for all sports
    if (listedSports == 1) {
        header.append("div")
            .append("a").attr("link:href","graph.php")
            .style("margin","2px").attr("class", "btn btn-light")
            .html("All sports");
    }

// end header


// Ocerview space
    var o_height = 120;
    var o_width = 800;
    var o_bars = 20;
    var overview = d3.select("#Overview")
      .append("svg").attr("viewBox", "0 0 "+o_width+" "+o_height)

    // Drag x-axis
    var zoom = d3.zoom().on("zoom", x_drag);
    overview.call(zoom);

    function x_drag() {
        if (d3.event !== null) { if (d3.event.transform !== undefined) {
            if (Math.abs(d3.event.transform.x - xShift) > 100) {d3.event.transform.x = xShift}
            xShift = d3.event.transform.x;
        } }
        let items = Object.keys(data).length;
        if (xShift > ((items-5)*o_width/o_bars)) { xShift = (items-10)*o_width/o_bars }
        if (xShift < -15*o_width/o_bars) { xShift = -10*o_width/o_bars }
        if (d3.event !== null) { if (d3.event.transform !== undefined) { d3.event.transform.x = xShift } }
        d3.selectAll(".drag")
            .transition().duration(300)
            .attr("transform", "translate(" + xShift + ",0)");
    }

    // X Axis
    x_left = d3.min([o_bars,index]);
    x_right = 0;
    var xScale = d3.scaleLinear()
        .domain([x_left, x_right])
        .range([0, o_width]);
    var o_xAxis = d3.axisBottom(xScale);

    // Y Axis
    var y = d3.scaleLinear()
        .domain([0, 150])
        .range([o_height, 0]);
    var yAxis = d3.axisLeft(y);

    // date labels
    var barText = overview.selectAll("barText")
        .data(data).enter()
        .append("text")
        .attr("class","drag").style("cursor","ew-resize").attr('text-anchor','left')
        .attr("x", function(d) { return xScale(d.value.index+0.5) })
        .attr("y", o_height/5)
        .text(function(d) { return d.value.label });

    // left nav arrow
    overview.append('text')
        .attr('x',0).attr('y', o_height/4)
        .style('font-size',30).style('fill','white')
        .html('&#8678;');
    // left nav button
    overview.append('rect')
        .style("fill", 'lightgray') .style("opacity", 0.3) .style("cursor", "w-resize")
        .attr('x',0) .attr('rx',5) .attr('ry',5) .attr('width', 40) .attr('height', 40)
        .on('mouseover', function() {d3.select(this).style('fill', 'gray')})
        .on('mouseout', function() {d3.select(this).style('fill', 'lightgray')})
        .on('mousedown', function() { xShift += o_width/2; x_drag() });

    // right nav arrow
    overview.append('text')
        .attr('x', o_width-30).attr('y', o_height/4)
        .style('font-size',30).style('fill','white')
        .html('&#8680;');
    // right nav button
    overview
        .append('rect')
        .style("fill", "lightgray") .style("opacity", 0.3) .style("cursor", "e-resize")
        .attr('x',o_width-40).attr('rx',5).attr('ry',5).attr('width', 40).attr('height', 40)
        .on('mouseover', function() {d3.select(this).style('fill', 'gray')})
        .on('mouseout', function() {d3.select(this).style('fill', 'lightgray')})
        .on('mousedown', function() { xShift -= o_width/2; x_drag() });

    // Rolling weeks TSS line
    var tssLine = overview.append("path")
        .datum(data)
        .attr("class","drag").style("opacity",1) .attr("fill", "none")
        .attr("stroke", 'lightblue').attr("stroke-width", 6)
        .attr("d", d3.line()
            .x(function(d) { return xScale(d.value.index) })
            .y(function(d) { return y(d.value.tssWeek) })
            );

    // Bar graph
    var bars = overview.selectAll("bar")
        .data(data).enter()
        .append("rect")
        .attr("id",  function(d) { return "bar_"+d.key }).attr("class",  function(d) { return "drag bar bar-"+d.value.sport;})
        .attr("opacity",0.6).style("cursor","pointer")
        .attr("x",      function(d) { return xScale(d.value.index) })
        .attr("width", o_width/o_bars)
        .attr("y",      function(d) { return y(d.value.hrtss); })
        .attr("height", function(d) { if (o_height-y(d.value.hrtss) > 0) {return o_height-y(d.value.hrtss)} else {return 10} })
        .on("mouseover", function(d) {
            tooltip.transition().duration(200)
                .style("opacity", .9);
            tooltip.html( 
                dt_date(dt(d.value.date)) + " " +
                dt_time(dt(d.value.date)) + "<br/>"+
                "<h2>"+trimHours(d.value.duration)+"</h2>"+
                d.value.km+" km<br/>" +
                getSpeed(d.value.speed,d.value.sport) +
                getHR(d.value.hr)+
                getWatt(d.value.watt)+
                getTSSweek(d.value.hrtss,d.value.tssWeek)
            )
            .style("left", (d3.event.pageX + 10) + "px")
            .style("top", y(60) + "px")
            .attr("class", "tooltip icon-"+d.value.sport) ;
        })
        .on("mouseout", function(d) {
            tooltip.transition().duration(500)
                .style("opacity", 0);
        })
    ;


    // show activity from _GET
    var actFile = false;
    <?php if (isset($_GET['act'])) { echo "actFile = ".$_GET['act'].";"; } ?>
    if (actFile) {
        if (!d3.select("#bar_"+actFile).empty()) {
            let thisID = d3.select("#bar_"+actFile)
            let thisData = thisID.data()[0];
            addGraph(thisData,thisID);
        }
    }

    function addGraph(d,selection) {
        // display graph
        if (d3.select('#row_'+d.key).empty()) {
            if ('intervals' in d.value) {
                plot_graph(d.key,graphID,d.value.intervals,cpData,cvData,tssData);
            } else {
                plot_graph(d.key,graphID,[],cpData,cvData,tssData);
            }
            // label with graph ID
            overview
                .append("circle").raise()
                .attr("class","drag marker bar-"+d.value.sport).attr("id","ref_"+d.key)
                .attr("cx", function () { return xScale(d.value.index)})
                .attr("cy", y(10))
                .attr("r", 10)
                .attr("transform", barText.attr("transform"))
                .on("click", function () {removeGraph(d.key)}) ;
            overview
                .append("text").raise()
                .attr("id","num_"+d.key).attr("class","drag marker")
                .style("text-anchor", "middle").style("cursor","pointer")
                .text(graphID)
                .attr("x", function () {return xScale(d.value.index)})
                .attr("y", y(3))
                .attr("transform", barText.attr("transform"))
                .on("click", function () {removeGraph(d.key)})
                .on("mouseover", function() {d3.select("#num_"+d.key).html("X")})
                .on("mouseout", function() {d3.select("#num_"+d.key).text(graphID)}) ;
            graphID += 1;
            graphsOpen += 1;
            xShift = -xScale(d.value.index)+o_width/3;
            x_drag();
        }
    }

    // Toggle DATA GRAPH when click bar
    overview.selectAll(".bar")
        .on("click", function(d) {
            var selection = d3.select(this);
            if (d3.select('#row_'+d.key).empty()) {
                addGraph(d,selection);
            } else {
                removeGraph(d.key);
            }
        });
}); // activities.json
// end overview


/////////////           DATA GRAPH            \\\\\\\\\\\\\\

function plot_graph(filename,graphID,sections,cpData,cvData,tssData) {
    d3.json("activities/" + filename + ".json").then(function(json){
        let sport = json.RIDE.TAGS.Sport.trim();
        var data = json['RIDE']['SAMPLES'];

        var row = d3.select("#Graph").append("div").lower()
            .attr("class", "row").attr("id", "row_"+filename);
        var graphDiv = d3.select("#row_"+filename).append("div")
            .attr("class","graph col-lg-10 col-sm-12").attr("id","graph_"+filename);
        var statsDiv = d3.select("#row_"+filename).append("div")
            .attr("class", "stats col-lg-2 col-sm-4 icon-"+sport).attr("id", "stats_"+filename);
        var mapDiv = d3.select("#row_"+filename).append("div")
            .attr("class", "map col-lg-2 col-sm-4 icon-"+sport).attr("id", "map_"+filename)
            .style("display", "none");
        var cvDiv = d3.select("#row_"+filename).append("div")
            .attr("class", "cp col-lg-6 col-sm-4").attr("id", "cv_"+filename)
            .style('display', 'none');
        var cpDiv = d3.select("#row_"+filename).append("div")
            .attr("class", "cp col-lg-6 col-sm-4").attr("id", "cp_"+filename)
            .style('display', 'none');

        // Graph Area
        g_width = 600;
        g_height = 200;
        var graph = d3.select("#graph_"+filename).append("svg")
            .attr("id", "svg_"+filename)
            .attr("viewBox", "0 0 "+g_width+" "+g_height) ;

        // x Axis
        tick = [];
        t = 0;
        while (t < data[data.length-1]['SECS']) { tick.push(t); t+=300; }

        var xShift = 0;
        var scale = 0.25;
        var x = d3.scaleLinear()
            .domain([0, g_width/scale])                              // show 1 hour
            .range([0, g_width]);
        var g_xAxis = d3.axisBottom(x);
        var gX = graph.append('g')
            .attr("class", "g_drag")
            .call(g_xAxis
                .tickValues(tick)
                .tickFormat(function(d) {return d/60 +"'"})   // minutes (from seconds)
            );
        var yKPH;       // needed for cursorLine / speedDot
        var yWatt;       // needed for cursorLine / speedDot

        // Drag x-axis
        graph.call(d3.zoom().on("zoom", x_drag));

        function x_drag() {
            xShift = d3.event.transform.x;
            d3.selectAll(".g_drag")
                .attr("transform", "translate(" + xShift + ",0)");
        }
        function shiftX() {
            d3.selectAll(".g_drag")
                .transition().duration(600)
                .attr("transform", "translate(" + xShift + ",0)");
        }

        statsDiv.append("div") 
            .style("cursor","pointer")
            .attr("id", 'numCircle_'+filename) .attr("class",'numberCircle')
            .on("click", function () {removeGraph(filename)})
            .on("mouseover", function() {d3.select("#numCircle_"+filename).html("X")})
            .on("mouseout", function() {d3.select("#numCircle_"+filename).html(graphID)})
            .html(graphID);

        var statsText = statsDiv.append("div");

        // Display cursor / brush or all stats
        function updateStats(xStart,xEnd) {
            let a = parseInt(xStart);
            let b = parseInt(xEnd);
            km = []
            v = []
            hr = []
            rcad = []
            cad = []
            watts = []
            secs = []
            tss = ""
            if (b == -1) { // all
                b = data[Object.keys(data).length-1]['SECS']
                tss = getTSS(tssData[filename])
            }
            let i = 0;
            while (data[i]['SECS'] < a) { i++; }
            while (data[i]['SECS'] < b) { i++; 
                    secs.push(data[i]['SECS']);
                    km.push(data[i]['KM']);
                    v.push(data[i]['KPH']);
                    hr.push(data[i]['HR']);
                    cad.push(data[i]['CAD']);
                    rcad.push(data[i]['RCAD']);
                    watts.push(data[i]['WATTS']);
            }

            if (km[0] !== undefined) { km = (d3.max(km)-d3.min(km)).toFixed(2) }
            if (hr[0] !== undefined) { hr = d3.mean(hr).toFixed(0)+" ("+d3.max(hr).toFixed(0)+")"} else {hr = hr[0]}
            if (rcad[0] !== undefined) {cad = d3.mean(rcad).toFixed(1)}
            else if (cad[0] !== undefined) {cad = d3.mean(cad).toFixed(1)}
            else {cad = rcad[0]}
            if (watts[0] !== undefined) {watts = d3.mean(watts).toFixed(0)+" ("+d3.max(watts).toFixed(0)+")"}
            else {watts = watts[0]}
            let durSec = d3.max(secs)-d3.min(secs)
            let durSecs = durSec%60;
            if (durSecs < 10) {durSecs = "0"+durSecs}
            duration = sec2time(durSec);
            let speed = (d3.mean(v).toFixed(1))+" ("+(d3.max(v).toFixed(1))+") <small>km/h</small><br/>";
            if (paceSports.includes(sport)) {
                speed = pace(d3.mean(v)) + "(" + pace(d3.max(v)) + ") <small> /500m</small><br/>";
            }

            statsText.html(duration+"<br/><h2>"+
                km+"<small> km</small></h2>"+
                speed+
                getCAD(cad)+
                getHR(hr)+
                getWatt(watts)+
                tss+
                "<p>"+json.RIDE.TAGS.Device+"</p>");

            mapDotStart.attr("cx",xMap(data[Object.keys(data)[a]].LON)).attr("cy",yMap(data[Object.keys(data)[a]].LAT));
            if (data[Object.keys(data)[b]] !== undefined) {
                mapDotEnd.attr("cx",xMap(data[Object.keys(data)[b]].LON)).attr("cy",yMap(data[Object.keys(data)[b]].LAT));
            }
        }

        // line graphs
        function plotData(data,column,colour) {
            var y_data = []
            data.forEach(function (d) { return y_data.push(d[column]) })
            // y Axis
            var y = d3.scaleLinear()
                .domain(d3.extent(y_data))      // y ticks
                .range([g_height, 0]);            // y axis size
            // var yAxis = d3.axisLeft(y);
            var gY = graph
                .append('g')
                .call(d3.axisLeft(y));
            gY.attr("visibility","hidden");   // hide axis
            // plot data
            // d3.selectAll(".graph_"+column+filename).remove();
            var graphPath = graph.append("path")
                .datum(data)
                .attr("class", "g_drag line graph_"+column+filename)
                .attr("id", "graph_"+column+filename)
                .attr("fill","none").attr("stroke",colour).attr("stroke-width", 1).attr("opacity", 0)
                .attr("d", d3.line()
                    .x(function(d) { return x(d.SECS) })
                    .y(function(d) { return y(d[column]) })
                )
            // add graph toggle button
            var graphBtn = graphDiv
                .append("button")
                .attr("class",'btn btn-large btn-light')
                .style("color",colour)
                .on("click", toggleGraph)
                .html(column);

            if (column == "KPH") {
               yKPH = d3.scaleLinear()
                .domain(d3.extent(y_data))      // y ticks
                .range([g_height, 0]);            // y axis size
            }
            if (column == "WATTS") {
               yWatt = d3.scaleLinear()
                .domain(d3.extent(y_data))      // y ticks
                .range([g_height, 0]);            // y axis size
               d3.selectAll(".fill_KPH"+filename).style('opacity', 0);
            }

            if ((column == "KPH") || (column == "WATTS")) {
                // line intervals
                graph.append("path")
                    .datum(data)
                    .attr("id", 'fill_'+column+filename)
                    .attr("class", 'g_drag fill_'+column+filename)
                    .attr("stroke", 'black')
                    .attr("stroke-width", 2)
                    .attr("d", d3.area()
                        .x(function(d) { return x(d.SECS) })
                        .y0(y(0))
                        .y1(function(d) { if (d.interval) {return y(d[column])}else{return y(0)} })
                    )
                for (i in HR_fills) {
                    HR_fill = HR_fills[i];
                    graph.append("path")
                        .datum(data)
                        .attr("id", 'fill_hr'+i+"_"+column+filename)
                        .attr("fill", HR_fill.colour)
                        .attr("class", "g_drag fill_"+column+filename)
                        .attr("opacity", 0.5)
                        .attr("d", d3.area()
                            .x(function(d) { return x(d.SECS) })
                            .y0(y(0))
                            .y1(function(d) { if ((d.HR >= HR_fill.min) && (d.HR < HR_fill.max)) {return y(d[column])}else{return y(0)} })
                    )
                    if (column == "KPH") {
                        graphDiv
                            .append("button")
                            .attr("class",'btn btn-large btn-light')
                            .style("opacity",0.5)
                            .style("background-color",HR_fill.colour)
                            .style("color",'black')
                            .html(HR_fill.zone);
                    }
                }
            }

            function toggleGraph() {
                if (graphPath.attr("opacity") == 0) {
                    graphPath.attr("opacity", 1);
                    graphBtn.style("color",colour);
                    if (column == "WATTS") {
                        d3.select("#cp_"+filename).style('display', 'block');
                        d3.selectAll(".fill_KPH"+filename).style('opacity', 0);
                        d3.selectAll(".fill_WATTS"+filename).style('opacity', 0.5);
                    }
                    if (column == "KPH") {
                        d3.select("#cv_"+filename).style('display', 'block');
                        d3.selectAll(".fill_KPH"+filename).style('opacity', 0.5);
                        d3.selectAll(".fill_WATTS"+filename).style('opacity', 0);
                    }
                } else {
                    graphPath.attr("opacity", 0);
                    graphBtn.style("color","gray");
                    if (column == "WATTS") {
                        d3.select("#cp_"+filename).style('display', 'none');
                        d3.selectAll(".fill_KPH"+filename).style('opacity', 0.5);
                        d3.selectAll(".fill_WATTS"+filename).style('opacity', 0);
                    }
                    if (column == "KPH") {
                        d3.select("#cv_"+filename).style('display', 'none');
                        if ("WATTS" in data[0]) {
                            d3.selectAll(".fill_KPH"+filename).style('opacity', 0);
                            d3.selectAll(".fill_WATTS"+filename).style('opacity', 0.5);
                        }
                    }
                }
            }
        } // function plotData

        function updatePlot(data,column) {
            var y_data = []
            data.forEach(function (d) { return y_data.push(d[column]) })
            var y = d3.scaleLinear()
                .domain(d3.extent(y_data))
                .range([g_height, 0]);
            var gY = graph
                .append('g')
                .call(d3.axisLeft(y));
            gY.attr("visibility","hidden");   // hide axis
            d3.select("#graph_"+column+filename)
                .datum(data)
                .transition().duration(800)
                .attr("d", d3.line()
                    .x(function(d) { return x(d.SECS) })
                    .y(function(d) { return y(d[column]) })
                )

            d3.select("#fill_"+column+filename)
                .datum(data)
                .transition().duration(800)
                .attr("d", d3.area()
                    .x(function(d) { return x(d.SECS) })
                    .y0(y(0))
                    .y1(function(d) { if (d.interval) {return y(d[column])}else{return y(0)} })
                )
            for (i in HR_fills) {
                HR_fill = HR_fills[i];
                d3.select("#fill_hr"+i+"_"+column+filename)
                    .datum(data)
                    .transition().duration(800)
                    .attr("fill", HR_fill.colour)
                    .attr("d", d3.area()
                        .x(function(d) { return x(d.SECS) })
                        .y0(y(0))
                        .y1(function(d) { if ((d.HR >= HR_fill.min) && (d.HR < HR_fill.max)) {return y(d[column])}else{return y(0)} })
                        )}
            tick = [];
            t = 0;
            console.log(scale);
            if (scale > 1) { // every minute
                while (t < data[data.length-1]['SECS']) { tick.push(t); t+=60; }
            } else if (scale > 0.2) {
                while (t < data[data.length-1]['SECS']) { tick.push(t); t+=300; }
            } else if (scale > 0.1) {
                while (t < data[data.length-1]['SECS']) { tick.push(t); t+=600; }
            } else {
                while (t < data[data.length-1]['SECS']) { tick.push(t); t+=1200; }
            }
            gX.call(g_xAxis
                .tickValues(tick)
                .tickFormat(function(d) {return d/60 +"'"})
                    )
        }

        // Tooltip on hover
        function mouseResponse() {
            // let xMouse = d3.mouse(this)[0]-xShift; ///scale;
            let xMouse = d3.mouse(this)[0]-xShift; ///scale;
            console.log("xShift ",xShift);
            console.log(xMouse/scale);
            console.log(scale);
            console.log((xMouse-300)/(4*scale)+300)
            // xMouse = ((xMouse-300)/(4*scale)+300);

            // xMouse += 300;

            i=0;
            while (data[i]['SECS'] < xMouse/scale) { i+=1; }
            // while (data[i]['SECS'] < xMouse*4) { i+=1; }
            let d = data[i];
            // console.log(d);
            if ((xMouse >= 0) & (i < data.length-2)) {
                cursorLine.attr("x1", xMouse+xShift).attr("x2", xMouse+xShift);
                speedDot.attr("cx", xMouse+xShift).attr("cy", yKPH(d.KPH));
                if ("WATTS" in d) { powerDot.attr("cx", xMouse+xShift).attr("cy", yWatt(d.WATTS)); }
                tooltip.transition().duration(100)
                    .style("opacity", .9)
                    .style("left",(d3.event.pageX + 25) + "px").style("top", (d3.event.pageY + 25) + "px")
                    .attr("class", "tooltip icon-undefined") ;
                tooltip.html("<p>"+parseInt(d.KM*100)/100+" km</><h2>" +
                    getSpeed(d.KPH,sport)+ "</h2>" +
                    getHR(d.HR)+
                    getWatt(d.WATTS)+
                    getCAD(d.RCAD)+
                    getCAD(d.CAD)
                );
                // mark on map
                mapDotStart.attr("cx",xMap(d.LON)).attr("cy",yMap(d.LAT));
            } else {
                tooltip.transition().duration(100).style("opacity",0)
            }
        }

        // Cursor line
        var cursorLine = graph.append('line')
            .attr("stroke", "black").attr("stroke-width", 1)
            .attr('x1',0).attr('x2',0)
            .attr('y1',0).attr('y1',g_height)

        // Cursor line - power
        var powerDot = graph.append('circle')
            .style("opacity", 0.5).attr("fill", "red")
            .attr("r", 5).attr('cy',-10) // out of sight

        // Cursor line - speed
        var speedDot = graph.append('circle')
            .style("opacity", 0.5)
            .attr("fill", "blue").attr("r", 5).attr('cy',-10) // out of sight

        // Highlight area
        var highlightArea = graph.append('rect')
            .attr('class','highlightArea g_drag').attr('id','highlightArea_'+filename)
            .style("fill", "white").style("opacity", 0.8)
            .attr('x',0).attr('width', 0).attr('height', g_height)


        function brushOff() {
            graph.call(d3.brushX().move, null);
            graph.on(".brush", null);
            graph.call(d3.zoom().on("zoom", x_drag));
            updateStats(0,-1);
            mapSelection.attr("stroke","black");
            d3.select('#select_'+filename)
                .html("Selection")
                .on("click", function() { brushOn() })
            tooltipOn();
        }

        function brushOn()  {
            graph.on(".zoom", null);
            graph.call(d3.brushX()
                .extent( [ [0,0], [g_width,g_height] ] )
                .on("brush end", function (d) {
                if (d3.event !== null) {
                    extent = d3.event.selection;
                    xStart = (extent[0]-xShift)/scale;
                    xEnd   = (extent[1]-xShift)/scale;
                    updateStats(xStart,xEnd);
                    mapPath(xStart,xEnd);
                }})
            ) ;
            tooltipArea.attr('width',0).attr('height',0)
            d3.select('#select_'+filename)
                .html("Clear selection")
                .on("click", function() { brushOff() })
        }

        // Intervals
        var intervalCount = 0
        for (key in sections) { intervalCount += 1}

        function displayIntervals() {
            d3.select("#intervals_table_"+filename).remove();
            var section = sections[Object.keys(sections)[0]];
            var titles = ['Section'];
            if ('speed' in section) {titles.push('Speed')}
            if ('hr' in section) {titles.push('<span style="color:red">\u2665 </span>')}
            if ('watt' in section) {titles.push('<span style="color:red">\u26A1</span>')}

            var table = d3.select("#row_"+filename)
                .append("div")
                .attr("class", "col-4")
                    .append("table")
                    .attr("class", "table table-condensed table-hover")
                    .attr("id","intervals_table_"+filename);
            var headers = table.append('thead').append('tr')
               .selectAll('th')
               .data(titles).enter()
               .append('th')
               .html(function (d) { return d; });
            var rows = table.append('tbody');
            for (key in sections) {
                d = sections[key];
                var row =  rows
                    .append('tr')
                    .datum(d)
                    .attr('class', function (d) {
                        if (key.includes('KPH')) {return 'KPH'}
                        else if (key.includes('WATTS')) {return 'WATTS'}
                        else {return 'Interval'}
                    })
                    .on("mouseover", function (d) { highlightInterval(d.START,d.STOP); })
                row.append('td').text(key);
                if (paceSports.includes(sport)) { row.append('td').text(pace(d.speed)); } 
                else                            { row.append('td').text(d.speed); }
                if ('hr' in d)                  { row.append('td').text(d.hr) }
                if ('watt' in d)                { row.append('td').text(d.watt) }
            }
        }

        function toggleIntervals() {
            if (d3.select('#intervals_table_'+filename).empty()) {
                displayIntervals();
            } else {
                d3.select("#intervals_table_"+filename).remove();
                d3.selectAll("#highlightArea_"+filename)
                    .attr('x',0)
                    .attr('width',0);
                updateStats(0,-1);
            }
        }

        function highlightInterval(a,b) {
            // mark interval area in data graph
            xShift = -(a*scale)+g_width/3;
            shiftX();
            d3.select("#highlightArea_"+filename)
                .transition().duration(400)
                .attr('x',a*scale+xShift)
                .attr('width',1+(b-a)*scale);
            mapPath(a,b);
            updateStats(a,b);
        }

        if ("KPH"   in data[0]) { plotData(data,'KPH','blue') };
        if ("WATTS" in data[0]) { plotData(data,'WATTS','red') };
        if ("RCAD"  in data[0]) { plotData(data,'RCAD','green') };
        if ("CAD"   in data[0]) { plotData(data,'CAD','green') };
        if ("ALT"   in data[0]) { plotData(data,'ALT','blue') };

        // full area to catch mouse events
        var tooltipArea = graph.append('rect')
        var navLeftText = graph.append('text')
        var navLeftBtn = graph.append('rect')
        var navRightText = graph.append('text')
        var navRightBtn = graph.append('rect')
        function tooltipOn() {
            tooltipArea.raise()
                .style("fill", "none").style("pointer-events", "all").attr('class', "g_tooltip")
                .attr('y',0).attr('x',0).attr('width',g_width).attr('height',g_height)
                .on('mousemove', mouseResponse)
                .on('mouseout', function(d) { tooltip.transition() .duration(500) .style("opacity", 0);});

            // LEFT nav button
            navLeftText.raise().attr('x',5).attr('y', 30).style('font-size',30).style('fill','white').html('&#8678;');
            navLeftBtn.raise().style("fill", 'white') .style("opacity", 0.3) .style("cursor", "w-resize").attr('rx',5).attr('ry',5).attr('width', 40).attr('height', 40)
            .attr('x',0).attr('y',0) 
            .on('mouseover', function() {d3.select(this).style('fill', 'gray')}).on('mouseout', function() {d3.select(this).style('fill', 'white')})
            .on('mousedown', function() { xShift += g_width/2; shiftX(); });

            // RIGHT nav button
            navRightText.raise().attr('x', g_width-35) .attr('y', 30) .style('font-size',30) .style('fill','white').html('&#8680;');
            navRightBtn.raise().style("fill", 'white') .style("opacity", 0.3) .style("cursor", "e-resize").attr('rx',5).attr('ry',5).attr('width', 40).attr('height', 40)
            .attr('x',g_width-40).attr('y',0) 
            .on('mouseover', function() {d3.select(this).style('fill', 'gray')}).on('mouseout', function() {d3.select(this).style('fill', 'white')})
            .on('mousedown', function() { xShift -= g_width/2; shiftX(); });
        }

        // BUTTONS UNDER GRAPH
        // Intervals button
        if (intervalCount > 0) {
            graphDiv.append("div")
                .attr("id",'toggle_intervals_'+filename).attr("class", "btn btn-light")
                .style("margin","10px")
                .html(intervalCount +" intervals")
                .on("click", toggleIntervals);
        }

        // Map button
        graphDiv.append("div")
            .attr("id",'toggle_maps_'+filename).attr("class","btn btn-light")
            .on("click", function() { 
                if (d3.selectAll(".map").style('display') === 'none') {
                    d3.selectAll(".map").style('display', 'block'); 
                    d3.select(this).attr("class", "btn btn-dark")
                } else {
                    d3.selectAll(".map").style('display', 'none'); 
                    d3.select(this).attr("class", "btn btn-light")
                }
            })
            .html("Map");

        // Zoom out button
        graphDiv.append("div")
            .attr("class", "btn btn-light")
            .on("click", function() {
                left = x.domain()[0];
                right = x.domain()[1];
                range = right - left;
                left = left - range/4
                right = right + range/4
                let items = Object.keys(data).length;
                xmax = data[items-1].SECS;
                if (right > xmax*1.5) { right = xmax*1.5 }
                if (left < 0 ) { left = 0 }
                x.domain([left,right]);
                scale = g_width/(right - left);
                if ("KPH"   in data[0]) { updatePlot(data,'KPH') };
                if ("RCAD"  in data[0]) { updatePlot(data,'RCAD') };
                if ("CAD"  in data[0]) { updatePlot(data,'CAD') };
                if ("WATTS" in data[0]) { updatePlot(data,'WATTS') };
                if ("ALT" in data[0]) { updatePlot(data,'ALT') };
                })
            .html("-");

        // Zoom in button
        graphDiv.append("div")
            .attr("class", "btn btn-light")
            .on("click", function() {
                left = x.domain()[0];
                right = x.domain()[1];
                range = right - left;
                left = left + range/4
                right = right - range/4
                let items = Object.keys(data).length;
                xmax = data[items-1].SECS;
                //if (right > xmax*1.5) { right = xmax*1.5 }
                //if (right > xmax) { right = xmax }
                //if (left < 0 ) { left = 0 }
                x.domain([left,right]);
                scale = g_width/(right - left);
                //shiftX();
                if ("KPH"   in data[0]) { updatePlot(data,'KPH') };
                if ("RCAD"  in data[0]) { updatePlot(data,'RCAD') };
                if ("CAD"  in data[0]) { updatePlot(data,'CAD') };
                if ("WATTS" in data[0]) { updatePlot(data,'WATTS') };
                if ("ALT" in data[0]) { updatePlot(data,'ALT') };
                // console.log("xShift: ", xShift);
                // xShift = left-g_width/2; //  + (right-left)/2;
                console.log("scale: ", scale);
            })
            .html("+");

        graphDiv
            .append("div")
            .attr("id",'select_'+filename)
            .attr("class", "btn btn-light")
            .on("click", function() { brushOn() })
            .html("Select");

        tooltipOn();


////////////////////////////// CV GRAPH \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    // CV GRAPH
        var cvDivGraph = d3.select("#cv_"+filename)
          .append("div")
          .style("text-align","center")
          .html("Critical Speed") ;
        cvWidth = 500;
        cvHeight = 500;
        var cvMax = 0;
        for (key in cvData) {
            cvMax = d3.max([cvData[key]["1"].speed,cvMax]);
        }

        var cvGraph = cvDivGraph
            .append("svg")
            .attr("viewBox", "0 0 "+cvWidth+" "+cvHeight) ;
        var xScaleCV = d3.scaleLog()
            .domain([1, 3600])
            .range([0, cvWidth]);
        var yScaleCV = d3.scaleLinear()
            .domain([cvMax,1])
            .range([0, cvHeight]);
        for (key in cvData) {
            var cvPoints = d3.entries(cvData[key]);
            var colour = "lightgray";
            var cvWidth = 1;
            if (key == filename) { 
                colour = 'red';
                cvWidth = 4;
            }
            cvGraph
                .append("path")
                .datum(cvPoints)
                .attr("fill", "none")
                .attr("stroke", colour)
                .attr("stroke-width", cvWidth)
                .attr("d", d3.line()
                    .x(function(d) { return xScaleCV(d.value.STOP-d.value.START) })
                    .y(function(d) { return yScaleCV(d.value.speed) })
                    );
            for (cvPoint in cvPoints) {
                cvPoints[cvPoint]['dt'] = dt_date(dt_file(key));
                cvGraph
                    .append("circle")
                    .datum(cvPoints[cvPoint])
                    .attr("class","cv_"+key)
                    .attr("fill", "gray")
                    .attr("r", 2)
                    .attr("cx", function(d) {return xScaleCV(d.value.STOP-d.value.START) })
                    .attr("cy",function(d) { return yScaleCV(d.value.speed) })
                    .on("mouseover", function(d) {
                        var duration = d.value.STOP-d.value.START;
                        if (duration >= 60) {
                            duration = parseInt(duration/60) + "min"
                        } else {
                            duration = duration + "sec"
                        }
                        d3.select(this).attr("r", 5);
                        tooltip.transition()
                            .duration(200)
                            .style("opacity", .9);
                        tooltip.html(d.dt + 
                            "<h2>"+(getSpeed(d.value.speed,sport))+" km/h<small> for "+duration+"</small></h2>"+
                            getWatt(d.value.watt)+
                            getHR(d.value.hr))
                            .style("left", (d3.event.pageX + 10) + "px")
                            .style("top", (d3.event.pageY - 200) + "px")
                            .attr("class", "tooltip icon-bike" ) 
                            ;
                        })
                    .on("mouseout", function(d) { d3.select(this).attr("r", 2) })
                }
        }

        d3.selectAll(".cv_"+filename)
            .attr("fill","red")
            .attr("r",5)
            .raise()
            .on("mouseover", function(d) {
                var duration = d.value.STOP-d.value.START;
                if (duration >= 60) {
                    duration = parseInt(duration/60) + "min"
                } else {
                    duration = duration + "sec"
                }
                d3.select(this).attr("r", 10);
                tooltip.transition()
                    .duration(200)
                    .style("opacity", .9);
                tooltip.html("This session<h2>"+(getSpeed(d.value.speed,sport))+" km/h</h2>"+duration+getHR(d.value.hr) )
                    .style("left", (d3.event.pageX + 10) + "px")
                    .style("top", (d3.event.pageY -100) + "px")
                    .attr("class", "tooltip icon-bike" ) 
                    ;
                highlightInterval(d.value.START,d.value.STOP);
                mapPath(d.value.START,d.value.STOP);
                })
            .on("mouseout", function(d) { d3.select(this).attr("r", 3) });
        //////////////////////// CP  \\\\\\\\\\\\\\\\\\\\\
        //
        //



    // CP GRAPH
        var cpDiv = d3.select("#cp_"+filename)
          .append("div")
          .style("text-align","center")
          .html("Critical Power") ;
          ;
        cpWidth = 500;
        cpHeight = 500;
        var cpMax = 0;
        for (key in cpData) {
            cpMax = d3.max([cpData[key]["1"].watt,cpMax]);
        }
        var cpGraph = cpDiv
            .append("svg")
            .attr("viewBox", "0 0 "+cpWidth+" "+cpHeight) ;
        var xScaleCP = d3.scaleLog()
            .domain([0.5, 4000])
            .range([0, cpWidth]);
        var yScaleCP = d3.scaleLinear()
            .domain([cpMax+10,1])
            .range([0, cpHeight]);
        for (key in cpData) {
            var cpPoints = d3.entries(cpData[key]);
            var colour = "gray";
            var cpWidth = 1;
            if (key == filename) { 
                colour = 'red';
                cpWidth = 2;
            }
            cpGraph
                .append("path")
                .datum(cpPoints)
                .attr("fill", "none")
                .attr("stroke", colour)
                .attr("stroke-width", cpWidth)
                .attr("d", d3.line()
                    .x(function(d) { return xScaleCP(d.value.STOP-d.value.START) })
                    .y(function(d) { return yScaleCP(d.value.watt) })
                    );
            for (cpPoint in cpPoints) {
                cpPoints[cpPoint]['dt'] = dt_date(dt_file(key));
                cpGraph
                    .append("circle")
                    .datum(cpPoints[cpPoint])
                    .attr("class","cp_"+key)
                    .attr("fill", "gray")
                    .attr("r", 2)
                    .attr("cx", function(d) {return xScaleCP(d.value.STOP-d.value.START) })
                    .attr("cy",function(d) { return yScaleCP(d.value.watt) })
                    .on("mouseover", function(d) {
                        var duration = d.value.STOP-d.value.START;
                        if (duration >= 60) {
                            duration = parseInt(duration/60) + "min"
                        } else {
                            duration = duration + "sec"
                        }
                        d3.select(this).attr("r", 5);
                        tooltip.transition()
                            .duration(200)
                            .style("opacity", .9);
                        tooltip.html(d.dt + 
                            "<h2>"+getWatt(d.value.watt)+"<small> for "+duration+"</small></h2>"+
                            getHR(d.value.hr))
                            .style("left", (d3.event.pageX + 10) + "px")
                            .style("top", (d3.event.pageY - 200) + "px")
                            .attr("class", "tooltip icon-bike" ) 
                            ;
                        })
                    .on("mouseout", function(d) { d3.select(this).attr("r", 2) })
                }
        }

        d3.selectAll(".cp_"+filename)
            .attr("fill","red")
            .attr("r",5)
            .raise()
            .on("mouseover", function(d) {
                var duration = d.value.STOP-d.value.START;
                if (duration >= 60) {
                    duration = parseInt(duration/60) + "min"
                } else {
                    duration = duration + "sec"
                }
                d3.select(this).attr("r", 10);
                tooltip.transition()
                    .duration(200)
                    .style("opacity", .9);
                tooltip.html("This session<h2>"+getWatt(d.value.watt)+"</h2>"+duration+getHR(d.value.hr) )
                    .style("left", (d3.event.pageX + 10) + "px")
                    .style("top", (d3.event.pageY -100) + "px")
                    .attr("class", "tooltip icon-bike" ) 
                    ;
                highlightInterval(d.value.START,d.value.STOP);
                mapPath(d.value.START,d.value.STOP);
                })
            .on("mouseout", function(d) { d3.select(this).attr("r", 3) });

    //////////////////////////      MAP          \\\\\\\\\\\\\\\\\\\\\\\\
        m_width = 200;
        m_height = 200;
        var route = "Route";
        if (json.RIDE.TAGS.Route) {
            route = json.RIDE.TAGS.Route;
        }
        var map = d3.select("#map_"+filename)
          .append("div")
                .html("<h2>"+route+"</h2>")
          .append("svg")
            .attr("id", "map_"+filename)
            .attr("viewBox", "0 0 "+m_width+" "+m_height)
          ;

        // x and y data
        var margin = 20;
        var y_data = [];
        var x_data = [];
        let xy_data = [];
        data.forEach(function (d) { if ((d.LON) && (d.LAT)) {
            x_data.push(d.LON); 
            y_data.push(d.LAT); 
            xy_data.push({'x':d.LON,'y':d.LAT});
        }})

        // x Axis
        var xMap = d3.scaleLinear()
            .domain(d3.extent(x_data))
            .range([margin, m_width-margin]);
        var m_xAxis = d3.axisBottom(xMap);
        map.append('g')
          .attr("visibility","hidden")   // hide axis
          .call(m_xAxis)
        // y Axis
        var yMap = d3.scaleLinear()
            .domain(d3.extent(y_data))      // y ticks
            .range([m_height-margin, margin]);            // y axis size
        var yAxis = d3.axisLeft(yMap);
        map.append('g')
            .attr("visibility","hidden")   // hide axis
            .call(yAxis);
        // plot data
        map.append("path")
            .datum(xy_data)
            .attr("stroke", "black")
            .attr("fill", "none")
            .attr("stroke-width", 2)
            .attr("d", d3.line()
                .x(function(d) { return xMap(d.x) })
                .y(function(d) { return yMap(d.y) })
            )

        var mapSelection
        function mapPath(a,b) {
            if (!d3.select("#map_selection_"+filename).empty()) {
                mapSelection.remove()
            }
            let xy = [];
            i = 0;
            while (data[i]['SECS'] < a) {i++}
            startLON = data[i].LON;
            startLAT = data[i].LAT;
            while (data[i]['SECS'] < b) {i++
                xy.push({'x':data[i].LON,'y':data[i].LAT});
            }
            endLON = data[i].LON;
            endLAT = data[i].LAT;

            mapSelection = map.append("path")
                .datum(xy)
                .attr("id", "map_selection_"+filename)
                .attr("stroke", "red")
                .attr("fill", "none")
                .attr("stroke-width", 2)
                .attr("d", d3.line()
                    .x(function(d) { return xMap(d.x) })
                    .y(function(d) { return yMap(d.y) })
                )
            mapDotStart
                .attr("cx",xMap(startLON))
                .attr("cy",yMap(startLAT))
            mapDotEnd
                .attr("cx",xMap(endLON))
                .attr("cy",yMap(endLAT))
        }

        d3.json("ini.json").then(function(ini) {
            for (l in ini.locations) { 
             map.append("text")
              .style("color", "green")
              .style("font-size", 25)
              .attr("x",xMap(ini.locations[l].LON))
              .attr("y",yMap(ini.locations[l].LAT))
              .text(l);
            }
        });

        // location dot
        var mapDotStart = map.append("circle")
          .style("fill", "green")
          .attr("stroke", "black")
          .attr('r', 5)
          .attr("cx",xMap(data[0].LON))    // starting point
          .attr("cy",yMap(data[0].LAT))
        var mapDotEnd = map.append("circle")
          .style("fill", "red")
          .attr("stroke", "black")
          .attr('r', 5)
          .attr("cx",xMap(data[0].LON))    // starting point
          .attr("cy",yMap(data[0].LAT))

        updateStats(0,-1);
    }); // json data function
} // graph file function

</script>
</body>
